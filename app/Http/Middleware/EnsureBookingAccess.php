<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\CustomerAccount;
use App\Services\CustomerAccountService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman /booking/{kode} (status, bayar, e-ticket) untuk booking milik akun:
 * tamu diminta masuk dulu, akun lain mendapat 404 (keberadaan booking tidak dibocorkan).
 * Booking tanpa akun (dibuat staf / B2B / lama) tetap terbuka lewat link kode acak.
 */
class EnsureBookingAccess
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function handle(Request $request, Closure $next): Response
    {
        $booking = $request->route('booking');

        if (! $booking instanceof Booking || $booking->customer_account_id === null) {
            return $next($request);
        }

        /** @var CustomerAccount|null $account */
        $account = Auth::guard('customer')->user();

        if (! $account) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi Anda sudah berakhir. Silakan muat ulang halaman dan masuk kembali.'], 401);
            }

            return redirect()->guest(route('akun.masuk'))
                ->with('notice', 'Booking ini tersimpan di akun pelanggan. Silakan masuk untuk melihat dan membayarnya.');
        }

        abort_unless($this->accounts->canAccessBookingPage($account, $booking), 404);

        if (! $account->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Verifikasi email Anda terlebih dahulu.'], 403);
            }

            return redirect()->guest(route('akun.verifikasi.notice'))
                ->with('notice', 'Verifikasi email Anda untuk melihat dan membayar booking.');
        }

        return $next($request);
    }
}
