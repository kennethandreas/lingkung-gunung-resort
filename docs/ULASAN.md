# Sistem Ulasan Tamu

Dibuat 27 Oktober 2026 di branch `fitur-ulasan` (lanjutan `ux-booking-akun`).

## Ringkasnya

1. Tamu yang sudah menginap membuka **Akun › Booking Saya › (booking) › Ulasan Anda**, lalu menulis ulasan berisi bintang 1–5, komentar opsional, dan foto opsional.
2. Ulasan berstatus **Menunggu persetujuan**. Selama status ini, ulasan belum tampil di website dan belum dihitung dalam rata-rata.
3. Staf membuka admin › **Operasional Harian › Ulasan Tamu**, lalu memilih salah satu tindakan:
   - **Setujui & tayangkan**
   - **Tolak**: catatan internal wajib diisi, pesan untuk pelanggan opsional
   - **Sembunyikan**: untuk ulasan yang sudah tayang
4. Ulasan yang sudah tayang muncul di halaman yang sesuai, dan rata-rata serta jumlah ulasan otomatis berubah.

## Yang bisa diulas

| Jenis | Syarat | Tampil di |
|---|---|---|
| Resort (pengalaman menginap secara umum) | Satu per booking | `/ulasan` dan ringkasan di beranda |
| Akomodasi (tipe unit) | Tipe unit yang ada di booking | `/akomodasi/{slug}` (bagian "Ulasan") dan `/ulasan/akomodasi/{slug}` |
| Paket rombongan | Paket dari penawaran B2B yang dikonversi menjadi booking ini | Ringkasan di `/gather` dan `/ulasan/paket/{slug}` |
| Aktivitas | Aktivitas di penawaran B2B tersebut | Ringkasan di tabel `/play` dan `/ulasan/aktivitas/{slug}` |

## Siapa yang boleh mengulas

Semua syarat di bawah ini dicek di server setiap kali ulasan dikirim atau diubah (`App\Services\ReviewService`):

- Pelanggan sudah masuk ke akunnya, dan booking itu milik akunnya. Booking milik orang lain menghasilkan 404.
- Status booking **Sudah Check-in**, dan tanggal check-out sudah tiba.
  - Sistem belum punya status "check-out", jadi ini syarat paling kuat yang tersedia. Lihat `docs/DATA-TODO.md` no. 40.
  - Booking yang **hanya lunas** (tamu tidak datang) tidak bisa diulas.
- Target ulasan harus benar-benar ada di booking itu.
- Satu ulasan per booking per target. Aturan ini dijaga unique index di database, jadi klik ganda atau dua tab tidak menghasilkan ulasan dobel.
- Booking rombongan (B2B) dibuat staf tanpa akun. Klien menautkannya sendiri lewat **Booking Saya › Tambahkan booking lama** (kode booking + nomor WhatsApp), lalu bisa mengulas.

## Rating publik

- Rata-rata, jumlah, dan sebaran bintang dihitung langsung dari ulasan berstatus **Tayang**, sehingga tidak ada angka tersimpan yang bisa basi.
- Ulasan Menunggu, Ditolak, dan Disembunyikan tidak pernah ikut dihitung atau ditampilkan.
- Bila belum ada ulasan tayang, website **tidak menampilkan angka apa pun** (tidak ada "5,0" bawaan).
- Rating akomodasi hanya dari ulasan akomodasi itu. Rating resort ditampilkan terpisah.
- Data terstruktur Google (`aggregateRating`) **sengaja tidak dipasang**: Google tidak menampilkan ulasan yang dikumpulkan bisnis sendiri untuk LocalBusiness, dan bisa menganggapnya pelanggaran.

## Mengubah & menghapus (pelanggan)

- **Ubah:** kelayakan dicek ulang dan ulasan kembali ke Menunggu persetujuan. Bila sebelumnya sudah tayang, ulasan disembunyikan dulu sampai disetujui lagi.
- **Hapus:** ulasan dan semua fotonya terhapus, dan rata-rata langsung ikut berubah.
- Pelanggan tidak bisa mengubah status, nama tampil, booking, atau tanggal tayang. Properti form untuk booking dan target dikunci (`#[Locked]`) dan selalu dicari ulang dari akun yang masuk.

