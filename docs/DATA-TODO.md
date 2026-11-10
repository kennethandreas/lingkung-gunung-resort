# Data yang Perlu Dikonfirmasi ke Resort

Dokumen ini berisi daftar angka dan aturan dari brosur 2026 yang janggal, tidak lengkap, atau belum ada sama sekali.
Website sudah memakai **nilai sementara** supaya pengembangan bisa berjalan. Semua nilai bisa diubah sendiri dari admin panel (`/kelola`) tanpa mengubah kode.

Di kode, setiap nilai sementara ditandai dengan komentar `// TODO: konfirmasi ke resort`.

Terakhir diperbarui: 25 Oktober 2026 (tahap peningkatan; perbandingan brosur vs elji.id ada di `docs/SUMBER-DATA.md`)

---

## A. Harga yang janggal (dimasukkan apa adanya sesuai brosur)

| No | Item | Angka di brosur | Dugaan | Tempat mengubah di admin |
|---|---|---|---|---|
| 1 | Family Suite Camp, harga **weekday** | Rp8.700.000 / tenda / malam (weekend hanya Rp990.000) | Rp870.000? | Inventaris & Harga › Tipe Unit & Harga › Family Suite Camp › tab **Harga** |
| 2 | Sleeping Bag | Rp17.500.000 / pax | Rp17.500? | Paket Rombongan › Item Tambahan |
| 3 | Kursi Standar | Rp11.600.000 / kursi | Rp11.600? | Paket Rombongan › Item Tambahan |
| 4 | Aula Mandalawangi (3), U Shape, maks. 60 orang | Rp135.000 / sett | Rp1.350.000? | Paket Rombongan › Item Tambahan |
| 5 | Half Day (4 Jam Meeting) | Rp1.630.000 / pax (One Day 8 jam hanya Rp210.000 / pax) | Rp163.000? | Paket Rombongan › Item Tambahan |
| 6 | Aula Mandalawangi (3), Lesehan, maks. 100 orang | Rp2.199.999 / sett | Rp2.200.000? | Paket Rombongan › Item Tambahan |
| 7 | Meja Standar dan Meja Meeting | Satuan tertulis "1 Kursi", keterangan "Kursi Standar" | Per meja? | Paket Rombongan › Item Tambahan (saat ini dicatat satuan **meja**) |
| 8 | Sate Kambing (1 porsi, 10 tusuk) | Rp410.000, satuan "5 porsi (minimal)" | Harga per porsi atau per paket 5 porsi? | Paket Rombongan › Item Tambahan (saat ini: per porsi, minimal 5) |

## B. Data yang belum ada di brosur

| No | Pertanyaan | Nilai sementara di website | Tempat mengubah |
|---|---|---|---|
| 9 | **Camp Site:** berapa kapasitas maksimal orang per malam? | 100 orang/malam, **pemesanan online dimatikan** sampai dikonfirmasi | Tipe Unit & Harga › Camp Site (Stok harian, toggle "Bisa dipesan online") |
| 10 | **Campervan:** harga per orang (seperti brosur) atau per kavling? Ada berapa kavling? | Per orang, 20 orang/malam, **pemesanan online dimatikan** | Tipe Unit & Harga › Kavling Campervan |
| 11 | **Definisi weekend:** malam Jumat + Sabtu, atau malam Sabtu saja? | Malam Jumat dan malam Sabtu dihitung weekend | Pengaturan › Pengaturan Umum › "Malam yang dihitung tarif weekend" |
| 12 | **Hari libur nasional & musim ramai:** harga berapa? Tanggal mana saja? | Belum ada tanggal yang ditandai. Bila ditandai "Musim Ramai" tanpa harga khusus, otomatis memakai harga weekend | Inventaris & Harga › Hari Libur & Musim Ramai; harga khusus di tab Harga (kategori "Musim Ramai") |
| 13 | **Sarapan / pax tambahan:** sarapan hanya termasuk 2 pax per kamar, padahal kapasitas 3–4 pax. Berapa harga sarapan atau pax tambahan? | Kosong (belum ditawarkan) | Tipe Unit & Harga › kolom "Harga pax tambahan" |
| 14 | **Coffee break Menu E:** brosur tidak memuat pilihan coffee break untuk Menu E, padahal Fullboard LDKS (B) memakai Menu E dan termasuk coffee break 1x | Belum ada opsi coffee break di Menu E | Paket Rombongan › Menu Makan A-E › Menu Makan E |
| 15 | **Jam Sanggar Alam:** Half-day tertulis 10.00–14.00 tetapi jadwalnya 08.00–13.00; Full-day tertulis 09.00–15.00 tetapi jadwalnya 08.00–15.00 | Memakai jam di judul (10.00–14.00 dan 09.00–15.00) | Paket Rombongan › Aktivitas |
| 16 | ~~**Email resmi resort**~~ **Terjawab (25 Okt 2026):** marketing@elji.id dari situs resmi elji.id. Mohon konfirmasi apakah email ini juga boleh menerima notifikasi pesan dari form kontak | marketing@elji.id | Pengaturan › Pengaturan Umum |
| 17 | **Masa berlaku harga 2026:** apakah harga brosur tetap berlaku di 2027? | Berlaku mulai 1 Januari 2026 tanpa tanggal akhir | Tab Harga di tiap tipe unit (isi "Berlaku sampai", lalu tambah harga baru) |

