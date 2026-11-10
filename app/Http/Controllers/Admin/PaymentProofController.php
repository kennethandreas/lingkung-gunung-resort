<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Menampilkan bukti transfer (disimpan privat) hanya untuk staf yang berhak. */
class PaymentProofController extends Controller
{
    public function __invoke(Request $request, Payment $payment): StreamedResponse
    {
        // Selalu guard staf ("web"), bukan akun pelanggan
        $user = $request->user('web');

        abort_unless(
            $user?->canAccessPanel(Filament::getPanel('admin')) && $user->can('view', $payment->booking),
            403
        );

        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        // File unggahan ditampilkan tanpa izin menjalankan script (pertahanan berlapis bila ada file berbahaya lolos)
        return Storage::disk('local')->response($payment->proof_path, null, [
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
