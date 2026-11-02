# Hosting Demo Gratis: Oracle Cloud Always Free + DuckDNS

Untuk demo yang menyala 24 jam selama berminggu-minggu. Biaya: **Rp0** selama memakai sumber daya "Always Free".
Mode demo: Midtrans **sandbox**, email ditulis ke log (tidak terkirim), verifikasi email mati, admin panel tetap wajib 2FA.

Total waktu: ±1 jam (sebagian besar menunggu).

> Ketentuan paket gratis bisa berubah. Cek halaman resmi Oracle Cloud Free Tier sebelum mendaftar.

---

## Langkah 1. Daftar Oracle Cloud (±15 menit)

1. Buka https://www.oracle.com/cloud/free/ → **Start for free**.
2. Isi data. **Home Region**: pilih **Indonesia North (Batam)** bila tersedia (paling dekat dengan tamu, data tersimpan di Indonesia), atau **Singapore**. Region ini **tidak bisa diganti** nanti, dan server gratis hanya bisa dibuat di home region.
3. Verifikasi kartu kredit/debit (hanya verifikasi; tidak ditagih selama memakai Always Free).
4. Tunggu email "Your account is ready", lalu login ke https://cloud.oracle.com.

## Langkah 2. Buat server (VM)

1. Menu ☰ → **Compute → Instances → Create instance**.
2. **Name**: `lingkung-demo`.
3. **Image and shape → Edit**:
   - Image: **Canonical Ubuntu 24.04**.
   - Shape: **Ampere → VM.Standard.A1.Flex**, isi **2 OCPU** dan **12 GB memory** (masih Always Free).
   - Bila muncul "Out of capacity": coba lagi beberapa jam kemudian, atau pilih **VM.Standard.E2.1.Micro** (AMD, 1 GB, juga gratis; lebih lambat tapi cukup untuk demo, skrip otomatis menambah swap).
4. **Add SSH keys**: pilih **Generate a key pair for me** → klik **Save private key**. Simpan file `ssh-key-….key` di tempat aman (mis. `C:\Users\NAMA\.ssh\lingkung-demo.key`). **Tanpa file ini Anda tidak bisa masuk ke server.**
5. Klik **Create**. Tunggu status **Running**, lalu catat **Public IP address** (mis. `152.69.xxx.xxx`).

## Langkah 3. Buka port web (80 & 443) di Oracle

1. Di halaman instance, klik nama **Virtual cloud network** (di bagian *Primary VNIC*) → **Security Lists** → **Default Security List…**
2. **Add Ingress Rules**, isi dua kali:

| Source CIDR | IP Protocol | Destination Port Range |
|---|---|---|
| `0.0.0.0/0` | TCP | `80` |
| `0.0.0.0/0` | TCP | `443` |

(Firewall di dalam server dibuka otomatis oleh skrip.)

## Langkah 4. Subdomain gratis DuckDNS (±3 menit)

1. Buka https://www.duckdns.org → login (Google/GitHub).
2. Isi nama subdomain, mis. `lingkunggunung` → **add domain**.
3. Di kolom **current ip**, isi **Public IP** server dari langkah 2 → **update ip**.

Alamat demo Anda: `lingkunggunung.duckdns.org`. (IP server Oracle tidak berubah selama server tidak dihapus.)

## Langkah 5. Siapkan laptop

1. Pastikan semua perubahan sudah di-commit (skrip hanya mengirim commit terakhir).
2. Pastikan `.env` laptop berisi kunci Midtrans **sandbox** (skrip menolak kunci produksi).
3. Buka **Git Bash** di folder proyek.
4. Coba masuk ke server (ganti IP & lokasi kunci):
   ```bash
   ssh -i "C:/Users/NAMA/.ssh/lingkung-demo.key" ubuntu@152.69.xxx.xxx
   ```
   Ketik `yes` bila ditanya, lalu `exit`. Bila muncul *"UNPROTECTED PRIVATE KEY"*, lihat bagian **Masalah umum**.

## Langkah 6. Pasang otomatis (±15–20 menit)

```bash
bash scripts/demo/deploy.sh ubuntu@152.69.xxx.xxx "C:/Users/NAMA/.ssh/lingkung-demo.key" lingkunggunung.duckdns.org --pertama emailanda@gmail.com
```