## C. Keputusan sementara yang perlu disetujui resort

| No | Hal | Keputusan sementara |
|---|---|---|
| 18 | **Nama unit di website** | Memakai nama pemasaran dari dokumen teknis: Pondokan Bale Gunung, Glamping Imah Lembah, Pangrango Deluxe, Pangrango Junior Suite, Kemping Ceria, Family Suite Camp (Two Room), Camp Site, Kavling Campervan. Nama di daftar harga (Glamourcamp, Camping Ceria, dll) tetap tersimpan dan terlihat di admin. |
| 19 | **Deskripsi unit** | Disusun dari teks brosur. Mohon dibaca ulang oleh resort. |
| 20 | **Ejaan menu A–E** | Disalin persis dari brosur, termasuk ejaan yang tampak salah ketik, misalnya "Semur Telut" (Menu B), "acara ingcolocolo" dan "soup bemabon" (Menu A), "coffe". Mohon dikoreksi bila perlu. |

## C2. Teks website yang perlu dibaca ulang (F2)

| No | Hal | Keterangan | Tempat mengubah |
|---|---|---|---|
| 21 | **Jam check-in & check-out** | Belum ada di brosur, jadi **belum ditampilkan** di website maupun e-ticket | Akan ditambahkan setelah dikonfirmasi |
| 22 | Judul & subjudul beranda | "Bangun di antara kabut, tidur di kaki Gunung Salak." (disusun dari brosur) | Konten › Halaman › Beranda (judul = headline, deskripsi = subjudul, foto = hero) |
| 23 | Deskripsi singkat aktivitas eco-culture, program Learn, dan teks Swaka | Ditulis ringkas dari judul di brosur (brosur hanya memuat nama aktivitas) | Konten › Play, Learn & Gather |
| 24 | FAQ | Hanya berisi fakta dari brosur & dokumen teknis | Konten › FAQ |
| 25 | Kebijakan Privasi & Syarat Ketentuan | **Draf**, perlu ditinjau resort (masa simpan data, kebijakan refund) | Konten › Halaman |

## C3. Asumsi Package Builder B2B (F3)

| No | Hal | Asumsi sementara | Tempat mengubah |
|---|---|---|---|
| 26 | **Satuan harga paket fullboard** (mis. LDKS A Rp260.000/pax) | **Per peserta per malam** (isi paket "Meals 3x" = makan satu hari). Acara 2 hari 1 malam = 1×, 3 hari 2 malam = 2×. Tarif WD/WE mengikuti malamnya | Kode: `app/Services/QuotationService.php` (bila ternyata harga per acara) |
| 27 | **Pilihan menu** | Opsi A–E dipilih **per hari per waktu makan** (sarapan, makan siang, makan malam), coffee break pilih 2 macam sesuai brosur | Builder & proposal |
| 28 | Hari libur untuk paket group | Malam hari libur/musim ramai memakai harga weekend; LDKS C ditolak di malam weekend/libur | Inventaris & Harga › Hari Libur |
| 29 | Lama hold booking B2B | Default sama dengan booking online (60 menit), bisa diperpanjang sales saat konversi (maks. 7 hari) untuk proses pembayaran instansi | Form "Konversi ke booking" |
| 30 | Alokasi unit untuk paket | Sales memilih unit yang dikunci saat konversi (mis. Glamping × 8 untuk Room Glamping). Paket tenda dome rombongan tidak memakai stok unit | Form "Konversi ke booking" |

