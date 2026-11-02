<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policy role Shield berada di luar auto-discovery Laravel
        Gate::policy(Role::class, RolePolicy::class);

        // Foto konten (galeri, tipe unit, program, beranda) harus di disk publik agar tampil di website.
        // Tanpa ini Filament memakai FILESYSTEM_DISK=local (privat) dan foto tidak bisa dibuka browser.
        SpatieMediaLibraryFileUpload::configureUsing(
            fn (SpatieMediaLibraryFileUpload $upload) => $upload->disk(config('media-library.disk_name'))->visibility('public'),
        );

        // Di belakang Cloudflare/proxy hosting: percayai proxy agar HTTPS & IP tamu terbaca benar
        if ($proxies = config('resort.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Produksi: semua URL yang dibuat aplikasi memakai HTTPS
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            // Tautan (reset password, verifikasi, e-ticket) selalu memakai APP_URL, bukan header Host
            // dari pengunjung (mencegah "password reset poisoning" lewat Host palsu)
            URL::forceRootUrl(config('app.url'));
        }
    }
}
