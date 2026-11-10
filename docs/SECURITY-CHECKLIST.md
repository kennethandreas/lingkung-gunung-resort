# Checklist Keamanan (Dokumen Teknis Bab 7)

Status per 4 Oktober 2026 (F4). Semua butir wajib selesai sebelum go-live.

Keterangan: ✅ selesai di kode & diuji otomatis · 🔧 dikonfigurasi saat deploy (dicek oleh `php artisan resort:cek-produksi`) · 📋 prosedur rutin

## Pembayaran

| Butir | Status | Bukti |
|---|---|---|
| Total harga selalu dihitung ulang di server | ✅ | `PricingService`, `QuotationService`; `BookingServiceTest::test_hold_menahan_stok_dan_menghitung_harga_di_server` |
| Webhook memverifikasi `signature_key` | ✅ | `PaymentService::verifySignature`; `MidtransWebhookTest::test_signature_salah_ditolak...` |
| Nominal & `order_id` dicocokkan | ✅ | `test_nominal_tidak_cocok_ditolak`, `test_order_id_tidak_dikenal_ditolak` |
| Status dicek ulang ke API Midtrans sebelum booking diubah | ✅ | `test_status_dicek_ulang_ke_midtrans_sebelum_booking_diubah` |
| Webhook idempoten (notifikasi ganda tidak dobel) | ✅ | `test_notifikasi_ganda_tidak_menambah_pembayaran_dua_kali` |
| Parameter URL palsu tidak bisa mengubah status | ✅ | `test_parameter_url_palsu_tidak_bisa_mengubah_status...` |
| Server Key hanya di `.env` server | ✅🔧 | `config/services.php` membaca `.env`; `.env` tidak masuk Git |
| Anti double-booking (stok dikunci) | ✅ | `ConcurrentBookingTest` (5 proses paralel, hanya 1 berhasil) |

## Akses data tamu

| Butir | Status | Bukti |
|---|---|---|
| URL booking, e-ticket, proposal memakai kode acak | ✅ | `test_halaman_booking_memakai_kode_acak...`, `test_url_proposal_memakai_kode_acak` |
| Halaman publik hanya menampilkan data minimum | ✅ | Halaman booking: nama, tanggal, rincian, status (tanpa email/HP lengkap) |
| Seluruh situs HTTPS | ✅🔧 | `URL::forceScheme('https')` di produksi, HSTS bila HTTPS (`test_hsts_hanya_dikirim_lewat_https`), Cloudflare Full (strict) |
| Data dikumpulkan sesuai kebutuhan, tanpa foto KTP | ✅ | Form hanya nama, HP, email, instansi |
| Halaman Kebijakan Privasi (UU PDP) | ✅📋 | `/info/kebijakan-privasi` (draf, **perlu ditinjau resort**) |
| Data lama dibersihkan | ✅ | Log aktivitas & log webhook > 365 hari dihapus otomatis (`test_log_webhook_lebih_dari_setahun_dibersihkan`) |

## Admin panel

| Butir | Status | Bukti |
|---|---|---|
| Login wajib 2FA | ✅ | `MfaRequiredTest` (tanpa 2FA diarahkan ke pemasangan authenticator) |
| Rahasia 2FA terenkripsi di database | ✅ | `test_rahasia_2fa_disimpan_terenkripsi_di_database` |
| Percobaan login dibatasi | ✅ | 5×/menit (Filament); `test_login_admin_dibatasi_setelah_lima_kali_gagal` |
| Role dibatasi (FO tidak ubah harga, Sales tidak hapus pembayaran) | ✅ | `AdminPanelTest`; pembayaran tidak punya aksi hapus untuk siapa pun |
| Hanya Owner yang mengelola pengguna & role | ✅ | `test_admin_tidak_bisa_mengelola_pengguna_dan_role` |
| Perubahan harga, booking, pembayaran tercatat | ✅ | spatie/laravel-activitylog, menu Log Aktivitas |
| Alamat panel bukan `/admin` | ✅ | `ADMIN_PATH=kelola`; `test_admin_panel_tidak_memakai_alamat_admin_default` |

## Aplikasi & server

| Butir | Status | Bukti |
|---|---|---|
| Rate limiting form booking, Package Builder, login | ✅ | 5×/menit/IP + honeypot (`test_percobaan_berlebihan_dibatasi`, `test_honeypot...`); webhook 120×/menit |
| Validasi input semua form, pesan ramah | ✅ | `BookingWizardTest`, `PackageBuilderTest` |
| Upload hanya admin & hanya gambar | ✅ | Semua `FileUpload` di admin memakai `->image()` + batas ukuran; tidak ada upload publik |
| Konten editor disanitasi saat ditampilkan | ✅ | `Str::sanitizeHtml()` (Symfony HtmlSanitizer) |
| Header keamanan (clickjacking, nosniff, referrer) | ✅ | `SecurityHeaders`; `test_header_keamanan_ada...` |
| `APP_DEBUG=false` & pesan error umum | ✅🔧 | Halaman error Indonesia (`test_halaman_error_ramah...`); `resort:cek-produksi` |
| Database tidak terbuka ke internet | 🔧 | MySQL `bind-address=127.0.0.1` / hosting |
| User database tanpa DROP/GRANT | 🔧 | `docs/DEPLOY.md` bagian 3; dicek otomatis oleh `resort:cek-produksi` |
| Backup harian terenkripsi ke penyimpanan terpisah | ✅🔧 | AES-256, jadwal 01:30 (`test_jadwal_backup...`); disk terpisah diatur saat deploy |
| Uji pemulihan backup minimal sebulan sekali | ✅📋 | Uji pertama 4 Okt 2026 berhasil (dump terbuka dengan password, gagal tanpa password); prosedur di `docs/DEPLOY.md` |
| `composer audit` & update keamanan bulanan | ✅📋 | 4 Okt 2026: 0 kerentanan (composer & npm) |

## Yang masih harus dilakukan saat go-live (F5)

1. Isi `.env` produksi dan jalankan `php artisan resort:cek-produksi` sampai tidak ada butir GAGAL.
2. Buat user database `lingkung_app` tanpa DROP/GRANT.
3. Arahkan backup ke penyimpanan terpisah (Cloudflare R2) dan isi email notifikasi.
4. Resort meninjau Kebijakan Privasi & Syarat Ketentuan (masa simpan data, refund).
5. Setiap staf memasang 2FA saat login pertama.
