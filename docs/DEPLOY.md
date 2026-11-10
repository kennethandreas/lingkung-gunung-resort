# Panduan Deploy (Staging & Produksi)

Panduan ini dipakai di F4 (staging) dan F5 (go-live). Ikuti berurutan. Setiap perintah dijalankan di terminal server (SSH) dari folder proyek.

## 1. Syarat hosting (dokumen teknis 10.1)

- PHP **8.4** dengan ekstensi: `mbstring`, `intl`, `gd`, `pdo_mysql`, `zip`, `bcmath`, `fileinfo`, `openssl`, `sodium`, `exif`
- MySQL **8.4** (atau 8.0+), plus program `mysqldump` (untuk backup)
- Akses **SSH**, **Composer 2**, **Git**
- **Cron** tiap menit
- Domain bisa diarahkan ke **Cloudflare**
- Node.js tidak wajib di server bila aset di-build di laptop/CI lalu diunggah. Lebih sederhana bila Node 22.12+ tersedia di server.

Cek cepat di server:

```bash
php -v && php -m | grep -E "intl|gd|pdo_mysql|zip|sodium|exif"
mysql --version && mysqldump --version
composer -V && git --version
```

## 2. Ambil kode

```bash
git clone git@github.com:AKUN/lingkung-gunung.git lingkung-gunung
cd lingkung-gunung
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # atau unggah folder public/build hasil build dari laptop
```

Arahkan **document root** domain ke folder `public/` (bukan folder proyek).

## 3. Database & user tanpa hak DROP/GRANT

Dokumen teknis bab 7: *user database aplikasi tidak punya hak DROP / GRANT*. Pakai **dua user**:

| User | Dipakai untuk | Hak |
|---|---|---|
| `lingkung_migrate` | Hanya saat deploy (`php artisan migrate`) | Mengubah struktur tabel |
| `lingkung_app` | Aplikasi sehari-hari (isi `.env`) | Baca/tulis data + backup, **tanpa DROP/GRANT** |

Jalankan sebagai root MySQL (ganti password):

```sql
CREATE DATABASE lingkung_gunung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'lingkung_migrate'@'localhost' IDENTIFIED BY 'PASSWORD_MIGRATE_PANJANG';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
    ON lingkung_gunung.* TO 'lingkung_migrate'@'localhost';

CREATE USER 'lingkung_app'@'localhost' IDENTIFIED BY 'PASSWORD_APP_PANJANG';
GRANT SELECT, INSERT, UPDATE, DELETE, SHOW VIEW, TRIGGER, LOCK TABLES
    ON lingkung_gunung.* TO 'lingkung_app'@'localhost';

FLUSH PRIVILEGES;
```

Di shared hosting (cPanel), buat kedua user lewat menu **MySQL Databases** dan centang hanya hak di atas.

## 3b. PHP server

Di `php.ini` server (atau menu *Select PHP Version → Options* di cPanel): `expose_php = Off`, `display_errors = Off`. Dicek oleh `resort:cek-produksi`.

## 4. File `.env`

```bash
cp .env.production.example .env
nano .env                         # isi semua nilai yang kosong
php artisan key:generate          # HANYA saat pemasangan pertama
```

Kunci yang wajib diisi: `APP_URL`, `DB_*` (user **lingkung_app**), `MAIL_*`, `MIDTRANS_*`, `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_NOTIFICATION_EMAIL`.

Set `CUSTOMER_EMAIL_VERIFICATION=true` (di laptop sengaja `false`). `MAIL_*` wajib berfungsi sebelum go-live: selain e-ticket, akun pelanggan membutuhkan email verifikasi dan email atur ulang password (dikirim lewat antrean, jadi cron/queue worker di bagian 6 harus jalan). `APP_URL` harus alamat asli (https), karena tautan di email dibuat dari nilai ini.

> Simpan `APP_KEY` dan `BACKUP_ARCHIVE_PASSWORD` di pengelola password resort. Tanpa keduanya, data terenkripsi (2FA staf, backup) tidak bisa dipulihkan.

## 5. Pasang aplikasi

```bash
# struktur tabel memakai user migrate (sementara, tidak disimpan di .env)
DB_USERNAME=lingkung_migrate DB_PASSWORD='PASSWORD_MIGRATE_PANJANG' php artisan migrate --force

php artisan db:seed --force                  # data brosur, role, konten awal
php artisan storage:link
php artisan inventory:generate
php artisan optimize                         # cache config, route, view
php artisan filament:optimize

# akun Owner pertama
php artisan make:filament-user
php artisan shield:super-admin --panel=admin

# pemeriksaan akhir: harus tanpa butir GAGAL
php artisan resort:cek-produksi
```

