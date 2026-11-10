# Laporan Audit Keamanan, QA & Uji End-to-End

Tanggal: 18 Oktober 2026 · Branch: `audit-keamanan` · Lingkungan: laptop development (Windows, PHP 8.4, MySQL 8.4, Midtrans **sandbox**). Tidak ada pengujian ke server produksi, uang sungguhan, atau email pelanggan asli.

## A. Ringkasan

Kondisi awal sudah baik: 146/146 test lulus, harga dihitung di server, stok dikunci (`lockForUpdate`), webhook Midtrans memverifikasi signature + nominal + order_id + cek ulang status ke API, URL publik memakai kode acak, admin terpisah dengan 2FA dan role.

Temuan terpenting yang diperbaiki:
1. Pembayaran sukses bisa **tidak tercatat** bila webhook gagal sampai dan tamu tidak kembali ke website (terbukti di sandbox) → rekonsiliasi terjadwal tiap 5 menit.
2. Klik ganda / dua tab bisa membuat **booking ganda**, dan satu akun bisa **menimbun stok** dengan banyak booking belum dibayar → kunci per akun + deteksi duplikat + batas 3 booking menunggu bayar.
3. Unggahan **SVG** (bisa berisi script) diterima form bukti transfer → hanya JPG/PNG/WebP + header `sandbox`.
4. Beberapa bug: Package Builder error 500 bila aktivitas dinonaktifkan, e-ticket PDF 2 halaman & 884 KB, halaman Kontak tanpa `h1`, dll.

Akhir: **156/156 test lulus (836 assertion)**, crawl browser 105 halaman tanpa masalah, siklus bayar DP → pelunasan → Lunas terverifikasi di Midtrans sandbox.

Risiko go-live yang tersisa bersifat operasional/bisnis (email asli, HTTPS, cron hosting, kunci Midtrans produksi, kebijakan refund & faktur), lihat bagian E–F.

## B. Temuan keamanan

| # | Tingkat | Temuan | Bukti | Komponen | Perbaikan | Verifikasi |
|---|---|---|---|---|---|---|
| S1 | Sedang | Stored XSS lewat SVG di bukti transfer: `FileUpload::image()` menerima `image/*` (termasuk SVG), file dibuka inline di domain admin | Kode `ViewBooking` + `PaymentProofController` | Admin › Catat pembayaran | Hanya JPG/PNG/WebP (`ImageSizes::UPLOAD_MIME_TYPES`) di 4 form unggah; respons bukti transfer diberi CSP `default-src 'none'; sandbox` + `nosniff` | `test_unggahan_svg_ditolak…`, `test_bukti_transfer_ditampilkan_tanpa_izin…` ✅ |
| S2 | Sedang (integritas) | Pembayaran sukses tidak tercatat bila webhook gagal & tamu tidak kembali; booking bisa kedaluwarsa padahal sudah dibayar | Sandbox: booking `LGWV28F7VG` tetap *pending* setelah kartu uji sukses, sampai rekonsiliasi dijalankan | PaymentService | `reconcilePending()` + `payments:reconcile` tiap 5 menit (aturan sama dengan webhook, idempoten) | Sandbox nyata: pending → DP Dibayar; dijalankan ulang tidak dobel; 3 test ✅ |
| S3 | Sedang | Booking ganda (klik ganda/dua tab) & penimbunan stok oleh satu akun | Analisis kode `BookingWizard::submit` (hanya rate limit per IP) | Wizard booking | `Cache::lock` per akun, booking identik ≤ 2 menit dipakai ulang, maks. `MAX_PENDING_HOLDS_PER_ACCOUNT=3` | `test_kirim_dua_kali…`, `test_satu_akun_tidak_bisa_menimbun…` ✅ |
| S4 | Rendah (potensi) | Host-header poisoning: URL dibuat dari header `Host` permintaan | Analisis kode (email reset sudah dibuat di antrean = memakai APP_URL, jadi risiko kecil) | AppServiceProvider | Produksi: `URL::forceRootUrl(APP_URL)` | Tidak bisa diuji di laptop (hanya aktif di production) |
| S5 | Rendah | Kebocoran versi PHP (`X-Powered-By: PHP/8.4.26`) | `curl -D -` ke beranda | SecurityHeaders | `header_remove()` + cek `expose_php` di `resort:cek-produksi` | `curl`: header hilang ✅ |
| S6 | Rendah (dev saja) | `shell-quote` critical (lewat `concurrently`, hanya untuk `composer run dev`) | `npm audit` | package-lock | `npm audit fix` | `npm audit`: 0 ✅ |

