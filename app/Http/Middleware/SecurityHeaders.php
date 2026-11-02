<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar untuk semua respons (dokumen teknis bab 7).
 * CSP penuh sengaja tidak dipasang karena Midtrans Snap memuat skrip dari banyak domain;
 * perlindungan clickjacking memakai frame-ancestors + X-Frame-Options.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(self "https://app.midtrans.com" "https://app.sandbox.midtrans.com")',
            'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; object-src 'none'",
            'Cross-Origin-Opener-Policy' => 'same-origin-allow-popups',
        ];

        // HSTS hanya bila sudah HTTPS (produksi di belakang Cloudflare)
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        $response->headers->remove('X-Powered-By');

        // Header versi PHP dikirim langsung oleh PHP (expose_php=On), bukan oleh objek respons
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
