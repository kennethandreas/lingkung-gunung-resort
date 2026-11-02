# Audit UX & Arah Desain Baru (Oktober 2026)

Audit dari sudut pandang **pengunjung pertama kali** atas website hasil tahap "transformasi premium", disertai keputusan desain untuk tahap `ux-redesign`.
Referensi prinsip (tidak meniru aset atau merek): Fairmont Hotels & Resorts dan Fairmont Jakarta.

## 1. Masalah yang ditemukan

| No | Masalah | Bukti di website lama | Dampak |
|---|---|---|---|
| 1 | **Label menu berbahasa Inggris & abstrak** (Stay, Play, Learn, Gather, Taste) | Header & footer | Pengunjung baru harus menebak: "Gather" itu apa? Paket rombongan ada di mana? |
| 2 | Judul halaman campur Inggris ("Turn nature into adventure", "Where people connect", "The Ecosystem of Experience") | Hero Play/Gather, beranda | Terasa seperti template; tidak menjelaskan isi halaman |
| 3 | **Hero beranda tidak menjelaskan apa tempat ini**: judul puitis "Bangun di antara kabut, menatap Puncak Salak." | Beranda | Pengunjung tidak langsung tahu ini resort + camping + tempat acara rombongan |
| 4 | **Ajakan bersaing**: hero punya 2 tombol + kotak booking + tombol header + bar HP | Beranda | Tidak jelas langkah utama |
| 5 | Beranda terlalu panjang: 9 seksi bernomor dengan pola sama (label nomor → judul besar → paragraf → tombol pil) | Beranda | Info penting (harga, lokasi) tenggelam; terasa repetitif |
| 6 | **Sudut membulat & pil di mana-mana** (83× `rounded-full`, 74× `rounded-kartu`) + bayangan + gradien + blob kabut + motif kontur | Semua komponen | Ciri khas "template AI/SaaS", bukan resort |
| 7 | Huruf tulisan tangan (Kaushan Script) dipakai 17× sebagai eyebrow | Hero, seksi | Dekoratif berlebihan, keterbacaan rendah |
| 8 | Efek kursor terus-menerus (sorotan, kabut ikut kursor), Ken Burns, pita parallax | Beranda, kartu | Gerak tanpa fungsi |
| 9 | Menu dropdown bergambar 6 panel penuh layar | Header desktop | Berat untuk sekadar memilih halaman |
| 10 | Kartu akomodasi identik dalam grid 3 kolom; harga & kapasitas kecil di bawah | /akomodasi, beranda | Sulit membandingkan sekilas |
| 11 | Keharusan akun untuk memesan baru diketahui di langkah 3 wizard | /pesan | Kejutan di tengah alur → tamu kabur |
| 12 | Lokasi & kontak tidak punya tempat jelas di beranda (hanya footer) | Beranda | Pertanyaan paling umum ("di mana?") sulit dijawab |
| 13 | Admin: dasbor penuh angka & 2 grafik; menu kelompok kurang mencerminkan pekerjaan harian; komponen bulat ala SaaS | /kelola | Staf front office harus mencari hal penting |

## 2. Keputusan desain

**Bahasa visual "arsitektural"**
- Sudut 0–2 px untuk semua komponen: tombol, gambar, field, dropdown, panel, dan tabel.
- Garis tipis (hairline) sebagai pemisah, bukan bayangan. Bayangan hanya untuk lapisan yang mengambang (dropdown, dialog).
- Tidak ada gradien dekoratif, blob, motif kontur, atau huruf tulisan tangan di antarmuka.
- Tipografi: Fraunces dengan sumbu `SOFT 0` (lebih tegas) dan bobot normal untuk judul editorial. Plus Jakarta Sans untuk isi. Label kecil berhuruf kapital dengan jarak huruf lebar untuk eyebrow, tombol, dan navigasi.
- Warna dibatasi: hijau hutan (utama), kertas hangat (latar), arang (teks). Emas dipakai sangat hemat sebagai garis aksen.

**Arsitektur informasi**
- Menu: **Menginap · Acara & Rombongan · Aktivitas · Kuliner · Galeri · Lokasi & Kontak**, plus tombol **Pesan**.
- Dropdown sederhana berupa teks, hanya untuk Menginap, Acara, dan Aktivitas.
- Beranda dengan alur yang disengaja:
  1. Hero (judul yang menjelaskan tempat) dan kotak booking menempel di bawahnya
  2. Pilih tujuan kunjungan (keluarga / rombongan / aktivitas)
  3. Daftar akomodasi yang bisa dibandingkan (foto, kapasitas, tempat tidur, sarapan, harga mulai)
  4. Acara & rombongan
  5. Aktivitas (deret foto dengan harga)
  6. Kuliner
  7. Lokasi & kontak