Skrip akan: build aset → mengemas kode → mengirim ke server → memasang Nginx, PHP 8.4, MySQL, Composer, HTTPS (Let's Encrypt, email Anda dipakai untuk pemberitahuan sertifikat), cron & queue → membuat database + `.env` demo → data brosur & foto contoh.

Selesai bila muncul `Selesai: https://lingkunggunung.duckdns.org`.

## Langkah 7. Buat akun Owner admin panel

```bash
ssh -t -i "C:/Users/NAMA/.ssh/lingkung-demo.key" ubuntu@152.69.xxx.xxx "cd /var/www/lingkung-gunung && sudo -u www-data php artisan make:filament-user"
```
Isi nama, email, password. Lalu jadikan Owner (ganti EMAIL):
```bash
ssh -t -i "C:/Users/NAMA/.ssh/lingkung-demo.key" ubuntu@152.69.xxx.xxx "cd /var/www/lingkung-gunung && sudo -u www-data php artisan shield:super-admin --panel=admin"
```
Login di `https://lingkunggunung.duckdns.org/kelola` → pasang Google Authenticator saat diminta.

## Langkah 8. Midtrans sandbox (agar webhook masuk)

Dashboard sandbox Midtrans → **Settings → Configuration → Payment Notification URL**:
`https://lingkunggunung.duckdns.org/webhook/midtrans` → Save.
Cek setelah transaksi uji: admin › **Log Webhook Midtrans**.

## Memperbarui website demo

Setelah ada perubahan (dan sudah di-commit):
```bash
bash scripts/demo/deploy.sh ubuntu@152.69.xxx.xxx "C:/Users/NAMA/.ssh/lingkung-demo.key" lingkunggunung.duckdns.org
```
Database, foto unggahan, dan `.env` server tidak terhapus.

---

## Penting: agar server gratis tidak diambil kembali

Oracle dapat **mengambil kembali server Always Free yang menganggur** (pemakaian CPU, jaringan, dan memori sangat rendah selama 7 hari). Website demo dengan pengunjung sedikit berisiko terkena. Pilihan:
- **Paling aman:** upgrade akun ke **Pay As You Go** (menu Billing → Upgrade). Sumber daya Always Free tetap gratis dan server tidak diambil kembali; tagihan hanya muncul bila memakai di luar kuota gratis. Pasang **Budget alert** Rp0/US$1 agar diberi tahu bila ada biaya.
- Atau buka website/admin secara rutin setiap beberapa hari (tidak dijamin cukup).

## Masalah umum

| Gejala | Solusi |
|---|---|
| `UNPROTECTED PRIVATE KEY` / `bad permissions` saat ssh | Pakai **PowerShell** (bukan Git Bash) sekali: `icacls "C:\Users\NAMA\.ssh\lingkung-demo.key" /inheritance:r /grant:r "$($env:USERNAME):R"`. Lalu ulangi dari Git Bash. |
| Website tidak terbuka | Cek langkah 3 (port 80/443) dan DuckDNS sudah berisi IP server. |
| `HTTPS dilewati` saat pemasangan | DuckDNS belum mengarah ke IP server. Setelah benar: `ssh … "sudo certbot --nginx -d lingkunggunung.duckdns.org -m EMAIL --agree-tos --redirect"`, lalu jalankan deploy (tanpa `--pertama`). |
| Ingin melihat log | `ssh … "tail -n 50 /var/www/lingkung-gunung/storage/logs/laravel-$(date +%F).log"` |
| Email e-ticket / verifikasi | Mode demo menulis email ke log (lihat baris di atas), tidak terkirim. |
| Server lambat (shape Micro 1 GB) | Normal untuk shape kecil; coba lagi membuat shape A1 bila kapasitas tersedia. |

## Catatan keamanan

- Server demo memakai Midtrans sandbox: tidak ada uang sungguhan.
- Password database dibuat acak dan disimpan di server (`/root/lingkung-db.env`), tidak pernah dikirim balik ke laptop.
- `.env` laptop tidak dikirim; hanya 3 kunci Midtrans sandbox disalin untuk `.env` server pertama kali.
- Demo ini **bukan** production: sebelum go-live sungguhan ikuti `docs/DEPLOY.md` (domain resmi, email asli, Midtrans produksi, backup ke penyimpanan terpisah).