Jangan jalankan `PlaceholderMediaSeeder` di produksi bila foto asli sudah siap; unggah foto asli lewat admin panel.

## 6. Cron (wajib)

Satu baris saja (crontab -e, atau menu **Cron Jobs** di cPanel):

```
* * * * * cd /home/USER/lingkung-gunung && php artisan schedule:run >> /dev/null 2>&1
```

Cron ini menjalankan: lepas hold 60 menit, rekonsiliasi pembayaran Midtrans tiap 5 menit (cadangan bila webhook gagal), pengingat pelunasan, stok harian, backup harian 01:30, dan pembersihan data lama. Bila hosting **tidak** bisa menjalankan `php artisan queue:work` terus-menerus, set `QUEUE_VIA_SCHEDULER=true` agar email diproses lewat cron yang sama. Bila hosting mendukung *supervisor*, jalankan `php artisan queue:work --tries=3` sebagai layanan dan set `QUEUE_VIA_SCHEDULER=false`.

## 7. Backup ke penyimpanan terpisah

Backup harian sudah terenkripsi AES-256, tetapi defaultnya disimpan di server yang sama (`storage/app/backups`). Dokumen teknis meminta penyimpanan **terpisah**. Opsi gratis: **Cloudflare R2** (10 GB gratis).

1. Buat bucket R2 + API token (akses tulis bucket itu saja).
2. `composer require league/flysystem-aws-s3-v3`
3. Tambahkan disk di `config/filesystems.php`:
   ```php
   'r2' => [
       'driver' => 's3',
       'key' => env('R2_ACCESS_KEY_ID'),
       'secret' => env('R2_SECRET_ACCESS_KEY'),
       'region' => 'auto',
       'bucket' => env('R2_BUCKET'),
       'endpoint' => env('R2_ENDPOINT'),
       'use_path_style_endpoint' => true,
       'throw' => true,
   ],
   ```
4. `.env`: `BACKUP_DISKS=backups,r2` lalu isi `R2_*`.
5. Uji: `php artisan backup:run` lalu `php artisan backup:list`.

### Uji pemulihan (minimal sebulan sekali)

1. Unduh zip backup terbaru (`backup:list` menunjukkan lokasinya).
2. Buka dengan 7-Zip memakai `BACKUP_ARCHIVE_PASSWORD`.
3. Impor `db-dumps/mysql-lingkung_gunung.sql` ke database **uji** (bukan produksi):
   `mysql -u root lingkung_gunung_restore_test < mysql-lingkung_gunung.sql`
4. Periksa jumlah booking & pembayaran terbaru sama dengan produksi. Catat tanggal uji.

## 8. Cloudflare

1. Tambahkan domain di Cloudflare (gratis), ganti nameserver di registrar.
2. **SSL/TLS → Full (strict)**, aktifkan **Always Use HTTPS**.
3. Pasang sertifikat di server (Let's Encrypt dari hosting atau *Cloudflare Origin Certificate*).
4. `.env`: `TRUSTED_PROXIES=*` (server hanya diakses lewat Cloudflare).
5. Jangan meng-cache halaman `/booking/*`, `/proposal/*`, `/pesan`, `/rancang-paket`, `/akun*`, `/kelola*`, `/webhook/*` (aturan *Cache Rules → Bypass*). Halaman beranda & akomodasi juga jangan di-cache sebagai HTML statis, karena isinya berbeda untuk pelanggan yang login (tombol "Akun Saya" & ikon simpan). Aset `/build/*` dan `/storage/*` boleh di-cache lama.

## 9. Midtrans

- Staging: tetap kunci **sandbox** (`MIDTRANS_IS_PRODUCTION=false`).
- Produksi (F5): kunci produksi + `MIDTRANS_IS_PRODUCTION=true`.
- Dashboard Midtrans → **Settings → Configuration → Payment Notification URL**: `https://domain-resort.id/webhook/midtrans`
- Uji transaksi nyata nominal kecil, lalu refund manual (checklist rilis 10.2).

## 10. Setelah deploy / update kode

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
DB_USERNAME=lingkung_migrate DB_PASSWORD='...' php artisan migrate --force
php artisan optimize && php artisan filament:optimize
php artisan queue:restart
php artisan resort:cek-produksi
```

## 11. Pemantauan rutin

| Kapan | Apa |
|---|---|
| Harian (otomatis) | Backup 01:30, email bila gagal (`BACKUP_NOTIFICATION_EMAIL`) |
| Mingguan | Admin › Log Webhook Midtrans: cari "PERLU TINDAKAN" atau signature tidak valid |
| Bulanan | `composer audit` & update keamanan Laravel/Filament/Livewire; uji pemulihan backup |
| Setelah go-live | Google Search Console › *Core Web Vitals* (data nyata pengunjung HP) |
