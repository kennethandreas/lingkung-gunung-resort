<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Halaman akun pelanggan → login pelanggan; selain itu (staf) → login admin panel
        $middleware->redirectGuestsTo(function (Request $request) {
            if (! $request->routeIs('akun.*')) {
                return route('filament.admin.auth.login');
            }

            if (! $request->expectsJson()) {
                $request->session()->flash('notice', 'Silakan masuk terlebih dahulu untuk melanjutkan.');
            }

            return route('akun.masuk');
        });

        // Pelanggan yang sudah login dan membuka halaman masuk/daftar → dashboard akun
        $middleware->redirectUsersTo(fn () => route('akun.dashboard'));

        // Midtrans memanggil webhook dari servernya (tanpa token CSRF); keamanannya lewat verifikasi signature
        $middleware->preventRequestForgery(except: ['webhook/midtrans']);

        // Header keamanan untuk semua respons (website, admin, webhook)
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