Diuji dan **tidak ditemukan celah**:

| Area | Cara uji | Hasil |
|---|---|---|
| SQL injection | Telusur kode: semua query lewat Eloquent/binding; `selectRaw` hanya teks tetap | Aman |
| XSS | Blade `{{ }}`; `{!! !!}` hanya JSON-LD (`JSON_HEX_TAG`), QR buatan server, konten editor lewat `Str::sanitizeHtml` | Aman |
| CSRF | `curl` POST `/akun/masuk` tanpa token | 419 ✅ |
| Webhook palsu | Signature salah (curl), nominal palsu & order_id asing (bertanda tangan sah) | 403 / 422 / 404 ✅ |
| Notifikasi ganda | Kirim ulang notifikasi settlement 2× | "Sudah diproses sebelumnya", pembayaran tetap 1× ✅ |
| Manipulasi harga/status | Harga dihitung ulang di server; properti Livewire (langkah, qty, tamu) dimanipulasi | Ditolak/dihitung ulang ✅ (test) |
| IDOR | Booking/akun/tersimpan/e-ticket milik orang lain | 404 ✅ (test) |
| Pelanggan → admin | Akses `/kelola`, resource, bukti transfer dengan sesi pelanggan | Diarahkan ke login staf ✅ |
| Session fixation | `session()->regenerate()` saat login/daftar/ganti password | ✅ |
| Rate limit | Login 5×/menit, reset, verifikasi, booking, builder, webhook | ✅ |
| File sensitif | `/.env`, `/.git/config`, `/composer.json`, path traversal `/storage/..%2f` | 404/403 ✅ |
| Open redirect | Tujuan setelah login hanya dari sesi server (`url.intended`) | Aman |
| Mass assignment | Semua model `#[Fillable]`; tidak ada `$request->all()` ke model | Aman |
| Double booking paralel | `ConcurrentBookingTest` (5 proses PHP memperebutkan unit terakhir) | Hanya 1 berhasil ✅ |

Risiko yang **tidak bisa diverifikasi / diterima**:
- Pendaftaran memberi tahu "email sudah terdaftar" (enumerasi email): pilihan UX, dibatasi rate limit.
- Keluar dari akun pelanggan mengakhiri seluruh sesi browser (staf yang login di browser yang sama ikut keluar). Dampak kecil.
- Link `/booking/{kode}` untuk booking tanpa akun (walk-in/B2B) dan `/proposal/{kode}` adalah link rahasia (siapa pun yang memegang link bisa melihat). Sesuai keputusan pemilik proyek.

## C. Perbaikan yang dikerjakan

| Perbaikan | File |
|---|---|
| Rekonsiliasi pembayaran Midtrans terjadwal | `app/Services/PaymentService.php` (`reconcilePending`), `app/Console/Commands/ReconcilePayments.php`, `routes/console.php` |
| Anti booking ganda & penimbunan stok | `app/Services/CustomerAccountService.php` (`guardNewBooking`), `app/Livewire/BookingWizard.php`, `config/resort.php`, `.env*.example` |
| Unggahan hanya JPG/PNG/WebP + CSP bukti transfer | `app/Support/ImageSizes.php`, `ViewBooking`, `GalleryPhotoForm`, `ProgramForm`, `UnitTypeForm`, `PaymentProofController` |
| Host header & versi PHP | `AppServiceProvider`, `SecurityHeaders`, `CheckProductionReadiness` |
| Package Builder tidak error bila aktivitas/item dinonaktifkan | `QuotationService::estimate`, `PackageBuilder::hydrate` |
| E-ticket 1 halaman A5, PDF 884 KB → ±27 KB (subset font; juga proposal) | `resources/views/pdf/e-ticket.blade.php`, `ETicket`, `QuotationService::pdf` |
| Kontak punya `h1`; lightbox galeri tanpa `<img src="">` | `section-heading` (`level`), `contact`, `gallery` |
| Dependensi dev | `package-lock.json` |
| Dokumentasi | `docs/DEPLOY.md` (php.ini, cron), laporan ini |