- Galeri, artikel, dan FAQ dikeluarkan dari beranda. Ketiganya tetap bisa dicapai dari menu dan footer.
- /akomodasi dikelompokkan: **Kamar & glamping**, **Tenda**, **Area camping & campervan**, plus tabel perbandingan.
- Wizard memberi tahu sejak langkah pertama bahwa akun dibutuhkan untuk menyelesaikan pesanan.

**Gerak**
- Hanya untuk orientasi: fade/slide halus saat seksi masuk, zoom pelan foto saat hover, transisi menu, dan transisi antarhalaman.
- Efek kursor terus-menerus, Ken Burns, dan pita parallax dihapus. Semua animasi tetap menghormati `prefers-reduced-motion`.

**Admin**
- Komponen bersudut tajam. Menu dikelompokkan menurut pekerjaan: **Operasional Harian · Akomodasi & Harga · Paket Rombongan · Website · Laporan & Pengaturan**.
- Dasbor = Perlu tindakan → Hari ini → Kedatangan → Uang (3 angka saja). Grafik dipindah ke halaman Laporan.

## 3. Tahap lanjutan: alur pemesanan & akun pelanggan (branch `ux-booking-akun`)

| No | Masalah yang ditemukan | Perbaikan |
|---|---|---|
| 14 | Tidak ada tinjauan pesanan sebelum menekan tombol bayar | Bagian **"Periksa pesanan Anda"** di langkah 3: tanggal, akomodasi, total, jumlah yang dibayar sekarang, lama unit ditahan, dan kapan e-ticket terbit |
| 15 | Di HP rincian harga tidak bisa dilihat, hanya total | Bar bawah HP bisa dibuka untuk melihat rincian per malam. Di langkah 3 bar menampilkan jumlah yang dibayar sekarang |
| 16 | Tombol HP "Bayar" menyesatkan (sebenarnya membuat pesanan & menahan unit) | Diganti "Lanjut bayar"; tombol desktop "Buat pesanan & lanjut ke pembayaran" |
| 17 | Pemberitahuan akun muncul dua kali | Satu pemberitahuan di langkah 1–2 untuk tamu; tombol langkah 2 berbunyi "Lanjut: masuk & isi data" |
| 18 | Pesan "pilihan Anda tersimpan" hilang saat pindah Masuk → Daftar (flash sekali pakai) | Panel **"Pesanan Anda menunggu"** di halaman masuk/daftar/verifikasi, dibaca dari URL lanjutan di sesi (`App\Support\PendingBooking`) |
| 19 | Pengunjung baru di halaman Masuk hanya melihat tautan kecil "Daftar" | Tombol "Buat akun baru (gratis)" di dalam kartu masuk |
| 20 | Error di langkah 3 tidak terlihat bila pengguna berada di bawah halaman | Pesan error digulir ke tengah layar; kolom yang salah difokuskan & digulir otomatis |
| 21 | Popup Midtrans ditutup tanpa umpan balik | Pesan "Jendela pembayaran ditutup" + tombol "Coba bayar lagi" (sisa waktu hold disebutkan) |
| 22 | Tidak ada gambaran "apa langkah berikutnya" setelah memesan | Komponen `<x-booking.langkah>`: Pesanan dibuat → Bayar DP/lunas → Pelunasan (batas tanggal) → Check-in, di halaman status, dasbor & detail akun |
| 23 | Daftar booking akun tidak menunjukkan booking mana yang harus dibayar | Baris booking berbingkai emas + "Perlu tindakan: bayar DP Rp… sebelum …"; dasbor menaruh semua tagihan di bagian paling atas |
| 24 | Teks "Bayar lunas: e-ticket langsung terbit" keliru (e-ticket sudah terbit setelah DP) | Diganti "Sekali bayar, tanpa tagihan pelunasan" + keterangan e-ticket terbit setelah pembayaran pertama |
| 25 | Bug: hold sudah lewat tapi status belum diperbarui penjadwal → halaman masih menyarankan "Bayar" sebagai langkah | Diperlakukan sama seperti kedaluwarsa (pesan "Waktu pembayaran habis" + pesan ulang) |

Logika server (harga, stok, hold, Midtrans, akses booking) **tidak diubah**. Perubahan backend hanya data tampilan: `actionNeeded` di `Akun\DashboardController` dan helper `PendingBooking`.