## C4. Perbedaan brosur 2026 vs situs elji.id (25 Okt 2026)

Detail dan sumber: `docs/SUMBER-DATA.md` bagian 4. Website memakai angka brosur 2026 sampai ada konfirmasi.

| No | Pertanyaan | Nilai di website |
|---|---|---|
| 31 | **Pangrango Deluxe**: kapasitas 2 orang (brosur) atau 4 orang (elji.id)? | 2 orang |
| 32 | **Campervan**: brosur Rp270.000/Rp425.000 per orang, promo Jan 2025 Rp75.000/orang. Mana yang berlaku? (terkait no. 10) | Brosur, pemesanan online dimatikan |
| 33 | **Pondok Sunset** (promo 2024) = **Bale Gunung**? | Dianggap sama, harga brosur |
| 34 | Promo 2024 di elji.id (Glamping 500K/790K, Camping Ceria 550K, Camp Site 50K) masih berlaku? | Tidak ditampilkan |
| 35 | Sarapan: brosur 2026 "2 pax", promo 2024 "untuk 4 orang". Mana yang benar? | 2 pax |
| 36 | Domain resmi: brosur menulis www.eji.id, padahal yang aktif elji.id. Brosur perlu dikoreksi? | elji.id |

## C5. Foto & aset (tahap transformasi, 25 Okt 2026)

| No | Pertanyaan | Status di website |
|---|---|---|
| 37 | **Izin foto:** 50 foto dari elji.id dipakai di website baru (`public/images/resort/SUMBER.json`). Mohon konfirmasi tertulis dari resort | Sudah terpasang |
| 38 | **Foto asli yang belum ada:** Pondokan Bale Gunung, Family Suite Camp, interior Swaka Cafe & Resto, aula Suryakencana, Pendopo Sanggar Alam | Bale Gunung & Family Suite memakai ilustrasi brosur (berlabel) |
| 39 | Foto 2023 di elji.id memperlihatkan **kuda tunggang dan kandang kelinci**. Apakah masih ada? Brosur 2026 tidak menyebutnya | Tidak ditampilkan |

## C6. Ulasan tamu (27 Okt 2026, detail di `docs/ULASAN.md`)

| No | Pertanyaan | Keputusan sementara di website |
|---|---|---|
| 40 | **Kapan tamu dianggap "selesai menginap"?** Sistem belum punya status check-out. | Status "Sudah Check-in" (dicatat staf saat tamu datang) **dan** tanggal check-out sudah tiba. Booking yang hanya lunas (tamu tidak datang) tidak bisa diulas |
| 41 | **Batas waktu menulis ulasan** setelah check-out (mis. 90 hari)? | Tanpa batas |
| 42 | **Siapa yang memoderasi** ulasan? | Owner & Admin. Sales hanya melihat; Front Office tidak punya akses |
| 43 | **Aktivitas & paket:** pemesanan online (B2C) hanya untuk akomodasi. Ulasan aktivitas/paket hanya dari booking rombongan (penawaran B2B yang dikonversi) yang sudah ditautkan klien ke akunnya. Apakah perlu cara lain (mis. aktivitas yang dibeli langsung di resort dicatat staf)? | Hanya dari booking rombongan |
| 44 | Nama yang tampil di website: nama depan + inisial nama belakang (mis. "Budi S."). Setuju? | Ya |

## D. Pertanyaan dari dokumen teknis (bab 11.1) yang masih terbuka

- Kebijakan pembatalan dan refund: apakah DP hangus bila batal? Bagaimana jika pelunasan lewat dari H-3?
- Booking mepet: website saat ini mewajibkan **bayar lunas** untuk pemesanan kurang dari 3 hari sebelum kedatangan (sesuai dokumen teknis). Mohon dikonfirmasi.
- Pilihan menu paket: opsi A–E dipilih per hari dan per waktu makan, atau satu opsi untuk seluruh acara? Bolehkah pindah ke set menu lain dengan biaya tambahan? (dibutuhkan untuk F3 Package Builder)
- Persetujuan resort untuk pembayaran online via Midtrans (dana masuk ke rekening yang sama).
- Domain baru dan alamat email resmi (domain di brosur, www.eji.id, tidak berfungsi).
- Foto asli setiap unit, aula, resto, dan aktivitas. (Sementara website memakai potongan foto dari brosur di `public/images/placeholder/`.)
- File logo resort dalam format SVG atau PNG resolusi tinggi (sementara memakai wordmark teks).
