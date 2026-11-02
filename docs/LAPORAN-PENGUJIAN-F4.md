# Laporan Pengujian F4

Tanggal: 4 Oktober 2026 · Branch: `f4-pengamanan`

## 1. Test otomatis

**113 test lulus, 522 pemeriksaan** (`php artisan test`, MySQL 8.4).

| Kelompok | File | Yang diuji |
|---|---|---|
| Harga WD/WE | `PricingServiceTest` | Tarif per malam, weekend, hari libur, musim ramai, promo, DP |
| Double booking | `ConcurrentBookingTest` | 5 proses PHP paralel memperebutkan unit terakhir: hanya 1 berhasil |
| Alur booking | `BookingServiceTest`, `BookingWizardTest`, `AdminBookingFlowTest` | Hold, expire, DP, pelunasan, batal, check-in, H-3, rate limit, honeypot |
| Webhook Midtrans | `MidtransWebhookTest`, `SnapPaymentTest` | Signature salah ditolak, notifikasi ganda tidak dobel, nominal/order_id, cek ulang status, pembayaran terlambat |
| B2B | `QuotationServiceTest`, `PackageBuilderTest` | Estimasi, aturan paket, proposal PDF, konversi ke booking |
| Website | `PublicSiteTest`, `StyleGuideTest` | Semua halaman, SEO, e-ticket, pengingat |
| Admin & role | `AdminPanelTest` | Hak akses Owner/Admin/Sales/FO |
| Keamanan (F4) | `SecurityTest`, `MfaRequiredTest` | Header, HSTS, halaman error, batas login, 2FA wajib, enkripsi 2FA, pembersihan log, jadwal backup |
| Laporan (F4) | `ReportTest` | Pendapatan, sumber booking, okupansi, ekspor Excel, hak akses |

## 2. Audit dependensi

- `composer audit`: **0 kerentanan**
- `npm audit --omit=dev`: **0 kerentanan**

## 3. Backup & pemulihan

- `php artisan backup:run` berhasil: 129 file + dump database, 5,9 MB, integritas terverifikasi.
- Arsip **terenkripsi AES-256**: tanpa password isi tidak bisa dibaca; dengan password, dump berisi semua tabel (bookings, payments, daily_inventory, unit_types, quotations, media) beserta data harga.

## 4. Performa & aksesibilitas (Lighthouse, mode HP)

Diukur dengan Lighthouse 12 (simulasi HP: koneksi 4G lambat, CPU 4× lebih lambat) pada kondisi mirip produksi: `APP_DEBUG=false`, aset hasil build, kompresi gzip/brotli dan cache aset (meniru Cloudflare).

| Halaman | Performa | Aksesibilitas | Best practices | SEO | FCP | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|---|
| Beranda `/` | 93 | 100 | 100 | 100 | 1,4 dtk | 3,0 dtk | 0 | 20 ms |
| Detail akomodasi | 95 | 100 | 100 | 100 | 1,2 dtk | 2,9 dtk | 0 | 0 ms |
| Wizard booking `/pesan` | 99 | 100 | 100 | 63* | 1,6 dtk | 2,0 dtk | 0 | 0 ms |
| Gather | 97 | 100 | 100 | 100 | 1,2 dtk | 2,6 dtk | 0 | 50 ms |
| Package Builder | 99 | 100 | 100 | 100 | 1,6 dtk | 2,0 dtk | 0 | 20 ms |

\* `/pesan` sengaja `noindex` (halaman transaksi tidak perlu diindeks Google).

Perbaikan yang dilakukan dari temuan Lighthouse:
- Kontras teks kecil dinaikkan (`kabut-500` → `kabut-600`, 3,8:1 → 5,5:1) di 18 file.
- Label logo diselaraskan dengan teks yang terlihat (pembaca layar / kontrol suara).
- Font judul dikurangi menjadi satu ketebalan (Fraunces 600): 36 KB → 18 KB.

Catatan: LCP beranda & detail akomodasi (2,9–3,0 dtk) sedikit di atas target "Good" 2,5 dtk pada simulasi lab yang ketat. Server development tidak mewakili produksi (tanpa HTTP/2, tanpa CDN). Ukur ulang setelah staging di belakang Cloudflare, dan pantau data nyata pengunjung di Google Search Console › Core Web Vitals setelah go-live. Bila masih di atas target, opsi lanjutan: foto hero lebih kecil untuk HP, dan font tagline (Kaushan Script) dimuat belakangan.

## 5. Pemeriksaan konfigurasi produksi

`php artisan resort:cek-produksi` di laptop development menghasilkan 10 butir GAGAL. Ini **sesuai harapan**, karena laptop memang memakai `APP_DEBUG=true`, Midtrans sandbox, email ke log, dan user database dengan hak penuh. Perintah yang sama dijalankan di staging/produksi sampai tidak ada butir GAGAL.
