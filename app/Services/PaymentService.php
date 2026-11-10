<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\BookingException;
use App\Mail\PaymentReceivedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pembayaran: transfer manual (dicatat admin) dan Midtrans Snap + webhook.
 * Status booking hanya berubah lewat pembayaran terverifikasi di kelas ini.
 */
class PaymentService
{
    public function __construct(private BookingService $bookings) {}

    /*
    |--------------------------------------------------------------------------
    | Transfer manual
    |--------------------------------------------------------------------------
    */

    /** Admin mencatat transfer manual ke rekening resort (dengan bukti transfer). */
    public function recordManualPayment(Booking $booking, int $amount, User $recordedBy, ?string $proofPath = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($booking, $amount, $recordedBy, $proofPath, $note) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $type = $this->determineType($booking, $amount);

            $payment = $booking->payments()->create([
                'type' => $type,
                'method' => PaymentMethod::Transfer,
                'amount' => $amount,
                'status' => PaymentStatus::Pending,
                'proof_path' => $proofPath,
                'note' => $note,
                'recorded_by' => $recordedBy->id,
            ]);

            $this->settle($payment);

            return $payment->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Midtrans Snap
    |--------------------------------------------------------------------------
    */

    /** Nominal & jenis tagihan berikutnya untuk booking ini (DP, lunas, atau pelunasan). */
    public function nextCharge(Booking $booking): ?array
    {
        if ($booking->status === BookingStatus::Pending) {
            if ($booking->hold_expires_at && $booking->hold_expires_at->isPast()) {
                return null;
            }

            return $booking->payment_option === 'full'
                ? ['type' => PaymentType::Full, 'amount' => $booking->total]
                : ['type' => PaymentType::Dp, 'amount' => $booking->dp_amount];
        }

        if ($booking->status === BookingStatus::DpPaid && $booking->outstanding() > 0) {
            return ['type' => PaymentType::Settlement, 'amount' => $booking->outstanding()];
        }

        return null;
    }

    /**
     * Buat (atau pakai ulang) transaksi Snap untuk tagihan berikutnya.
     *
     * @return array{token: string, payment: Payment}
     */
    public function startSnapPayment(Booking $booking): array
    {
        $this->assertMidtransConfigured();

        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()->with(['customer', 'items'])->lockForUpdate()->findOrFail($booking->id);
            $charge = $this->nextCharge($booking)
                ?? throw new BookingException('Booking ini tidak memiliki tagihan yang bisa dibayar saat ini.');

            // Pakai ulang token yang masih berlaku untuk nominal yang sama
            $existing = $booking->payments()
                ->where('method', PaymentMethod::Midtrans)
                ->where('status', PaymentStatus::Pending)
                ->where('amount', $charge['amount'])
                ->where('expires_at', '>', now()->addMinutes(2))
                ->whereNotNull('snap_token')
                ->latest('id')
                ->first();

            if ($existing) {
                return ['token' => $existing->snap_token, 'payment' => $existing];
            }

            // Batas waktu bayar tidak boleh melewati batas hold stok
            $minutes = $booking->status === BookingStatus::Pending && $booking->hold_expires_at
                ? max(1, (int) floor(now()->diffInMinutes($booking->hold_expires_at)))
                : 24 * 60;

            $orderId = $booking->code.'-'.strtoupper($charge['type']->value).'-'.Str::upper(Str::random(5));

            $payload = [
                'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $charge['amount']],
                'item_details' => [[
                    'id' => $charge['type']->value,
                    'price' => $charge['amount'],
                    'quantity' => 1,
                    'name' => Str::limit($charge['type']->getLabel().' booking '.$booking->code, 50, ''),
                ]],
                'customer_details' => array_filter([
                    'first_name' => Str::limit($booking->customer->name, 50, ''),
                    'email' => $booking->customer->email,
                    'phone' => $booking->customer->phone,
                ]),
                'expiry' => ['unit' => 'minute', 'duration' => $minutes],
                'callbacks' => ['finish' => route('booking.show', $booking)],
            ];

            try {
                $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
                    ->acceptJson()
                    ->timeout(20)
                    ->post(config('services.midtrans.snap_url'), $payload);
            } catch (ConnectionException $e) {
                // Server tidak bisa menjangkau Midtrans (internet, DNS, sertifikat SSL)
                Log::error('Midtrans Snap tidak terjangkau', ['order_id' => $orderId, 'error' => $e->getMessage()]);
                throw new BookingException('Pembayaran online sedang mengalami gangguan. Silakan coba beberapa saat lagi atau hubungi kami via WhatsApp.');
            }

            if (! $response->successful() || ! $response->json('token')) {
                Log::warning('Midtrans Snap gagal', ['order_id' => $orderId, 'status' => $response->status(), 'body' => $response->json()]);
                throw new BookingException('Pembayaran online sedang mengalami gangguan. Silakan coba beberapa saat lagi atau hubungi kami via WhatsApp.');
            }

            $payment = $booking->payments()->create([
                'type' => $charge['type'],
                'method' => PaymentMethod::Midtrans,
                'amount' => $charge['amount'],
                'gateway_order_id' => $orderId,
                'snap_token' => $response->json('token'),
                'expires_at' => now()->addMinutes($minutes),
                'status' => PaymentStatus::Pending,
            ]);

            return ['token' => $payment->snap_token, 'payment' => $payment];
        });
    }

    /**
     * Proses notifikasi (webhook) Midtrans.
     * Signature diverifikasi, order_id & nominal dicocokkan, status dicek ulang ke API Midtrans,
     * lalu diproses idempoten. Mengembalikan [kode HTTP, keterangan].
     *
     * @return array{0: int, 1: string}
     */
    public function handleNotification(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $signatureValid = $this->verifySignature($payload);

        $log = WebhookLog::query()->create([
            'order_id' => $orderId ?: null,
            'signature_valid' => $signatureValid,
            'payload' => $payload,
        ]);

        $finish = function (int $status, string $result) use ($log) {
            $log->update(['result' => $result, 'processed_at' => now()]);

            return [$status, $result];
        };

        if (! $signatureValid) {
            return $finish(403, 'Ditolak: signature tidak valid');
        }

        $payment = Payment::query()->where('gateway_order_id', $orderId)->first();
        if (! $payment) {
            return $finish(404, 'Ditolak: order_id tidak dikenal');
        }

        if (! $this->amountMatches($payload['gross_amount'] ?? null, $payment->amount)) {
            return $finish(422, 'Ditolak: nominal tidak cocok dengan tagihan');
        }

        // Jangan percaya isi notifikasi begitu saja: cek ulang status langsung ke Midtrans
        try {
            $status = $this->fetchStatus($orderId);
        } catch (Throwable $e) {
            Log::error('Cek status Midtrans gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return $finish(503, 'Gagal cek ulang status ke Midtrans, menunggu notifikasi ulang');
        }

        [$code, $result] = $this->applyGatewayStatus($payment, $status);

        return $finish($code, $result);
    }

    /**
     * Sinkronkan transaksi Midtrans yang masih menunggu langsung dari API Midtrans
     * (server ke server). Dipakai saat tamu kembali ke halaman booking setelah membayar,
     * sehingga status tetap benar walau webhook terlambat (atau di laptop/localhost
     * yang tidak bisa dijangkau webhook). Aturannya sama persis dengan webhook.
     */
    public function syncPendingPayments(Booking $booking): void
    {
        if (blank(config('services.midtrans.server_key'))) {
            return;
        }

        $pending = $booking->payments()
            ->where('method', PaymentMethod::Midtrans)
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('gateway_order_id')
            ->latest('id')
            ->take(3)
            ->get();

        foreach ($pending as $payment) {
            try {
                $status = $this->fetchStatus($payment->gateway_order_id);
            } catch (Throwable) {
                continue; // mis. 404: tamu belum memilih metode pembayaran di Snap
            }

            if (isset($status['transaction_status'])) {
                $this->applyGatewayStatus($payment, $status);
            }
        }
    }

    /**
     * Rekonsiliasi terjadwal: cek ke API Midtrans semua transaksi online yang masih "menunggu"
     * (maks. 48 jam terakhir). Menangkap pembayaran yang sukses tetapi webhook-nya gagal sampai
     * dan tamu tidak kembali ke website. Aturannya sama dengan webhook (applyGatewayStatus).
     *
     * @return array{checked: int, updated: int}
     */
    public function reconcilePending(int $limit = 50): array
    {
        if (blank(config('services.midtrans.server_key'))) {
            return ['checked' => 0, 'updated' => 0];
        }

        $pending = Payment::query()
            ->where('method', PaymentMethod::Midtrans)
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('gateway_order_id')
            ->where('created_at', '>=', now()->subHours(48))
            ->oldest('id')
            ->take($limit)
            ->get();

        $updated = 0;

        foreach ($pending as $payment) {
            try {
                $status = $this->fetchStatus($payment->gateway_order_id);
            } catch (Throwable) {
                continue; // 404 = tamu belum memilih metode bayar di Snap
            }

            if (isset($status['transaction_status'])) {
                $this->applyGatewayStatus($payment, $status);
                $updated += (int) ($payment->fresh()->status !== PaymentStatus::Pending);
            }
        }

        return ['checked' => $pending->count(), 'updated' => $updated];
    }

    /**
     * Terapkan status transaksi yang SUDAH didapat langsung dari API Midtrans.
     *
     * @return array{0: int, 1: string}
     */
    private function applyGatewayStatus(Payment $payment, array $status): array
    {
        $orderId = $payment->gateway_order_id;

        if (($status['order_id'] ?? null) !== $orderId || ! $this->amountMatches($status['gross_amount'] ?? null, $payment->amount)) {
            return [422, 'Ditolak: data status Midtrans tidak cocok'];
        }

        $transactionStatus = $status['transaction_status'] ?? null;
        $fraudStatus = $status['fraud_status'] ?? 'accept';

        $payment->forceFill([
            'gateway_transaction_id' => $status['transaction_id'] ?? $payment->gateway_transaction_id,
            'payment_channel' => $status['payment_type'] ?? $payment->payment_channel,
            'raw_payload' => $status,
        ])->save();

        if (in_array($transactionStatus, ['settlement', 'capture'], true) && $fraudStatus === 'accept') {
            if ($payment->status === PaymentStatus::Paid) {
                return [200, 'Sudah diproses sebelumnya (notifikasi ganda diabaikan)'];
            }

            try {
                $this->settle($payment);
            } catch (BookingException $e) {
                // Uang sudah masuk tetapi booking tidak bisa dikonfirmasi: tandai untuk refund manual
                $payment->update(['status' => PaymentStatus::Paid, 'verified_at' => now(), 'note' => 'PERLU TINDAKAN: '.$e->getMessage()]);
                $payment->booking->update(['internal_notes' => trim($payment->booking->internal_notes."\nPembayaran {$orderId} masuk tetapi booking tidak bisa dikonfirmasi: {$e->getMessage()} Mohon proses refund/penjadwalan ulang.")]);

                return [200, 'Dibayar, tetapi perlu tindakan manual: '.$e->getMessage()];
            }

            return [200, 'Pembayaran berhasil diverifikasi'];
        }

        if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
            if ($payment->status === PaymentStatus::Pending) {
                $payment->update(['status' => $transactionStatus === 'expire' ? PaymentStatus::Expired : PaymentStatus::Failed]);
            }

            return [200, "Status {$transactionStatus} dicatat"];
        }

        return [200, 'Status '.($transactionStatus ?? 'tidak diketahui').' dicatat, menunggu'];
    }

    public function verifySignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '' || ! isset($payload['signature_key'], $payload['order_id'], $payload['status_code'], $payload['gross_amount'])) {
            return false;
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey);

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    /** GET status transaksi langsung dari API Midtrans. */
    public function fetchStatus(string $orderId): array
    {
        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->timeout(15)
            ->get(config('services.midtrans.api_url').'/v2/'.rawurlencode($orderId).'/status');

        $response->throw();

        return $response->json();
    }

    /*
    |--------------------------------------------------------------------------
    | Inti: tandai pembayaran berhasil
    |--------------------------------------------------------------------------
    */

    /**
     * Tandai pembayaran berhasil dan perbarui booking.
     * Idempoten: memanggil dua kali untuk pembayaran yang sama tidak menambah nominal lagi.
     */
    public function settle(Payment $payment): void
    {
        $settled = DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === PaymentStatus::Paid) {
                return null;
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);

            // Pembayaran online yang masuk setelah hold habis: coba tahan ulang stok
            if ($booking->status === BookingStatus::Expired && $payment->method === PaymentMethod::Midtrans) {
                $this->bookings->reinstate($booking);
                $booking->refresh();
            }

            $this->determineType($booking, $payment->amount); // validasi ulang nominal

            if ($booking->status === BookingStatus::Pending) {
                $this->bookings->confirm($booking);
                $booking->refresh();
            }

            $paid = $booking->paid_amount + $payment->amount;
            $fullyPaid = $paid >= $booking->total;

            $booking->update([
                'paid_amount' => $paid,
                'status' => $fullyPaid ? BookingStatus::FullyPaid : BookingStatus::DpPaid,
                'fully_paid_at' => $fullyPaid ? now() : null,
            ]);

            $payment->update(['status' => PaymentStatus::Paid, 'verified_at' => now()]);

            return $payment;
        });

        // E-ticket / bukti bayar dikirim setelah transaksi tersimpan
        if ($settled) {
            $this->sendReceipt($settled);
        }
    }

    private function sendReceipt(Payment $payment): void
    {
        $booking = $payment->booking()->with('customer')->first();

        if (filled($booking->customer->email)) {
            Mail::to($booking->customer->email)->queue(new PaymentReceivedMail($booking, $payment));
        }
    }

    /** Tentukan jenis pembayaran dan tolak nominal yang tidak sesuai aturan. */
    private function determineType(Booking $booking, int $amount): PaymentType
    {
        if (in_array($booking->status, [BookingStatus::Expired, BookingStatus::Cancelled, BookingStatus::CheckedIn, BookingStatus::FullyPaid], true)) {
            throw new BookingException('Booking ini sudah '.strtolower($booking->status->getLabel()).', pembayaran tidak bisa dicatat.');
        }

        $outstanding = $booking->total - $booking->paid_amount;

        if ($amount <= 0) {
            throw new BookingException('Nominal pembayaran harus lebih dari Rp0.');
        }

        if ($amount > $outstanding) {
            throw new BookingException('Nominal melebihi sisa tagihan ('.self::rupiah($outstanding).').');
        }

        if ($booking->paid_amount > 0) {
            return PaymentType::Settlement;
        }

        if ($amount >= $booking->total) {
            return PaymentType::Full;
        }

        if ($booking->payment_option === 'full') {
            throw new BookingException('Booking ini harus dibayar lunas ('.self::rupiah($booking->total).').');
        }

        if ($amount < $booking->dp_amount) {
            throw new BookingException('Pembayaran pertama minimal DP '.self::rupiah($booking->dp_amount).'.');
        }

        return PaymentType::Dp;
    }

    private function amountMatches(mixed $grossAmount, int $expected): bool
    {
        return is_numeric($grossAmount) && (int) round((float) $grossAmount) === $expected && fmod((float) $grossAmount, 1.0) === 0.0;
    }

    private function assertMidtransConfigured(): void
    {
        if (blank(config('services.midtrans.server_key')) || blank(config('services.midtrans.client_key'))) {
            throw new BookingException('Pembayaran online belum diaktifkan. Silakan hubungi kami via WhatsApp untuk menyelesaikan pemesanan.');
        }
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
