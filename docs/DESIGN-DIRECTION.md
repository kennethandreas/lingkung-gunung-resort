# Usulan Arah Desain: Website Lingkung Gunung

Status: **DISETUJUI** pemilik proyek (F2, 4 Oktober 2026). Label menu: Stay · Play · Learn · Gather · Taste.
Lihat langsung: `http://localhost:8000/style-guide`

## 1. Suasana

"Kabut pagi di atas hutan pinus." Hangat, alami, dan premium, setara website resort butik, bukan template.

- **Foto adalah bintang utama:** hero layar penuh, kartu akomodasi dengan foto dominan, galeri besar. Teks dan warna mendukung, bukan bersaing.
- **Detail dari brosur:** hijau tua dan emas (roda "Ecosystem of Experience"), garis kontur peta sebagai motif latar, tagline tulisan tangan "Tempat Alam Bercerita".
- **Gerak halus:** konten muncul pelan saat di-scroll, kartu terangkat saat disentuh/hover, kabut di hero bergerak perlahan. Semua otomatis mati bila pengguna memilih "kurangi animasi" di perangkatnya.

## 2. Palet warna

| Token | Peran | Warna kunci |
|---|---|---|
| `hutan` | Warna utama: header, tombol utama, judul, latar gelap | hutan-800 `#1f402d` |
| `emas` | Aksen: tombol "Cek Ketersediaan", ikon, garis dekoratif | emas-400 `#d4a43c` (teks emas pakai emas-700 `#84521f`) |
| `tanah` | Pendukung hangat: section Play/Gather, latar ilustrasi | tanah-500 `#8b5e3c` |
| `kabut` | Netral hangat: latar kertas, teks isi, garis | kabut-50 `#fbf9f4`, kabut-900 `#26231e` |
| status | sukses `#1f8a5b`, peringatan `#b45309`, bahaya `#b42318`, info `#1d6fa5` | dibedakan dari hijau brand |

Kontras sudah dicek (dihitung otomatis di halaman style guide): teks isi 14,9:1, tombol utama 10,9:1, tombol aksen 7,6:1, semuanya lolos WCAG AA/AAA. Emas muda (emas-400) **tidak dipakai untuk teks** di latar terang (2,2:1).

## 3. Tipografi (Google Fonts, di-host sendiri saat build)

| Peran | Font | Alasan |
|---|---|---|
| Judul | **Fraunces** 500/600 | Serif lembut berkarakter, terasa hangat & premium, senada dengan judul serif di brosur |
| Isi | **Plus Jakarta Sans** 400–700 | Dirancang di Indonesia, sangat terbaca di HP, modern |
| Aksen | **Kaushan Script** 400 | Mirip tulisan tangan "Tempat Alam Bercerita" di brosur. Hanya untuk tagline pendek |

Ukuran judul fluid (`text-display-2xl` … `text-display-md`): mengecil rapi di HP, tidak perlu ukuran terpisah.

## 4. Token lain

- Radius: `rounded-kartu` 1.25rem, `rounded-foto` 1rem, tombol & badge `rounded-full`.
- Bayangan bernuansa hijau gelap: `shadow-lembut`, `shadow-angkat` (hover), `shadow-mengambang` (sticky bar).
- Jarak: `.section` (64 → 80 → 112px), `.container-lg` (maks. 1280px, gutter 16px di HP).
- Gerak: easing `ease-alam`, durasi 300–800ms.

## 5. Komponen Blade (`resources/views/components/ui/`)

button · badge · picture (WebP + srcset + lazy + placeholder) · card-akomodasi · field · input · select · stepper · alert · modal (bottom sheet di HP) · steps (indikator langkah) · price-summary · section-heading · reveal · mobile-bar · logo
Layout: `<x-layouts.public>` (header transparan di atas hero, menu HP layar penuh, footer kontak dari Pengaturan, sticky bar HP).

## 6. Kinerja & aksesibilitas

- Font di-host sendiri + fallback yang ukurannya disamakan (mengurangi pergeseran layout/CLS). Hanya 2 varian font yang di-preload.
- Gambar WebP 480/960/1600px; hero `fetchpriority="high"`, lainnya lazy. Placeholder brosur ≤ 171 KB per file.
- Tanpa library JS tambahan: Alpine.js sudah termasuk di Livewire.
- Skip link "Lompat ke konten", fokus keyboard terlihat (outline emas), modal mengunci fokus, label form selalu terlihat, pesan error terhubung ke field.

## 7. Yang perlu diputuskan

1. **Palet & font di atas: setuju atau ada yang ingin diubah?**
2. **Label menu utama:** saat ini memakai konsep brosur dalam bahasa Inggris (Stay · Play · Learn · Gather · Taste) dengan keterangan Indonesia di menu HP. Alternatif: label Indonesia (Menginap · Petualangan · Edukasi · Rombongan · Kuliner).
3. **Logo:** saat ini wordmark sementara. Butuh file logo resort (SVG/PNG resolusi tinggi) dari pihak resort.
4. **Foto:** gambar di style guide adalah potongan sementara dari brosur. Foto asli tetap dibutuhkan (lihat DATA-TODO).

Setelah disetujui: token yang sama diterapkan ke tema admin panel (Filament), lalu halaman publik dibangun.
