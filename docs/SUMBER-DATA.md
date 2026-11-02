# Sumber Data Resmi & Perbandingan

Dokumen ini mencatat dari mana setiap informasi di website berasal, sumber mana yang dipakai bila berbeda, dan hal yang masih perlu dikonfirmasi ke resort.
Dicek ulang: **25 Oktober 2026**. Daftar pertanyaan lengkap untuk resort ada di `docs/DATA-TODO.md`.

## 1. Sumber yang diperiksa

| Kode | Sumber | Tanggal / masa berlaku | Catatan |
|---|---|---|---|
| **B26** | `docs/BROSUR ELJI 2026.pdf` (30 halaman, hasil scan, dibaca per halaman sebagai gambar) | Judul "2026"; tidak ada tanggal terbit atau masa berlaku tertulis di dalamnya | Sumber utama harga, paket group, menu A–E, aktivitas, item tambahan, mekanisme pembayaran |
| **WEB** | https://elji.id/ (beranda, `/room-elji-cottage/`, `/deluxe-room/`, `/junior-suite-room/`, `/artikel/`, 5 halaman promo, artikel "Tempat Camping Outbound") | Artikel 20 Sep 2024 – 19 Mar 2025; footer "© 2023" | Situs resmi yang aktif. Domain di brosur (`www.eji.id`) tidak berfungsi |
| **PRM** | Gambar promo di elji.id (`5_20241029…png` Glamping, `4_20241029…png` Pondok Sunset, `1_20241029…png` Camping Ceria, `Paket_20241029…jpg` Camp Site Jabon, `PHOTO-2025-01-24…jpg` Campervan, `promo-camp-cer.jpeg`) | Nama file Okt 2024 & Jan 2025; tanpa masa berlaku | **Lebih lama dari B26**, jadi tidak ditampilkan sebagai promo aktif |
| **DOK** | `docs/Perencanaan Teknis Website Lingkung Gunung.pdf` | – | Nama pemasaran unit & aturan sistem |

Media sosial resmi (ditautkan dari elji.id): Instagram `@lingkunggunung`, Facebook `LingkungGunungBogor`, YouTube `@lingkunggunungadventurecam7274`. Isinya tidak dipakai sebagai sumber harga.

## 2. Aturan memilih sumber

1. **Harga, paket, menu, aktivitas, item tambahan, pembayaran: B26** (paling baru, berlabel 2026).
2. **WEB** dipakai untuk hal yang **tidak ada** di B26 dan tidak bertentangan: email, situs, media sosial, kode pos, luas kamar, ukuran TV.
3. **PRM** (2024–Jan 2025) **tidak** dipakai untuk harga dan tidak ditampilkan sebagai promo, karena tidak bermasa berlaku dan sudah digantikan B26.
4. Bila dua sumber bertentangan dan tidak jelas mana yang benar: dipakai nilai B26, lalu dicatat di bagian 4 dan di `DATA-TODO.md`.

## 3. Data yang ditambahkan / diperbaiki di tahap ini

| Data | Nilai di website | Sumber | Tempat mengubah di admin |
|---|---|---|---|
| Email resmi | marketing@elji.id | WEB (beranda, footer, semua artikel) | Pengaturan › Pengaturan Umum |
| Situs resmi | https://elji.id | WEB | Pengaturan Umum |
| Facebook / YouTube | LingkungGunungBogor / @lingkunggunungadventurecam7274 | WEB (footer) | Pengaturan Umum |
| Alamat lengkap | Jalan Akses Lingkung Gunung No.01, Kp. Baru, Cimande, Kec. Caringin, Kabupaten Bogor, Jawa Barat 16730 | B26 hal. 23–29 (No.01, Kp. Baru) + WEB (kecamatan, kode pos) | Pengaturan Umum |
| Lokasi | "Berbatasan langsung dengan Taman Nasional Gunung Gede Pangrango, panorama Puncak Salak" (sebelumnya website menulis "di kaki Gunung Salak") | B26 hal. 3 & 23; WEB: "tepat di bawah kaki gunung Gede Pangrango" | Teks beranda: Konten › Halaman › Beranda |
| "Satu jam dari Jakarta" | **Dihapus**, karena tidak ada di sumber mana pun. Contoh rundown B26 hal. 17 justru berangkat 06.00, tiba 08.00 | – | – |
| Pangrango Deluxe | Kamar 22 m², LED TV 32 inci, water heater, lemari pakaian, teko listrik | WEB `/deluxe-room/` (30 Okt 2024) | Inventaris & Harga › Tipe Unit › Fasilitas |
| Pangrango Junior Suite | Kamar 26 m², Smart TV 43 inci, water heater, lemari pakaian, teko listrik | WEB `/junior-suite-room/` (19 Mar 2025) | idem |

Data lama yang sudah sesuai B26 (harga 8 tipe unit, 9 paket group, 7 aktivitas, 23 item tambahan, menu A–E, rute, kapasitas meeting 200 orang, Swaka Cafe & Resto, Sanggar Alam, mekanisme pembayaran) **tidak diubah**. Seluruh 30 halaman B26 sudah dicocokkan ulang dengan isi database.

## 4. Perbedaan antar-sumber (belum bisa dipastikan)

