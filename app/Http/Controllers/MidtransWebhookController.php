<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Notifikasi pembayaran dari Midtrans (HTTP Notification URL). Semua logika ada di PaymentService. */
class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        [$status, $message] = $payments->handleNotification($request->json()->all() ?: $request->all());

        return response()->json(['message' => $message], $status);
    }
}
