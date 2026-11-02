<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\NeedsAttention;
use App\Filament\Widgets\ResortStatsOverview;
use App\Filament\Widgets\UpcomingArrivals;
use App\Support\BrandColors;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->path(config('resort.admin_path'))
            ->brandName('Lingkung Gunung')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/brand/ikon-512.png'))
            ->login()
            ->profile(isSimple: false)
            // 2FA via aplikasi authenticator (Google Authenticator, dll) + kode pemulihan.
            // Login admin dibatasi percobaannya oleh Filament (rate limit bawaan).
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('Lingkung Gunung')],
                isRequired: fn (): bool => (bool) config('resort.admin_requires_mfa'),
            )
            // Design tokens yang sama dengan website publik (app/Support/BrandColors.php)
            ->colors([
                'primary' => BrandColors::HUTAN,
                'gray' => BrandColors::KABUT,
                'warning' => BrandColors::EMAS,
                'success' => Color::Emerald,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])
            // Font self-host dari build Vite (@fonts), bukan dari CDN
            ->font('Plus Jakarta Sans', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render('@fonts'))
            // Foto latar halaman login lewat asset() (URL absolut di CSS salah alamat saat Vite dev berjalan)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => '<style>:root{--lg-login-foto:url("'.e(asset('images/resort/glamping-udara-1600.webp')).'")}</style>')
            // Dikelompokkan menurut pekerjaan staf (docs/AUDIT-UX.md)
            ->navigationGroups([
                'Operasional Harian',
                'Akomodasi & Harga',
                'Paket Rombongan',
                'Konten Website',
                'Laporan & Pengaturan',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Dasbor ringkas: perlu tindakan → hari ini & uang → kedatangan. Grafik tren ada di halaman Laporan.
            ->widgets([
                NeedsAttention::class,
                ResortStatsOverview::class,
                UpcomingArrivals::class,
            ])
            // Navigasi lebih cepat (tanpa muat ulang halaman penuh), sidebar bisa diciutkan, cari cepat Ctrl+K
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->unsavedChangesAlerts()
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Laporan & Pengaturan')
                    ->navigationLabel('Role & Hak Akses')
                    ->navigationSort(4),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
