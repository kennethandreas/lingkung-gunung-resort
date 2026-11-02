<?php

namespace App\Support;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * E-ticket: QR untuk check-in + PDF.
 * QR berisi tautan halaman booking di admin panel; staf FO memindai dengan kamera HP,
 * login, lalu menekan "Check-in".
 */
class ETicket
{
    public static function checkInUrl(Booking $booking): string
    {
        return BookingResource::getUrl('view', ['record' => $booking], panel: 'admin');
    }

    /** QR dalam format SVG (tanpa ekstensi imagick). */
    public static function qrSvg(Booking $booking, int $size = 220): string
    {
        return (string) @QrCode::format('svg')->size($size)->margin(1)->errorCorrection('M')->generate(self::checkInUrl($booking));
    }

    public static function qrDataUri(Booking $booking, int $size = 220): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::qrSvg($booking, $size));
    }

    public static function pdf(Booking $booking): string
    {
        $booking->loadMissing(['customer', 'items']);

        return Pdf::loadView('pdf.e-ticket', [
            'booking' => $booking,
            'qr' => self::qrDataUri($booking, 240),
        ])->setOption('isFontSubsettingEnabled', true)->setPaper('a5')->output(); // subset font: PDF kecil untuk lampiran email
    }

    public static function filename(Booking $booking): string
    {
        return "e-ticket-{$booking->code}.pdf";
    }
}
