<?php

namespace App\Http\Controllers\Akun;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\CustomerAccountService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman tulis/ubah ulasan untuk satu booking + target. Formnya komponen Livewire App\Livewire\ReviewForm. */
class ReviewController extends Controller
{
    public function __construct(private CustomerAccountService $accounts, private ReviewService $reviews) {}

    public function edit(Request $request, string $code, string $target): View|RedirectResponse
    {
        $account = $request->user('customer');
        // Booking orang lain / target yang tidak ada di booking = 404 (tidak membocorkan keberadaannya)
        $booking = $this->accounts->findOwnedBooking($account, $code) ?? abort(404);
        $reviewTarget = $this->reviews->findTarget($booking, $target) ?? abort(404);

        if ($problem = $this->reviews->eligibilityProblem($account, $booking)) {
            return redirect()->route('akun.booking.show', $booking->code)->with('error', $problem);
        }

        $review = Review::query()->where('booking_id', $booking->id)->where('customer_account_id', $account->id)
            ->forTarget($reviewTarget->type, $reviewTarget->id())->first();

        return view('akun.ulasan.form', ['booking' => $booking, 'target' => $reviewTarget, 'review' => $review]);
    }
}
