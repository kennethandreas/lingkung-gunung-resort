# Uji Coba oleh Staf Resort (UAT)

Lakukan di **website staging** (bukan website asli) sebelum go-live. Pembayaran memakai **mode uji Midtrans**: tidak ada uang sungguhan yang berpindah.

Cara mengisi: centang kolom **Lulus** bila hasilnya sesuai. Bila tidak sesuai, tulis apa yang terjadi di kolom **Catatan** (sertakan screenshot bila bisa).

Penguji: ____________________ Tanggal: ____________ Perangkat: HP / Laptop

---

## A. Tamu memesan sendiri (pakai HP)

| No | Langkah | Hasil yang diharapkan | Lulus | Catatan |
|---|---|---|---|---|
| A1 | Buka website, lihat beranda | Foto, harga, dan tombol "Cek Ketersediaan" tampil rapi di HP | ☐ | |
| A2 | Klik **Cek Ketersediaan**, pilih tanggal Jumat–Minggu, 3 tamu | Muncul daftar akomodasi dengan harga 2 malam (tarif weekend) | ☐ | |
| A3 | Pilih 1 Glamping, klik **Lanjut** (belum masuk akun) | Diminta masuk/daftar; setelah masuk kembali ke langkah Data pemesan dengan pilihan yang sama | ☐ | |
| A3b | Isi data diri, pilih **DP 25%**, klik Bayar | Popup pembayaran Midtrans muncul, ada hitung mundur 60 menit | ☐ | |
| A4 | Bayar dengan kartu uji `4811 1111 1111 1114` (CVV 123, OTP 112233) | Status berubah **DP Dibayar**, QR e-ticket muncul | ☐ | |
| A5 | Cek email yang dipakai | Email e-ticket dengan PDF terlampir masuk | ☐ | |
| A6 | Buka lagi link booking, klik **Bayar Pelunasan** | Tagihan = sisa 75%; setelah bayar status **Lunas** | ☐ | |
| A7 | Pesan lagi tetapi jangan bayar, tunggu 60 menit | Status **Kedaluwarsa**, unit kembali tersedia | ☐ | |
| A8 | Coba isi nomor HP asal (mis. "123") | Muncul pesan ramah: nomor belum benar | ☐ | |

## B. Klien rombongan (Package Builder)

| No | Langkah | Hasil yang diharapkan | Lulus | Catatan |
|---|---|---|---|---|
| B1 | Menu **Gather** → **Rancang paket** | Daftar paket LDKS / Camp / Room Glamping dengan harga WD/WE | ☐ | |
| B2 | Pilih LDKS (C), isi 50 peserta | Muncul pesan "minimal 100 peserta" | ☐ | |
| B3 | Pilih paket lain yang sesuai, pilih menu tiap hari, tambah aktivitas | Estimasi harga ikut berubah | ☐ | |
| B4 | Isi data instansi, klik **Buat proposal** | Halaman proposal + PDF; email proposal masuk | ☐ | |
| B5 | Klik **Kirim ke tim sales via WhatsApp** | WhatsApp terbuka berisi nomor penawaran | ☐ | |

## C. Admin & Sales (admin panel)

| No | Langkah | Hasil yang diharapkan | Lulus | Catatan |
|---|---|---|---|---|
| C1 | Login pertama kali | Diminta memasang Google Authenticator, simpan kode pemulihan | ☐ | |
| C2 | Salah password 6× | Diminta menunggu (login dibatasi) | ☐ | |
| C3 | **Penawaran B2B** → buka proposal dari B4 → **Chat klien** | WhatsApp ke klien terbuka; status jadi "Dihubungi" | ☐ | |
| C4 | Ubah harga salah satu item penawaran | Total berubah, PDF ikut diperbarui | ☐ | |
| C5 | (Admin) **Konversi ke booking**, alokasikan unit | Booking B2B terbentuk; stok di Kalender Okupansi berkurang | ☐ | |
| C6 | **Kirim link pembayaran** | WhatsApp berisi link pembayaran DP | ☐ | |
| C7 | **Booking → Input reservasi manual** (tamu telepon) | Booking terbentuk dengan harga otomatis | ☐ | |
| C8 | **Catat pembayaran** transfer + unggah foto bukti | Status berubah; bukti bisa dibuka lagi | ☐ | |
| C9 | **Kalender Okupansi** → blokir 1 tanggal untuk perawatan | Tanggal itu tidak bisa dipesan di website | ☐ | |
| C10 | **Laporan** bulan ini → **Ekspor Excel** | File Excel berisi Ringkasan, Pembayaran, Booking, Okupansi | ☐ | |
| C11 | **Tipe Unit & Harga** → ubah harga weekend | Harga baru tampil di website & wizard | ☐ | |
| C12 | **Konten → Galeri** → unggah foto | Foto tampil di galeri website | ☐ | |

## D. Front Office (hari kedatangan)

| No | Langkah | Hasil yang diharapkan | Lulus | Catatan |
|---|---|---|---|---|
| D1 | Login sebagai Front Office | Tidak ada menu ubah harga, pengguna, laporan | ☐ | |
| D2 | Pindai QR e-ticket tamu dengan kamera HP | Halaman booking terbuka di admin | ☐ | |
| D3 | Klik **Check-in**, pilih nomor unit | Status **Sudah Check-in**, unit tercatat | ☐ | |
| D4 | Dashboard | Kedatangan hari ini & 7 hari ke depan tampil | ☐ | |

## E. Akun pelanggan

| No | Langkah | Hasil yang diharapkan | Lulus | Catatan |
|---|---|---|---|---|
| E1 | **Masuk → Daftar gratis**, isi nama, email, password | Diarahkan ke halaman "Satu langkah lagi"; email verifikasi masuk (staging/produksi dengan `CUSTOMER_EMAIL_VERIFICATION=true`) | ☐ | |
| E2 | Klik tombol di email verifikasi | Masuk ke Dashboard akun | ☐ | |
| E3 | Daftar lagi dengan email yang sama | Muncul pesan "Email ini sudah terdaftar" | ☐ | |
| E4 | Ketuk ikon ♡ di kartu akomodasi, lalu buka **Akun Saya → Tersimpan** | Akomodasi muncul; tombol Hapus berfungsi | ☐ | |
| E5 | Saat sudah masuk, pesan lewat **Cek Ketersediaan** | Data diri terisi otomatis; booking muncul di **Booking Saya** | ☐ | |
| E6 | **Booking Saya → Tambahkan booking lama**: kode booking + nomor WhatsApp yang dipakai | Booking lama masuk ke akun; nomor salah ditolak | ☐ | |
| E7 | Keluar, lalu **Lupa password?** | Email atur ulang masuk; password baru bisa dipakai masuk | ☐ | |
| E8 | **Profil Saya**: ganti nama, nomor, password | Tersimpan; ganti email meminta verifikasi ulang | ☐ | |

## F. Kesan umum

- Apakah ada teks yang salah, membingungkan, atau harga yang tidak sesuai brosur?
- Apakah ada langkah yang terasa lambat di HP?
- Saran perbaikan:

_____________________________________________________________________