| No | Hal | B26 (2026) | WEB / PRM (2024–2025) | Yang dipakai website |
|---|---|---|---|---|
| S1 | Kapasitas **Pangrango Deluxe** | 2 pax, 1 King Bed | WEB: "Kapasitas: 4 Orang" | **2 pax** (B26 lebih baru). Konfirmasi ke resort |
| S2 | **Campervan** | Rp270.000 (WD) / Rp425.000 (WE) per orang | PRM (Jan 2025): Rp75.000/orang/malam | B26, pemesanan online **tetap dimatikan** (lihat DATA-TODO no. 10) |
| S3 | **Camp Site** | Rp58.000 / Rp64.000 per orang | PRM "Camping Site Jabon": Rp50.000/orang/malam; WEB "mulai 50 ribuan" | B26 |
| S4 | **Glamping** | Rp670.000 / Rp815.000, sarapan 2 pax | PRM: Rp500.000 / Rp790.000, sarapan 4 orang | B26 |
| S5 | **Camping Ceria** | Rp580.000 / Rp640.000 per tenda, sarapan 2 pax | PRM: Rp550.000/tenda, sarapan 4 orang; `promo-camp-cer.jpeg`: Rp350.000/2D1N | B26 |
| S6 | **Pondok Sunset** vs **Bale Gunung** | Bale Gunung Rp525.000 / Rp640.000, 1 Queen + 1 Single | PRM "Pondok Sunset": Rp500.000 / Rp600.000, queen bed, sarapan 4 orang | B26. Belum pasti apakah Pondok Sunset = Bale Gunung |
| S7 | Domain | `www.eji.id` (tidak bisa dibuka) | `elji.id` (aktif) | elji.id |
| S8 | Pembayaran online | "Pembayaran hanya melalui transfer" (hal. 30) | – | Website menawarkan Midtrans (keputusan proyek, menunggu persetujuan resort; DATA-TODO bagian D) |

## 5. Foto, logo, dan artikel resmi (tahap transformasi, 25 Okt 2026)

**Cara pengumpulan.** Daftar media publik elji.id dibaca lewat API WordPress resmi (`/wp-json/wp/v2/media`, 170 file; robots.txt mengizinkan). Tidak ada login, pembatasan, atau sistem anti-bot yang dilewati.

**Seleksi.** 86 kandidat diperiksa satu per satu sebagai gambar:
- **Dipakai: 50 foto asli resort**, disimpan di `public/images/resort/{nama}-{480,960,1600}.webp`. Sumber, tanggal unggah, dan ukuran asli tiap foto dicatat di `public/images/resort/SUMBER.json`.
- **Ditolak:** foto stok bawaan template WordPress, seperti "glacier national park", "aurora borealis", dan pasangan berkemah luar negeri. Ini bukan foto resort, jadi tidak boleh dipakai.
- **Ditolak:** gambar promo 2024 berisi harga lama, logo partner, dan placeholder template.

**Pemakaian.**
- Foto akomodasi masuk ke Glamping, Pangrango Deluxe, Junior Suite, Kemping Ceria, Camp Site, dan Campervan.
- Foto juga dipakai untuk sampul program Play/Learn/Gather, 26 foto galeri, hero beranda dan halaman, artikel, serta latar login admin.
- Semuanya dipasang lewat `Database\Seeders\OfficialMediaSeeder`. Seeder ini hanya mengganti gambar sementara dari brosur, dan foto unggahan admin tidak disentuh.

**Belum ada foto asli** untuk Pondokan Bale Gunung, Family Suite Camp, dan Swaka (interior). Bale Gunung dan Family Suite Camp tetap memakai gambar brosur, dan halaman detailnya diberi keterangan "Gambar ilustrasi dari brosur resort". Gambar di brosur 2026 tampak seperti ilustrasi atau render, bukan foto.

**Logo.**
- Simbol resmi Lingkung Gunung diambil dari brosur 2026 halaman 1 (render 300 dpi). Latarnya dibuang dan bentuknya tidak diubah.
- Disediakan versi hijau, versi terang (untuk latar gelap), dan favicon di `public/images/brand/`.
- File vektor (SVG) resmi tetap perlu diminta ke resort (DATA-TODO bagian D).

**Artikel.** Empat artikel awal (`ArticleSeeder`) disusun ulang dari artikel resmi elji.id, bukan disalin. Tautan sumber tampil di bawah setiap artikel.

**Izin.** Foto dan logo milik resort, dipakai untuk website resmi resort itu sendiri. **Sebelum go-live, mohon minta konfirmasi tertulis dari resort** bahwa foto di elji.id boleh dipakai di website baru (DATA-TODO no. 37).

## 6. Yang sengaja TIDAK ditampilkan

- Promo 2024–2025 dari elji.id (S2–S6): tanpa masa berlaku dan sudah digantikan harga 2026. Bila resort punya promo baru, tambahkan lewat admin › Promo/Banner (dengan tanggal mulai & berakhir).
- Testimoni di beranda elji.id: tidak disalin karena tidak ada izin dari pemberi testimoni dan tidak bisa diverifikasi.
- Jam check-in/check-out: tidak ada di sumber mana pun (DATA-TODO no. 21).
