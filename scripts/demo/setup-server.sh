#!/usr/bin/env bash
# =====================================================================
# Pemasangan server demo Lingkung Gunung (SEKALI SAJA)
# Ubuntu 24.04 (Oracle Cloud Always Free). Dijalankan otomatis oleh deploy.sh --pertama:
#   sudo bash setup-server.sh DOMAIN [EMAIL_UNTUK_SERTIFIKAT]
# Memasang: Nginx, PHP 8.4, MySQL, Composer, Certbot (HTTPS), firewall, cron, queue worker.
# Aman diulang (setiap langkah dicek dulu).
# =====================================================================
set -euo pipefail

DOMAIN="${1:?Pakai: sudo bash setup-server.sh DOMAIN [EMAIL]}"
EMAIL="${2:-}"
APP_DIR=/var/www/lingkung-gunung
DEPLOY_USER="${SUDO_USER:-ubuntu}"
export DEBIAN_FRONTEND=noninteractive

step() { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }

step "1/9 Swap 2 GB (cadangan memori)"
if ! swapon --show | grep -q '/swapfile'; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

step "2/9 Paket sistem, PHP 8.4, Nginx, MySQL"
echo iptables-persistent iptables-persistent/autosave_v4 boolean true | debconf-set-selections
echo iptables-persistent iptables-persistent/autosave_v6 boolean true | debconf-set-selections
apt-get update -y
apt-get install -y software-properties-common ca-certificates curl unzip git openssl
if ! ls /etc/apt/sources.list.d/ | grep -q ondrej; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
apt-get install -y nginx mysql-server certbot python3-certbot-nginx iptables-persistent \
    php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath

step "3/9 Pengaturan PHP"
for sapi in fpm cli; do
    cat > "/etc/php/8.4/${sapi}/conf.d/99-lingkung-gunung.ini" <<'INI'
expose_php = Off
display_errors = Off
memory_limit = 512M
upload_max_filesize = 16M
post_max_size = 20M
max_execution_time = 60
INI
done
systemctl restart php8.4-fpm

step "4/9 Composer"
if ! command -v composer >/dev/null; then
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    if [ "$EXPECTED" != "$ACTUAL" ]; then echo "Checksum installer Composer tidak cocok, dibatalkan."; exit 1; fi
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm /tmp/composer-setup.php
fi

step "5/9 Firewall server: buka port 80 & 443"
for port in 80 443; do
    if ! iptables -C INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT 2>/dev/null; then
        line="$(iptables -L INPUT --line-numbers | awk '/REJECT/ {print $1; exit}')"
        if [ -n "$line" ]; then
            iptables -I INPUT "$line" -p tcp --dport "$port" -m state --state NEW -j ACCEPT
        else
            iptables -A INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT
        fi
    fi
done
netfilter-persistent save

step "6/9 Database & 2 user (migrate: ubah struktur; app: tanpa DROP/GRANT)"
if [ ! -f /root/lingkung-db.env ]; then
    MIG_PASS="$(openssl rand -hex 18)"
    APP_PASS="$(openssl rand -hex 18)"
    mysql <<SQL
CREATE DATABASE IF NOT EXISTS lingkung_gunung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'lingkung_migrate'@'localhost' IDENTIFIED BY '${MIG_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON lingkung_gunung.* TO 'lingkung_migrate'@'localhost';
CREATE USER IF NOT EXISTS 'lingkung_app'@'localhost' IDENTIFIED BY '${APP_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE, SHOW VIEW, TRIGGER, LOCK TABLES ON lingkung_gunung.* TO 'lingkung_app'@'localhost';
FLUSH PRIVILEGES;
SQL
    printf 'DB_MIGRATE_PASSWORD=%s\nDB_APP_PASSWORD=%s\n' "$MIG_PASS" "$APP_PASS" > /root/lingkung-db.env
    chmod 600 /root/lingkung-db.env
fi
# MySQL hanya untuk server ini (tidak terbuka ke internet)
grep -q '^bind-address' /etc/mysql/mysql.conf.d/mysqld.cnf && sed -i 's/^bind-address.*/bind-address = 127.0.0.1/' /etc/mysql/mysql.conf.d/mysqld.cnf
systemctl restart mysql

step "7/9 Folder aplikasi & Nginx"
mkdir -p "$APP_DIR"
chown "$DEPLOY_USER":www-data "$APP_DIR"
# Hanya dibuat sekali: Certbot menambahkan blok HTTPS ke file ini, jangan ditimpa saat diulang
if [ ! -f /etc/nginx/sites-available/lingkung-gunung ]; then
cat > /etc/nginx/sites-available/lingkung-gunung <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;
    server_tokens off;
    client_max_body_size 20M;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml text/plain application/xml;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Aset hasil build & foto: cache lama di browser
    location ~* ^/(build|storage|images|fonts)/ {
        expires 30d;
        access_log off;
        try_files \$uri =404;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
NGINX
fi
ln -sf /etc/nginx/sites-available/lingkung-gunung /etc/nginx/sites-enabled/lingkung-gunung
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

step "8/9 Cron (jadwal tiap menit) & queue worker (email)"
echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1" | crontab -u www-data -
cat > /etc/systemd/system/lingkung-queue.service <<UNIT
[Unit]
Description=Lingkung Gunung queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable lingkung-queue >/dev/null

step "9/9 HTTPS (Let's Encrypt)"
SERVER_IP="$(curl -fsS https://api.ipify.org || true)"
DNS_IP="$(getent ahostsv4 "$DOMAIN" | awk 'NR==1 {print $1}' || true)"
if [ -n "$EMAIL" ] && [ -n "$SERVER_IP" ] && [ "$SERVER_IP" = "$DNS_IP" ]; then
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect || echo "Certbot gagal, ulangi nanti: sudo certbot --nginx -d $DOMAIN"
else
    echo "HTTPS dilewati: domain ${DOMAIN} mengarah ke '${DNS_IP:-?}', IP server '${SERVER_IP:-?}' (atau email kosong)."
    echo "Setelah DuckDNS benar, jalankan: sudo certbot --nginx -d ${DOMAIN} -m EMAIL --agree-tos --redirect, lalu deploy ulang."
fi

echo
echo "Server siap. Lanjutkan pemasangan aplikasi (install-app.sh dijalankan otomatis oleh deploy.sh)."
