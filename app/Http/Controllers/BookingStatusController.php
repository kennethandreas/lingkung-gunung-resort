<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Services\PaymentService;
use App\Support\ETicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Halaman publik status booking (URL memakai kode acak, bukan ID) + tombol bayar + e-ticket.
 * Hanya menampilkan data minimum.
 */
class BookingStatusController extends Controller
{
    public function show(Booking $booking, PaymentService $payments): View
    {
        // Kembali dari Midtrans: tanyakan status langsung ke Midtrans (tidak menunggu webhook)
        if (request()->filled('status') || request()->filled('order_id')) {
            $payments->syncPendingPayments($booking);
            $booking->refresh();
        }

        $booking->load(['customer', 'items', 'payments' => fn ($q) => $q->latest()]);

        return view('public.booking-status', [
            'booking' => $booking,
            'charge' => $payments->nextCharge($booking),
            'hasETicket' => $booking->status->isConfirmed(),
            'qr' => $booking->status->isConfirmed() ? ETicket::qrSvg($booking, 200) : null,
        ]);
    }

    /** Buat/pakai ulang token Snap lalu kembalikan ke browser (dipanggil tombol "Bayar"). */
    public function pay(Booking $booking, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->startSnapPayment($booking);
        } catch (BookingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['token' => $result['token']]);
    }

    public function eTicket(Booking $booking): Response
    {
        abort_unless($booking->status->isConfirmed(), 404);

        return response(ETicket::pdf($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.ETicket::filename($booking).'"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