Perbaikan dari tahap integrasi login sebelumnya (masih berlaku): pengecekan akun di wizard dipertegas walau langkah dimanipulasi, rute bukti transfer dikunci ke guard staf.

## D. Hasil pengujian

| Fitur | Skenario | Hasil | Bukti |
|---|---|---|---|
| Regresi | Seluruh test otomatis | PASS | `php artisan test`: 156/156, 836 assertion |
| Publik | 29 halaman × 3 lebar layar: status, error JS, aset, overflow, h1, alt, gambar rusak | PASS | Crawl Puppeteer: "TIDAK ADA MASALAH" |
| Tautan | 42 tautan internal | PASS | Crawl |
| Akun | Daftar, verifikasi (link sah/palsu/kedaluwarsa), kirim ulang, login, batas login, lupa & reset password, profil, ganti password, tersimpan | PASS | `CustomerAccountTest` (31 test) |
| Akses | Tamu → login → kembali ke wizard dengan pilihan sama | PASS | Test + browser |
| Akses | Booking milik orang lain / akun lain / pelanggan ke admin | PASS | Test |
| Booking | Tanggal salah, kapasitas kurang, stok habis, H-3 wajib lunas, honeypot, rate limit | PASS | `BookingWizardTest`, `BookingServiceTest` |
| Booking | Klik ganda, penimbunan stok | PASS | `AuditRegressionTest` |
| Booking | 5 pemesan paralel unit terakhir | PASS | `ConcurrentBookingTest` |
| Bayar (sandbox nyata) | Buat booking lewat browser → Snap → kartu uji → sukses | PASS | Booking `LGWV28F7VG`, DP Rp131.250 |
| Bayar (sandbox nyata) | Sukses tetapi tamu tidak kembali & tanpa webhook | PASS (setelah perbaikan) | `payments:reconcile`: pending → DP Dibayar |
| Bayar (sandbox nyata) | Pelunasan, tamu kembali ke website | PASS | Status Lunas tanpa webhook (cek ke API saat kembali) |
| Webhook (lokal, bertanda tangan) | Ganda, nominal palsu, order_id asing, signature salah | PASS | 200 diabaikan / 422 / 404 / 403 |
| Bayar | Gagal / kedaluwarsa / pending / dibayar setelah hold habis | PASS (mock) | `MidtransWebhookTest`, `SnapPaymentTest`, `AuditRegressionTest` |
| Bayar | Pembatalan di Snap (tutup popup) | NOT TESTED langsung | Kode: `onClose` hanya mengaktifkan tombol lagi; status tidak berubah |
| E-ticket | PDF valid, 1 halaman, ringan; pemilik bisa unduh, akun lain 404 | PASS | Browser + test |
| Email | E-ticket setelah bayar (antrean), verifikasi, reset password, isi Bahasa Indonesia | PASS (log/fake) | `storage/logs/laravel.log`, test |
| Email | Pengiriman ke kotak masuk sungguhan | BLOCKED | Belum ada pengirim email (MAIL_MAILER=log) |
| Admin | Login + 2FA, dashboard, booking, laporan, kalender, penawaran, log webhook, tipe unit, pengaturan | PASS | Browser (akun staf sementara, sudah dihapus) |
| Admin | Booking sandbox tampil Lunas Rp525.000 di detail admin | PASS | Browser |
| Admin | Hak akses per role | PASS | `AdminPanelTest` |
| Dependensi | `composer audit`, `npm audit` | PASS | 0 kerentanan |
| Format & build | `pint --test`, `npm run build`, `route:cache`, `view:cache` | PASS | — |
| Produksi | `resort:cek-produksi` | Sesuai harapan di laptop | 9 GAGAL karena pengaturan development |
| Performa | Lighthouse | NOT TESTED ulang | Hasil F4 (`docs/LAPORAN-PENGUJIAN-F4.md`) |

