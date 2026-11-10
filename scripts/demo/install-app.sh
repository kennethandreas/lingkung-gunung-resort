#!/usr/bin/env bash
# =====================================================================
# Pasang / perbarui aplikasi di server demo. Dijalankan otomatis oleh deploy.sh:
#   sudo bash install-app.sh DOMAIN
# Pemasangan pertama: membuat .env (demo: Midtrans sandbox, email ke log, verifikasi email mati),
# migrate, data brosur, foto contoh, stok harian. Berikutnya: hanya update kode + migrate.
# Data (database, foto unggahan, .env) TIDAK dihapus saat update.
# =====================================================================
set -euo pipefail

DOMAIN="${1:?Pakai: sudo bash install-app.sh DOMAIN}"
APP_DIR=/var/www/lingkung-gunung
DEPLOY_USER="${SUDO_USER:-ubuntu}"
# shellcheck disable=SC1091
source /root/lingkung-db.env

step() { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }
art() { sudo -u www-data php artisan "$@"; }
set_env() { # set_env KUNCI NILAI (ganti baris yang ada atau tambahkan)
    local key="$1" value="$2"
    if grep -q "^${key}=" "$APP_DIR/.env"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$APP_DIR/.env"
    else
        printf '%s=%s\n' "$key" "$value" >> "$APP_DIR/.env"
    fi
}

FIRST=false
[ -f "$APP_DIR/.env" ] || FIRST=true

if ! $FIRST && [ -f "$APP_DIR/artisan" ]; then
    step "Mode pemeliharaan"
    art down --retry=15 || true
fi

step "Ekstrak kode"
tar -xzf /tmp/rilis.tar.gz -C "$APP_DIR"
rm -f /tmp/rilis.tar.gz
chown -R "$DEPLOY_USER":www-data "$APP_DIR"
cd "$APP_DIR"

step "Composer (tanpa paket development)"
sudo -u "$DEPLOY_USER" composer install --no-dev --optimize-autoloader --no-interaction --no-progress

if $FIRST; then
    step "Membuat .env demo"
    cp .env.production.example .env
    set_env APP_KEY "$(php artisan key:generate --show --no-ansi)"
    set_env DB_USERNAME lingkung_app
    set_env DB_PASSWORD "$DB_APP_PASSWORD"
    set_env MAIL_MAILER log
    set_env MAIL_FROM_ADDRESS "\"noreply@${DOMAIN}\""
    set_env MIDTRANS_IS_PRODUCTION false
    set_env CUSTOMER_EMAIL_VERIFICATION false
    set_env QUEUE_VIA_SCHEDULER false
    set_env TRUSTED_PROXIES ""
    set_env BACKUP_ARCHIVE_PASSWORD "$(openssl rand -hex 24)"
    if [ -s /tmp/midtrans.env ]; then
        while IFS='=' read -r key value; do
            [ -n "$key" ] && set_env "$key" "$(printf '%s' "$value" | tr -d '\r')"
        done < /tmp/midtrans.env
    fi
fi
rm -f /tmp/midtrans.env

# HTTPS aktif? → URL https + cookie Secure. Belum → http (agar login tetap bisa dicoba)
if [ -d "/etc/letsencrypt/live/${DOMAIN}" ]; then
    set_env APP_URL "https://${DOMAIN}"
    set_env SESSION_SECURE_COOKIE true
else
    set_env APP_URL "http://${DOMAIN}"
    set_env SESSION_SECURE_COOKIE false
fi
chown root:www-data .env
chmod 640 .env

step "Izin folder"
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

step "Migrate database (user migrate, sementara)"
art optimize:clear >/dev/null
sudo -u www-data env DB_USERNAME=lingkung_migrate DB_PASSWORD="$DB_MIGRATE_PASSWORD" php artisan migrate --force

if $FIRST; then
    step "Data awal: brosur, role, konten, foto contoh, stok 365 hari"
    art db:seed --force
    art db:seed --class=PlaceholderMediaSeeder --force
    [ -e public/storage ] || php artisan storage:link
    art inventory:generate
fi

step "Optimasi & restart"
art optimize
art filament:optimize
systemctl restart php8.4-fpm
art queue:restart
systemctl restart lingkung-queue
art up

echo
echo "Selesai: $(grep '^APP_URL=' .env | cut -d= -f2)"
if $FIRST; then
    echo "Langkah berikut: buat akun Owner admin panel (lihat docs/DEMO-ORACLE-CLOUD.md langkah 7)."
fi
