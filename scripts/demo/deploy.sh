#!/usr/bin/env bash
# =====================================================================
# Kirim website ke server demo (dijalankan di LAPTOP, Git Bash, dari folder proyek).
#
# Pemasangan pertama:
#   bash scripts/demo/deploy.sh ubuntu@IP_SERVER "C:/lokasi/ssh-key.key" nama.duckdns.org --pertama email@anda.com
# Update berikutnya (setelah commit perubahan):
#   bash scripts/demo/deploy.sh ubuntu@IP_SERVER "C:/lokasi/ssh-key.key" nama.duckdns.org
#
# Yang dikirim: kode dari commit terakhir (git archive) + aset hasil build (public/build).
# File .env lokal TIDAK dikirim; hanya kunci Midtrans SANDBOX disalin untuk .env server pertama kali.
# =====================================================================
set -euo pipefail

SERVER="${1:?Pakai: deploy.sh ubuntu@IP KUNCI.key DOMAIN [--pertama EMAIL]}"
KEY="${2:?Lokasi file kunci SSH dari Oracle (.key)}"
DOMAIN="${3:?Subdomain, mis. lingkunggunung.duckdns.org}"
FIRST=false
EMAIL=""
if [ "${4:-}" = "--pertama" ]; then
    FIRST=true
    EMAIL="${5:-}"
fi

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
SSH_OPTS=(-i "$KEY" -o StrictHostKeyChecking=accept-new -o ServerAliveInterval=30)

if [ -n "$(git status --porcelain)" ]; then
    echo "PERHATIAN: ada perubahan yang belum di-commit. Yang dikirim hanya commit terakhir ($(git rev-parse --short HEAD))."
fi

if grep -q '^MIDTRANS_IS_PRODUCTION=true' .env 2>/dev/null; then
    echo "Dibatalkan: .env lokal memakai Midtrans PRODUKSI. Server demo hanya boleh sandbox."
    exit 1
fi

echo "==> Build aset (npm run build)"
npm run build

echo "==> Mengemas commit $(git rev-parse --short HEAD) ($(git branch --show-current))"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
git archive --format=tar -o "$TMP/rilis.tar" HEAD
tar -rf "$TMP/rilis.tar" public/build
gzip -f "$TMP/rilis.tar"
grep -E '^MIDTRANS_(MERCHANT_ID|SERVER_KEY|CLIENT_KEY)=' .env > "$TMP/midtrans.env" 2>/dev/null || : > "$TMP/midtrans.env"
echo "    ukuran paket: $(du -h "$TMP/rilis.tar.gz" | cut -f1)"

echo "==> Mengirim ke $SERVER"
scp "${SSH_OPTS[@]}" "$TMP/rilis.tar.gz" "$TMP/midtrans.env" scripts/demo/setup-server.sh scripts/demo/install-app.sh "$SERVER:/tmp/"

if $FIRST; then
    echo "==> Memasang server (sekali saja, ±10-15 menit)"
    ssh "${SSH_OPTS[@]}" "$SERVER" "sed -i 's/\r\$//' /tmp/setup-server.sh && sudo bash /tmp/setup-server.sh '$DOMAIN' '$EMAIL'"
fi

echo "==> Memasang aplikasi"
ssh "${SSH_OPTS[@]}" "$SERVER" "chmod 600 /tmp/midtrans.env; sed -i 's/\r\$//' /tmp/install-app.sh && sudo bash /tmp/install-app.sh '$DOMAIN'"

echo
echo "Selesai. Buka: https://$DOMAIN (atau http:// bila HTTPS belum aktif)"