## E. Fitur kurang & keputusan bisnis (urut dampak)

1. **Tinggi:** pengirim email asli (domain resort + SPF/DKIM) dan `CUSTOMER_EMAIL_VERIFICATION=true`. Tanpa ini e-ticket/verifikasi tidak sampai.
2. **Tinggi:** kebijakan pembatalan & refund (DP hangus? lewat H-3?). Website belum punya alur refund otomatis; refund dilakukan manual lewat Midtrans dashboard (log "PERLU TINDAKAN" di admin).
3. **Sedang:** faktur/invoice resmi (nomor faktur, pajak PB1/PPN bila berlaku, NPWP). Saat ini e-ticket PDF berfungsi sebagai bukti bayar (total, dibayar, sisa). Tidak dibuat tanpa aturan dari resort.
4. **Sedang:** data brosur yang janggal (`docs/DATA-TODO.md`), jam check-in/out.
5. **Rendah:** detail booking admin belum menampilkan kanal pembayaran (mis. `credit_card`, VA); data sudah tersimpan di `payments.payment_channel`.
6. **Rendah:** pelanggan belum bisa menghapus akun sendiri (UU PDP: bisa lewat permintaan ke resort).

## F. Kesiapan produksi

| Area | Status | Bukti / catatan |
|---|---|---|
| Logika booking, harga, stok | Ready | Test + uji paralel + E2E |
| Pembayaran Midtrans | Ready with limitations | Sandbox terverifikasi; kunci produksi & Payment Notification URL belum (F5) |
| Akun & otorisasi | Ready | Test + browser |
| Admin & role | Ready | Test + browser |
| Email | Not ready | MAIL_MAILER=log |
| HTTPS, cookie Secure, HSTS | Not verified | Butuh server + Cloudflare; kode siap (`forceScheme`, HSTS saat HTTPS) |
| Konfigurasi `.env` produksi | Not ready | `resort:cek-produksi` di server sampai tanpa GAGAL |
| User database tanpa DROP/GRANT | Not ready | `docs/DEPLOY.md` bagian 3 |
| Antrean & cron | Ready with limitations | Jadwal lengkap (termasuk rekonsiliasi); cron hosting belum dipasang |
| Backup & pemulihan | Ready with limitations | Terenkripsi & teruji di F4; disk terpisah (R2) & email notifikasi belum |
| Log & pemantauan | Ready with limitations | Log harian + log webhook di admin; belum ada layanan error monitoring (Sentry DSN kosong) |
| Dependensi | Ready | 0 kerentanan |
| Debug & rahasia | Ready with limitations | Rahasia hanya di `.env`; `APP_DEBUG=false` wajib di server |

## G. Serah terima

```bash
php artisan migrate                 # migration aman (tidak menghapus data)
php artisan test                    # 156 test (MySQL harus menyala)
vendor/bin/pint --test              # format kode
npm run build                       # aset produksi
composer audit && npm audit         # cek kerentanan dependensi
php artisan payments:reconcile      # cek ulang transaksi Midtrans yang masih menunggu
php artisan resort:cek-produksi     # di server: harus tanpa GAGAL
php artisan schedule:list           # pastikan payments:reconcile & bookings:expire-holds terjadwal
```

Email (saat go-live): isi `MAIL_*` (Brevo/Resend + domain resort), `MAIL_FROM_ADDRESS`, `APP_URL=https://…`, lalu `CUSTOMER_EMAIL_VERIFICATION=true`. Uji: daftar akun baru → email verifikasi masuk; lupa password → email masuk; bayar sandbox → e-ticket masuk.

Verifikasi yang masih perlu dilakukan di staging/produksi: transaksi nyata nominal kecil + refund manual; Payment Notification URL Midtrans mengarah ke `https://domain/webhook/midtrans` dan muncul di admin › Log Webhook; Lighthouse ulang di belakang Cloudflare; uji pemulihan backup dari R2.

Data uji di database lokal: akun pelanggan `demo.e2e@example.com` (password `demo12345`) dengan booking sandbox `LGWV28F7VG` (Lunas, 23–24 Nov 2026). Aman dihapus kapan saja.
