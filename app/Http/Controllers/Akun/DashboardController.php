<?php

namespace App\Http\Controllers\Akun;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Services\CustomerAccountService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Dashboard, riwayat booking, detail booking, dan penautan booking lama. */
class DashboardController extends Controller
{
    public function __construct(private CustomerAccountService $accounts, private PaymentService $payments, private ReviewService $reviews) {}

    public function index(Request $request): View
    {
        $account = $request->user('customer');
        $groups = $this->accounts->groupedBookings($account);

        $next = $groups['upcoming']->first();

        return view('akun.dashboard', [
            'account' => $account,
            'next' => $next,
            'nextCharge' => $next ? $this->payments->nextCharge($next) : null,
            // Booking akan datang yang masih punya tagihan (DP/lunas/pelunasan): ditampilkan paling atas
            'actionNeeded' => $groups['upcoming']->filter(fn ($booking) => $this->payments->nextCharge($booking) !== null)->values(),
            'upcomingCount' => $groups['upcoming']->count(),
            'recent' => $account->bookings()->with('items')->latest()->take(5)->get(),
            'savedCount' => $account->savedUnitTypes()->count(),
        ]);
    }

    public function bookings(Request $request): View
    {
        return view('akun.booking.index', $this->accounts->groupedBookings($request->user('customer')));
    }

    public function show(Request $request, string $code): View
    {
        // Hanya booking milik akun ini; booking orang lain = 404 (tidak membocorkan keberadaannya)
        $booking = $this->accounts->findOwnedBooking($request->user('customer'), $code) ?? abort(404);

        // Tagihan berikutnya dihitung sama seperti halaman status booking publik (null bila hold habis / lunas)
        return view('akun.booking.show', [
            'booking' => $booking,
            'charge' => $this->payments->nextCharge($booking),
            'reviewOptions' => $this->reviews->opportunities($request->user('customer'), $booking),
        ]);
    }

    public function claim(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('claim', [
            'code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Masukkan kode booking (mis. LGAB12CD34).',
            'phone.required' => 'Masukkan nomor WhatsApp yang dipakai saat memesan.',
        ]);

        $account = $request->user('customer');
        $key = 'akun-tautkan:'.$account->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Silakan coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.'], 'claim')->withInput();
        }

        try {
            $booking = $this->accounts->claimBooking($account, $data['code'], $data['phone']);
        } catch (BookingException $e) {
            RateLimiter::hit($key, 600);

            return back()->withErrors(['code' => $e->getMessage()], 'claim')->withInput();
        }

        return redirect()->route('akun.booking.show', $booking->code)->with('status', "Booking {$booking->code} berhasil ditambahkan ke akun Anda.");
    }
}
