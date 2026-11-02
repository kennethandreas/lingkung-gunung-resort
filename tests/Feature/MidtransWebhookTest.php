<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Mail\PaymentReceivedMail;
use App\Models\Booking;
use App\Models\DailyInventory;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/**
 * Test wajib webhook Midtrans: signature salah ditolak, notifikasi ganda tidak dobel,
 * nominal & order_id dicocokkan, status dicek ulang ke API Midtrans.
 */
class MidtransWebhookTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-uji-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        config([
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.client_key' => 'SB-Mid-client-uji',
        ]);
        Mail::fake();
    }

    /** Booking Pending (1 malam WD Rp500.000, DP Rp125.000) + transaksi Midtrans menunggu. */
    private function pendingMidtransPayment(int $amount = 125000, string $type = 'dp'): Payment
    {
        $booking = $this->holdBooking($this->makeUnitType(500000, 600000, ['total_units' => 1]), '2026-10-12', '2026-10-13');

        return $booking->payments()->create([
            'type' => PaymentType::from($type),
            'method' => PaymentMethod::Midtrans,
            'amount' => $amount,
            'gateway_order_id' => $booking->code.'-DP-ABCDE',
            'snap_token' => 'token-uji',
            'expires_at' => now()->addHour(),
            'status' => PaymentStatus::Pending,
        ]);
    }

    private function notification(Payment $payment, string $status = 'settlement', ?string $gross = null, ?string $signature = null): array
    {
        $gross ??= number_format($payment->amount, 2, '.', '');
        $payload = [
            'order_id' => $payment->gateway_order_id,
            'status_code' => $status === 'settlement' ? '200' : '201',
            'gross_amount' => $gross,
            'transaction_status' => $status,
            'transaction_id' => 'trx-123',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = $signature ?? hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::SERVER_KEY);

        return $payload;
    }

    /** Palsukan jawaban API "cek status" Midtrans. */
    private function fakeStatusApi(Payment $payment, string $status = 'settlement', ?int $gross = null): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/v2/*' => Http::response([
                'order_id' => $payment->gateway_order_id,
                'transaction_status' => $status,
                'gross_amount' => number_format($gross ?? $payment->amount, 2, '.', ''),
                'transaction_id' => 'trx-123',
                'payment_type' => 'qris',
                'fraud_status' => 'accept',
                'status_code' => '200',
            ]),
        ]);
    }

    public function test_signature_salah_ditolak_dan_booking_tidak_berubah(): void
    {
        $payment = $this->pendingMidtransPayment();
        Http::fake();

        $this->postJson('/webhook/midtrans', $this->notification($payment, signature: str_repeat('0', 128)))
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::Pending, $payment->booking->fresh()->status);
        $this->assertFalse(WebhookLog::query()->sole()->signature_valid);
        Http::assertNothingSent(); // tidak sampai cek status ke Midtrans
    }

    public function test_notifikasi_valid_mengonfirmasi_booking_dan_mengirim_e_ticket(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment);

        $this->postJson('/webhook/midtrans', $this->notification($payment))->assertOk();

        $booking = $payment->booking->fresh();
        $this->assertSame(BookingStatus::DpPaid, $booking->status);
        $this->assertSame(125000, $booking->paid_amount);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame('qris', $payment->fresh()->payment_channel);
        $this->assertSame(1, DailyInventory::query()->whereDate('date', '2026-10-12')->value('booked'));
        Mail::assertQueued(PaymentReceivedMail::class, fn ($mail) => $mail->booking->is($booking));
    }

    public function test_notifikasi_ganda_tidak_menambah_pembayaran_dua_kali(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment);
        $payload = $this->notification($payment);

        $this->postJson('/webhook/midtrans', $payload)->assertOk();
        $this->postJson('/webhook/midtrans', $payload)->assertOk();
        $this->postJson('/webhook/midtrans', $payload)->assertOk();

        $booking = $payment->booking->fresh();
        $this->assertSame(125000, $booking->paid_amount);
        $this->assertSame(1, $booking->payments()->where('status', PaymentStatus::Paid)->count());
        $this->assertSame(1, DailyInventory::query()->whereDate('date', '2026-10-12')->value('booked'));
        $this->assertSame(3, WebhookLog::query()->count());
        $this->assertStringContainsString('notifikasi ganda', WebhookLog::query()->latest('id')->first()->result);
        Mail::assertQueued(PaymentReceivedMail::class, 1);
    }

    public function test_nominal_tidak_cocok_ditolak(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment);

        $this->postJson('/webhook/midtrans', $this->notification($payment, gross: '1000.00'))->assertStatus(422);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, $payment->booking->fresh()->paid_amount);
    }

    public function test_order_id_tidak_dikenal_ditolak(): void
    {
        $payment = $this->pendingMidtransPayment();
        $payload = $this->notification($payment);
        $payload['order_id'] = 'LGPALSU-DP-XXXXX';
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::SERVER_KEY);

        $this->postJson('/webhook/midtrans', $payload)->assertNotFound();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_status_dicek_ulang_ke_midtrans_sebelum_booking_diubah(): void
    {
        // Notifikasi mengaku "settlement", tetapi API Midtrans bilang masih "pending"
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment, status: 'pending');

        $this->postJson('/webhook/midtrans', $this->notification($payment))->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::Pending, $payment->booking->fresh()->status);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/'.$payment->gateway_order_id.'/status'));
    }

    public function test_transaksi_kedaluwarsa_dicatat_tanpa_mengubah_booking(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment, status: 'expire');

        $this->postJson('/webhook/midtrans', $this->notification($payment, status: 'expire'))->assertOk();

        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(BookingStatus::Pending, $payment->booking->fresh()->status);
    }

    public function test_pembayaran_terlambat_setelah_hold_habis_menahan_ulang_stok_bila_masih_ada(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();
        $this->assertSame(BookingStatus::Expired, $payment->booking->fresh()->status);
        $this->fakeStatusApi($payment);

        $this->postJson('/webhook/midtrans', $this->notification($payment))->assertOk();

        $this->assertSame(BookingStatus::DpPaid, $payment->booking->fresh()->status);
        $this->assertSame(1, DailyInventory::query()->whereDate('date', '2026-10-12')->value('booked'));
    }

    public function test_pembayaran_terlambat_saat_stok_sudah_terjual_ditandai_perlu_refund_tanpa_overbooking(): void
    {
        $payment = $this->pendingMidtransPayment();
        $type = $payment->booking->items()->first()->unitType;
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();

        // Tamu lain mengambil unit terakhir
        $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['customer' => ['name' => 'Tamu Lain', 'phone' => '081299990000']]);
        $this->fakeStatusApi($payment);

        $this->postJson('/webhook/midtrans', $this->notification($payment))->assertOk();

        $booking = $payment->booking->fresh();
        $this->assertSame(BookingStatus::Expired, $booking->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertStringContainsString('PERLU TINDAKAN', $payment->fresh()->note);
        $row = DailyInventory::query()->where('unit_type_id', $type->id)->whereDate('date', '2026-10-12')->first();
        $this->assertLessThanOrEqual($row->capacity, $row->booked + $row->held);
    }

    public function test_kembali_dari_snap_menyinkronkan_status_langsung_dari_midtrans_tanpa_webhook(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment);

        $this->get(route('booking.show', $payment->booking).'?order_id='.$payment->gateway_order_id.'&transaction_status=settlement')
            ->assertOk()
            ->assertSee('DP sudah diterima');

        $this->assertSame(BookingStatus::DpPaid, $payment->booking->fresh()->status);
        $this->assertSame(0, WebhookLog::query()->count());
    }

    public function test_parameter_url_palsu_tidak_bisa_mengubah_status_tanpa_konfirmasi_midtrans(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment, status: 'pending'); // Midtrans: belum dibayar

        $this->get(route('booking.show', $payment->booking).'?status=berhasil&transaction_status=settlement')->assertOk();

        $this->assertSame(BookingStatus::Pending, $payment->booking->fresh()->status);
    }

    public function test_webhook_tidak_butuh_token_csrf(): void
    {
        $payment = $this->pendingMidtransPayment();
        $this->fakeStatusApi($payment);

        $this->post('/webhook/midtrans', $this->notification($payment))->assertOk();
        $this->assertSame(1, Booking::query()->where('status', BookingStatus::DpPaid)->count());
    }
}
