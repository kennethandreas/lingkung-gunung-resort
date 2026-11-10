# Daftar Akses Halaman & Fitur

Siapa yang boleh membuka setiap bagian website. Aturan dicek di **server** (middleware, service, policy), bukan sekadar menyembunyikan tombol.

Ada dua jenis akun yang **terpisah total**:

| | Akun pelanggan | Akun staf |
|---|---|---|
| Tabel / guard | `customer_accounts` / `customer` | `users` / `web` |
| Masuk lewat | `/akun/masuk` | `/kelola/login` (+ 2FA) |
| Bisa membuka admin panel | ❌ Tidak pernah | ✅ Sesuai role (Shield) |

## 1. Publik (tanpa akun)

| Halaman / fitur | Rute |
|---|---|
| Beranda, Stay (daftar & detail akomodasi), Play, Learn, Gather, Taste, Galeri, Rute & Akses, Kontak, halaman info (kebijakan, S&K) | `/`, `/akomodasi`, `/akomodasi/{slug}`, `/play`, `/learn`, `/gather`, `/taste`, `/galeri`, `/rute-dan-akses`, `/kontak`, `/info/{slug}` |
| Wizard booking langkah 1–2: pilih tanggal, jumlah tamu, lihat ketersediaan & harga, pilih akomodasi | `/pesan` |
| Package Builder B2B & proposal (keputusan pemilik proyek: tetap tanpa login, karena link dikirim sales ke instansi) | `/rancang-paket`, `/proposal/{kode}`, `/proposal/{kode}/proposal.pdf` |
| Halaman masuk, daftar, lupa & atur ulang password, tautan verifikasi email (bertanda tangan) | `/akun/masuk`, `/akun/daftar`, `/akun/lupa-password`, `/akun/reset-password/{token}`, `/akun/verifikasi/{id}/{hash}` |
| Ulasan tamu (hanya yang sudah disetujui staf) | `/ulasan`, `/ulasan/{akomodasi\|aktivitas\|paket}/{slug}` |
| Foto ulasan: ulasan tayang = semua orang; belum tayang = hanya pemilik ulasan atau staf berizin `View:Review`, selain itu 404 | `/ulasan/foto/{uuid}/{sm\|md}` |
| Sitemap, webhook Midtrans (diamankan signature, bukan login) | `/sitemap.xml`, `POST /webhook/midtrans` |

## 2. Wajib akun pelanggan (email terverifikasi bila `CUSTOMER_EMAIL_VERIFICATION=true`)

| Fitur | Aturan |
|---|---|
| Wizard booking langkah 3–4 (data pemesan & bayar) | Tamu diarahkan ke `/akun/masuk`; tanggal, jumlah tamu, unit, dan kode promo dibawa kembali lewat URL. Server menolak kirim booking tanpa akun walau langkah dimanipulasi dari browser. Booking otomatis milik akun yang login (dari sesi, bukan input). |
| Dashboard, Booking Saya, detail booking | `/akun`, `/akun/booking`, `/akun/booking/{kode}`. Hanya booking milik akun sendiri; milik orang lain = 404. |
| Tambahkan booking lama | `POST /akun/booking/tautkan`: wajib kode booking + nomor WhatsApp yang cocok, maks. 5 percobaan salah per 10 menit. |
| Akomodasi tersimpan | `/akun/tersimpan`, `POST /akun/tersimpan/{slug}`. Hanya milik sendiri; tidak menyentuh stok. |
| Tulis / ubah / hapus ulasan | `/akun/booking/{kode}/ulasan/{target}`. Booking milik sendiri (lainnya 404), status "Sudah Check-in" dan tanggal check-out sudah tiba; target hanya resort atau akomodasi/aktivitas/paket yang ada di booking itu. Maks. 10 kiriman per 10 menit per akun. |
| Profil & ganti password | `/akun/profil` (boleh sebelum verifikasi, untuk membetulkan email). |
| Status booking, bayar, e-ticket **untuk booking milik akun** | `/booking/{kode}`, `/booking/{kode}/bayar`, `/booking/{kode}/e-ticket.pdf`. Tamu → diminta masuk lalu kembali; akun lain → 404. |

## 3. Link kode acak (tanpa akun)

| Fitur | Alasan |
|---|---|
| `/booking/{kode}` untuk booking **tanpa akun**: walk-in, telepon, B2B (konversi penawaran), dan booking lama | Tamunya tidak punya akun; link dikirim staf lewat email/WhatsApp. Kode 10 karakter acak (bukan ID berurutan). |

## 4. Staf (admin panel `/kelola`, guard `web`, 2FA, Filament Shield)

| Role | Akses |
|---|---|
| Owner | Semua, termasuk pengguna & role |
| Admin | Semua operasional & master data, laporan, konversi penawaran |
| Sales | Lihat semua, kelola booking & pelanggan; tidak ubah harga / hapus / batalkan |
| Front Office | Walk-in, catat pembayaran, check-in, kalender; harga hanya lihat |

Ulasan Tamu (Operasional Harian › Ulasan Tamu): Owner & Admin bisa memoderasi (izin khusus `Moderate:Review`), Sales hanya melihat, Front Office tidak punya akses.

Bukti transfer `/kelola/bukti-transfer/{id}`: hanya staf (`auth:web`) yang punya izin melihat booking tersebut.

## Pesan yang dipakai di seluruh website

| Situasi | Yang terjadi |
|---|---|
| Belum masuk saat membuka fitur akun | Ke `/akun/masuk` + pesan "Silakan masuk terlebih dahulu untuk melanjutkan." Setelah masuk kembali ke halaman tujuan. |
| Email belum diverifikasi (bila diwajibkan) | Ke `/akun/verifikasi` dengan tombol kirim ulang. |
| Membuka data milik orang lain | Halaman 404 "tidak ditemukan" (tidak membocorkan bahwa data ada). |
| Tidak berhak (staf) | Halaman 403. |
| Sesi/halaman kedaluwarsa | Halaman 419 "Sesi berakhir" (Bahasa Indonesia). |
| Terlalu banyak percobaan | Halaman 429 / pesan "Terlalu banyak percobaan…". |
