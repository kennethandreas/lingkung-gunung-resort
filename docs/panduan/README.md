# Panduan website (PDF untuk pemilik resort)

- Hasil: `docs/lingkung-gunung-website-user-manual-id.pdf` (dibuat oleh `scripts/buat-pdf.mjs`, tidak disimpan di repository)
- Sumber yang bisa diedit: `docs/lingkung-gunung-website-user-manual-id.html` (setiap `<section class="page">` = satu halaman A4; nomor halaman & daftar isi dihitung otomatis)
- `gambar/`: tangkapan layar asli dari website lokal, memakai **data contoh fiktif**. Nama, nomor, email, dan kode booking tamu lain disamarkan (••••).
- `aset/`: font (Fraunces, Plus Jakarta Sans; lisensi OFL) dan logo.
- `scripts/`: skrip untuk membuat ulang.

## Membuat ulang (laptop development, bukan server produksi)

Butuh: MySQL menyala, `php artisan serve` (http://localhost:8000), Google Chrome, dan `puppeteer-core` (boleh dipasang di folder lain, arahkan dengan `PUPPETEER_DIR=<folder node_modules>`).

```bash
# 1. Data contoh + akun staf sementara (Owner, 2FA)
PANDUAN_MODE=buat php artisan tinker docs/panduan/scripts/data-contoh.php

# 2. Tangkapan layar (menimpa isi gambar/)
node docs/panduan/scripts/ambil-gambar.mjs

# 3. Hapus lagi data contoh & akun staf sementara
PANDUAN_MODE=hapus php artisan tinker docs/panduan/scripts/data-contoh.php

# 4. PDF + pemeriksaan (isi meluap, gambar rusak, font)
node docs/panduan/scripts/buat-pdf.mjs
```

Catatan: jendela pembayaran Midtrans tidak bisa dipotret otomatis (tidak terbuka di Chrome headless), jadi tidak ada gambarnya di panduan.
Bila fitur website berubah, perbarui teks di file HTML lalu jalankan langkah 4 (dan 1–3 bila tampilannya berubah).
