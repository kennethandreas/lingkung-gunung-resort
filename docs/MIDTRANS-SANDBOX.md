# Panduan Mencoba Pembayaran Midtrans (Sandbox)

Sandbox adalah mode uji coba Midtrans: gratis, dan **tidak ada uang sungguhan yang berpindah**.

## 1. Buat akun sandbox (sekali saja)

1. Buka https://dashboard.sandbox.midtrans.com dan daftar (gratis).
2. Setelah login, pastikan di pojok kiri atas tertulis **Sandbox**.
3. Buka **Settings → Access Keys**. Salin tiga nilai berikut:
   - **Merchant ID**
   - **Client Key** (diawali `Mid-client-`; akun lama: `SB-Mid-client-`)
   - **Server Key** (diawali `Mid-server-`; akun lama: `SB-Mid-server-`)

> Server Key itu rahasia. Jangan dikirim ke siapa pun, jangan ditaruh di kode, dan jangan di-commit ke Git.

## 2. Isi file `.env`

Buka file `.env` di folder proyek, lalu isi:

```
MIDTRANS_MERCHANT_ID=M123456789
MIDTRANS_SERVER_KEY=Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

Simpan, lalu jalankan di terminal:

```
php artisan config:clear
```

Lalu **matikan dan nyalakan ulang server** (Ctrl+C di jendela `composer run dev`, lalu jalankan lagi), supaya kunci baru terbaca.

### Popup tidak muncul?

| Gejala | Penyebab & solusi |
|---|---|
| Halaman booking menulis "Pembayaran online belum aktif" | Server masih membaca `.env` lama. Matikan **semua** jendela server (Ctrl+C), buka terminal baru, jalankan `composer run dev` lagi. Di Windows, dua server bisa sama-sama berjalan di port 8000 dan yang lama tetap menjawab. Cek dengan `netstat -ano \| findstr :8000`: harus hanya ada satu baris LISTENING. |
| Muncul "Pembayaran online sedang mengalami gangguan" | Lihat `storage/logs/laravel.log`. Bila tertulis `cURL error 60: SSL certificate`, PHP belum punya daftar sertifikat: `scoop install cacert`, lalu tambahkan `curl.cainfo` dan `openssl.cafile` (berisi lokasi `cacert.pem`) di file `lingkung-gunung.ini`. Bila tertulis `401`, kunci salah atau tertukar antara sandbox dan produksi. |

## 3. Coba alur pemesanan

1. Pastikan `mysqld --console` dan `composer run dev` berjalan.
2. Buka http://localhost:8000, klik **Cek Ketersediaan**, pilih tanggal dan akomodasi, lalu isi data pemesan (pakai email Anda sendiri).
3. Klik **Lanjut ke pembayaran**. Popup Midtrans akan muncul.
4. Pilih salah satu metode uji coba:

| Metode | Cara membayar di sandbox |
|---|---|
| **QRIS / GoPay** | Salin URL gambar QR, buka https://simulator.sandbox.midtrans.com, pilih QRIS, tempel URL-nya, lalu klik *Pay* |
| **Virtual Account** (BCA, BNI, BRI, dll.) | Salin nomor VA, buka https://simulator.sandbox.midtrans.com, pilih bank yang sama, masukkan nomor VA, lalu bayar |
| **Kartu kredit** | Nomor `4811 1111 1111 1114`, CVV `123`, tanggal kedaluwarsa bebas (asal di masa depan), OTP `112233` |

5. Setelah membayar, kembali ke halaman booking. Halaman ini otomatis menanyakan status ke Midtrans. Status berubah menjadi **DP Dibayar** atau **Lunas**, dan QR e-ticket muncul.

## 4. Melihat email e-ticket saat development

Selama `MAIL_MAILER=log` di `.env`, email **tidak benar-benar dikirim**. Isinya ditulis ke `storage/logs/laravel.log`, dengan PDF e-ticket dalam bentuk lampiran yang di-encode.

- Email dikirim lewat antrean (queue). `composer run dev` sudah menjalankan queue worker secara otomatis. Kalau memakai `php artisan serve` saja, jalankan juga `php artisan queue:work`.
- Email sungguhan (Brevo atau Resend free tier) diatur saat go-live (F5).

## 5. Webhook (Notification URL) untuk staging dan produksi

Midtrans mengirim notifikasi pembayaran ke server kita. Di laptop (`localhost`), Midtrans tidak bisa menjangkau server, jadi website memakai cara cadangan, yaitu cek status langsung (langkah 3 nomor 5). Di server online:

1. Dashboard Midtrans → **Settings → Configuration**
2. **Payment Notification URL**: `https://domain-anda/webhook/midtrans`
3. Simpan.

Setiap notifikasi tercatat di admin panel, menu **Pengaturan → Log Webhook Midtrans**. Notifikasi dengan signature palsu otomatis ditolak.

(Opsional, untuk menguji webhook dari laptop: jalankan `cloudflared tunnel --url http://localhost:8000` dari Cloudflare, yang gratis, lalu pakai URL `https://….trycloudflare.com/webhook/midtrans` sebagai Notification URL.)

## 6. Pindah ke produksi (F5, dikerjakan bersama)

Butuh akun Midtrans produksi yang sudah disetujui (dokumen PT dan rekening Mandiri resort). Lalu ganti kunci di `.env` server dengan kunci produksi dan set `MIDTRANS_IS_PRODUCTION=true`.
