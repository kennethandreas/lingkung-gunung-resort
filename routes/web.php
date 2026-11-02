<?php

use App\Http\Controllers\Admin\PaymentProofController;
use App\Http\Controllers\Akun\AuthController;
use App\Http\Controllers\Akun\DashboardController;
use App\Http\Controllers\Akun\EmailVerificationController;
use App\Http\Controllers\Akun\PasswordResetController;
use App\Http\Controllers\Akun\ProfileController;
use App\Http\Controllers\Akun\ReviewController as AkunReviewController;
use App\Http\Controllers\Akun\SavedRoomController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BookingStatusController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewPhotoController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StyleGuideController;
use App\Http\Middleware\EnsureBookingAccess;
use App\Models\Review;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website publik
|--------------------------------------------------------------------------
*/
Route::controller(PublicPageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/akomodasi', 'accommodations')->name('akomodasi.index');
    Route::get('/akomodasi/{unitType:slug}', 'accommodation')->name('akomodasi.show');
    Route::get('/play', 'play')->name('play');
    Route::get('/learn', 'learn')->name('learn');
    Route::get('/gather', 'gather')->name('gather');
    Route::get('/taste', 'taste')->name('taste');
    Route::get('/galeri', 'gallery')->name('galeri');
    Route::get('/rute-dan-akses', 'route')->name('rute');
    Route::get('/kontak', 'contact')->name('kontak');
    Route::get('/info/{page:slug}', 'page')->name('halaman');
});

// Artikel & kabar resort
Route::get('/artikel', [ArticleController::class, 'index'])->name('artikel.index');
Route::get('/artikel/{article:slug}', [ArticleController::class, 'show'])->name('artikel.show');

// Ulasan tamu (hanya yang sudah disetujui staf). Foto ulasan disimpan privat dan dikirim lewat rute yang memeriksa status.
Route::get('/ulasan', [ReviewController::class, 'index'])->name('ulasan.index');
Route::get('/ulasan/foto/{uuid}/{size}', ReviewPhotoController::class)
    ->whereUuid('uuid')->whereIn('size', array_keys(Review::PHOTO_SIZES))
    ->middleware('throttle:240,1,ulasan-foto')
    ->name('ulasan.foto');
Route::get('/ulasan/{jenis}/{slug}', [ReviewController::class, 'target'])->whereIn('jenis', ['akomodasi', 'aktivitas', 'paket'])->name('ulasan.target');

// Form kontak (honeypot + batas 5 pesan/10 menit per IP)
Route::post('/kontak', [InquiryController::class, 'store'])->middleware('throttle:5,10,kontak')->name('kontak.store');

// Booking B2C (wizard Livewire)
Route::view('/pesan', 'public.booking')->name('pesan');

// Status booking & e-ticket: URL memakai kode acak (bukan ID berurutan).
// Booking milik akun hanya untuk pemiliknya yang login (EnsureBookingAccess).
Route::controller(BookingStatusController::class)->prefix('booking/{booking:code}')->name('booking.')->middleware(EnsureBookingAccess::class)->group(function () {
    Route::get('/', 'show')->middleware('throttle:60,1,booking-show')->name('show');
    Route::post('/bayar', 'pay')->middleware('throttle:10,1,booking-bayar')->name('pay');
    Route::get('/e-ticket.pdf', 'eTicket')->middleware('throttle:20,1,booking-eticket')->name('eticket');
});

// Package Builder B2B + proposal (URL kode acak)
Route::view('/rancang-paket', 'public.package-builder')->name('rancang-paket');
Route::controller(ProposalController::class)->prefix('proposal/{quotation:code}')->name('proposal.')->middleware('throttle:60,1,proposal')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::get('/proposal.pdf', 'pdf')->name('pdf');
});

/*
|--------------------------------------------------------------------------
| Akun pelanggan (guard "customer"). Booking tanpa akun tetap bisa dilakukan.
|--------------------------------------------------------------------------
*/
Route::prefix('akun')->name('akun.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/daftar', [AuthController::class, 'showRegister'])->name('daftar');
        Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:5,1,akun-daftar')->name('daftar.store');
        Route::get('/masuk', [AuthController::class, 'showLogin'])->name('masuk');
        Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1,akun-masuk')->name('masuk.store');
        Route::get('/lupa-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/lupa-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1,akun-lupa-password')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1,akun-reset-password')->name('password.update');
    });

    // Tautan dari email: bertanda tangan & berbatas waktu, tidak wajib login
    Route::get('/verifikasi/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1,akun-verifikasi'])
        ->name('verifikasi.verify');

    Route::middleware('auth:customer')->group(function () {
        Route::post('/keluar', [AuthController::class, 'logout'])->name('keluar');
        Route::get('/verifikasi', [EmailVerificationController::class, 'notice'])->name('verifikasi.notice');
        Route::post('/verifikasi/kirim-ulang', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1,akun-verifikasi-kirim')->name('verifikasi.kirim');

        // Fitur akun hanya untuk email yang sudah diverifikasi
        Route::middleware('verified:akun.verifikasi.notice')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/booking', [DashboardController::class, 'bookings'])->name('booking.index');
            Route::post('/booking/tautkan', [DashboardController::class, 'claim'])->middleware('throttle:10,1,akun-tautkan')->name('booking.tautkan');
            Route::get('/booking/{code}', [DashboardController::class, 'show'])->name('booking.show');
            Route::get('/booking/{code}/ulasan/{target}', [AkunReviewController::class, 'edit'])
                ->where('target', 'resort|(akomodasi|aktivitas|paket)-[0-9]+')
                ->name('ulasan.edit');
            Route::get('/tersimpan', [SavedRoomController::class, 'index'])->name('tersimpan');
            Route::post('/tersimpan/{unitType:slug}', [SavedRoomController::class, 'toggle'])->middleware('throttle:30,1,akun-tersimpan')->name('tersimpan.toggle');
        });

        // Profil bisa dibuka sebelum verifikasi (mis. untuk membetulkan email yang salah ketik)
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profil');
        Route::put('/profil', [ProfileController::class, 'update'])->middleware('throttle:10,1,akun-profil')->name('profil.update');
        Route::put('/profil/password', [ProfileController::class, 'password'])->middleware('throttle:5,1,akun-password')->name('profil.password');
    });
});

// Notifikasi pembayaran Midtrans (dikecualikan dari CSRF di bootstrap/app.php)
Route::post('/webhook/midtrans', MidtransWebhookController::class)
    ->middleware('throttle:120,1,midtrans-webhook')
    ->name('webhook.midtrans');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Katalog design tokens & komponen UI (tidak tersedia di produksi)
if (! app()->isProduction()) {
    Route::get('/style-guide', StyleGuideController::class)->name('style-guide');
}

// Bukti transfer (file privat) - hanya staf yang login
Route::middleware('auth:web')
    ->get(config('resort.admin_path').'/bukti-transfer/{payment}', PaymentProofController::class)
    ->name('admin.payment-proof');