## Foto

- Format JPG, PNG, atau WebP (tanpa SVG/GIF), maks. 4 foto, maks. 5 MB per foto, ukuran 100–8000 px. Batas ini bisa diubah lewat `REVIEW_MAX_PHOTOS`, `REVIEW_PHOTO_MAX_KB`, dan `REVIEW_COMMENT_MAX` di `.env`.
- Jenis file diperiksa dari **isi file** (`getimagesize` + `finfo`), tidak dari nama atau MIME kiriman browser.
- Nama file dibuat acak oleh server; nama asli dari perangkat tamu tidak disimpan.
- File disimpan **privat** di `storage/app/private/ulasan` (disk `ulasan`) dan tidak bisa dibuka langsung lewat URL.
- Yang dikirim ke browser hanya salinan WebP hasil konversi (480 px dan 1200 px), lewat `/ulasan/foto/{uuid}/{ukuran}`:
  - Konversi membuang metadata EXIF, termasuk lokasi GPS.
  - Rute ini memeriksa status ulasan, memakai header `nosniff` dan CSP `sandbox`, dan file asli tidak pernah dikirim.
- Di website, foto diberi label "Foto dari tamu".

## Admin

- Menu **Operasional Harian › Ulasan Tamu**:
  - tab per status, dengan jumlah ulasan yang menunggu;
  - filter jenis, nilai, dan tanggal kirim;
  - pencarian isi ulasan dan nama.
- Dasbor › "Perlu tindakan" menampilkan jumlah ulasan yang menunggu persetujuan.
- Halaman detail menampilkan:
  - nilai, isi, dan foto ulasan;
  - tautan ke halaman publik dan ke booking;
  - akun pelanggan;
  - riwayat moderasi: siapa, kapan, dan catatan internal.
- Hak akses:
  - Owner dan Admin: izin `Moderate:Review`, bisa memoderasi.
  - Sales: hanya melihat.
  - Front Office: tidak punya akses.
  - Izin bisa diubah di Pengaturan › Role & Hak Akses.
- Ulasan tidak bisa dibuat, diubah, atau dihapus dari admin.
- Aktivitas atau paket yang sudah punya ulasan tidak bisa dihapus dari admin; muncul pesan untuk menonaktifkannya saja. Tipe unit memang tidak punya tombol hapus.

## Database

Migration `2026_10_27_100000_create_reviews_table` hanya **menambah** tabel `reviews`, tanpa mengubah data lama. Isinya:
- foreign key sungguhan per jenis target: `unit_type_id`, `activity_id`, `group_package_id`;
- kolom terhitung `target_ref`, dengan unique (`booking_id`, `target_type`, `target_ref`);
- CHECK constraint (MySQL) untuk nilai 1–5, status yang sah, dan kecocokan jenis target dengan kolomnya;
- index untuk halaman publik dan antrean moderasi.

Rollback: `php artisan migrate:rollback --step=1` menghapus tabel ulasan (data ulasan ikut hilang).

Langkah memasang di server:

```bash
php artisan migrate
php artisan db:seed --class=RolesSeeder   # izin ViewAny/View/Moderate:Review untuk role
```

## Batasan yang perlu diketahui

- Belum diuji di hosting sungguhan (InfinityFree/produksi). Unggah foto memakai upload sementara Livewire dan konversi GD WebP; perlu dipastikan `upload_max_filesize`/`post_max_size` PHP ≥ 5 MB dan GD mendukung WebP (dicek `cek-server.php` versi InfinityFree).
- Folder `infinityfree-terbaru/` **belum** berisi fitur ini.
- Belum ada email pemberitahuan ke staf saat ada ulasan baru (cukup penanda di menu dan dasbor) maupun ke pelanggan saat ulasan disetujui/ditolak.
- Pertanyaan bisnis yang terbuka: `docs/DATA-TODO.md` no. 40–44.
