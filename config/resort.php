<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alamat Admin Panel
    |--------------------------------------------------------------------------
    |
    | Sengaja tidak memakai "/admin" bawaan (lihat dokumen teknis bab 7).
    | Ubah lewat ADMIN_PATH di file .env.
    |
    */

    'admin_path' => env('ADMIN_PATH', 'kelola'),

    /*
    |--------------------------------------------------------------------------
    | Wajib 2FA untuk login admin
    |--------------------------------------------------------------------------
    */

    'admin_requires_mfa' => (bool) env('ADMIN_REQUIRE_MFA', true),

    // Akun pelanggan wajib verifikasi email sebelum memakai fitur akun.
    // false = dimatikan sementara (development, belum ada pengirim email); WAJIB true di produksi.
    'customer_email_verification' => (bool) env('CUSTOMER_EMAIL_VERIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Proxy tepercaya (Cloudflare / load balancer hosting)
    |--------------------------------------------------------------------------
    |
    | Isi "*" bila server hanya bisa diakses lewat Cloudflare/proxy hosting,
    | atau daftar IP dipisah koma. Kosongkan di laptop.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Proses antrean lewat cron (shared hosting)
    |--------------------------------------------------------------------------
    |
    | true bila hosting tidak bisa menjalankan "php artisan queue:work" terus-menerus:
    | antrean (email e-ticket, proposal, pengingat) diproses oleh schedule:run tiap menit.
    |
    */

    'queue_via_scheduler' => (bool) env('QUEUE_VIA_SCHEDULER', false),

    // Anti penimbunan stok: maks. booking online "Menunggu Pembayaran" (hold aktif) per akun pelanggan sekaligus.
    'max_pending_holds_per_account' => (int) env('MAX_PENDING_HOLDS_PER_ACCOUNT', 3),

    /*
    |--------------------------------------------------------------------------
    | Ulasan tamu
    |--------------------------------------------------------------------------
    |
    | Foto ulasan disimpan privat (disk "ulasan") dan hanya dikirim lewat rute yang memeriksa status ulasan.
    |
    */

    'reviews' => [
        'max_photos' => (int) env('REVIEW_MAX_PHOTOS', 4),
        'photo_max_kb' => (int) env('REVIEW_PHOTO_MAX_KB', 5120),
        'comment_max' => (int) env('REVIEW_COMMENT_MAX', 2000),
        'per_page' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nilai default pengaturan
    |--------------------------------------------------------------------------
    |
    | Dipakai bila belum diubah di admin panel (menu Pengaturan, tabel settings).
    | Jangan baca langsung dari sini; pakai App\Models\Setting::get('kunci').
    |
    */

    'defaults' => [
        // Malam yang dihitung tarif weekend. 0=Minggu ... 5=Jumat, 6=Sabtu.
        // TODO: konfirmasi ke resort (malam Jumat+Sabtu, atau Sabtu saja?)
        'weekend_nights' => [5, 6],

        'dp_percent' => 25,
        'hold_minutes' => 60,
        'settlement_days_before' => 3,   // pelunasan paling lambat H-3
        'booking_window_days' => 365,    // tamu bisa memesan sampai 1 tahun ke depan

        'contact_whatsapp' => '628111516727',      // 0811-1516-727
        'contact_phone' => '0251-829-1453',
        'contact_instagram' => 'LingkungGunung',
        // Sumber: elji.id (situs resmi, dicek 25 Okt 2026). Lihat docs/SUMBER-DATA.md
        'contact_email' => 'marketing@elji.id',
        'contact_website' => 'https://elji.id',
        'contact_facebook' => 'LingkungGunungBogor',
        'contact_youtube' => '@lingkunggunungadventurecam7274',
        'address' => 'Jalan Akses Lingkung Gunung No.01, Kp. Baru, Cimande, Kec. Caringin, Kabupaten Bogor, Jawa Barat 16730',
        'google_maps_query' => 'Lingkung Gunung Resort Bogor',
        'waze_query' => 'Lingkung Gunung',

        'bank_name' => 'Bank Mandiri',
        'bank_account_name' => 'PT. Lingkung Gunung Sejahtera',
        'bank_account_number' => '133-00-1868-7988',
    ],

];
