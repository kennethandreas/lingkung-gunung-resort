<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

class SnapPaymentTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        config(['services.midtrans.server_key' => 'SB-Mid-server-uji', 'services.midtrans.client_key' => 'SB-Mid-client-uji']);
        Mail::fake();
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'snap-token-123', 'redirect_url' => 'https://example.test'], 201)]);
    }

    public function test_tombol_bayar_membuat_transaksi_snap_sesuai_dp_dan_batas_hold(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(500000, 600000), '2026-10-12', '2026-10-14');

        $this->postJson(route('booking.pay', $booking))->assertOk()->assertJson(['token' => 'snap-token-123']);

        $payment = $booking->payments()->sole();
        $this->assertSame(250000, $payment->amount);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        Http::assertSent(function ($request) use ($booking) {
            return $request['transaction_details']['gross_amount'] === 250000
                && str_starts_with($request['transaction_details']['order_id'], $booking->code.'-DP-')
                && $request['expiry']['duration'] <= 60 // tidak boleh melewati hold stok
                && $request->hasHeader('Authorization');
        });
    }

    public function test_klik_bayar_berulang_memakai_token_yang_sama(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        $this->postJson(route('booking.pay', $booking))->assertOk();
        $this->postJson(route('booking.pay', $booking))->assertOk();

        $this->assertSame(1, $booking->payments()->count());
        Http::assertSentCount(1);
    }

    public function test_pelunasan_setelah_dp_menagih_sisa_pembayaran(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(500000, 600000), '2026-10-12', '2026-10-14');
        app(PaymentService::class)->recordManualPayment($booking, 250000, User::factory()->create());

        $this->postJson(route('booking.pay', $booking))->assertOk();

        $this->assertSame(750000, $booking->payments()->where('status', PaymentStatus::Pending)->sole()->amount);
    }

    public function test_hold_yang_sudah_habis_tidak_bisa_dibayar(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');
        $this->travel(61)->minutes();

        $this->postJson(route('booking.pay', $booking))->assertStatus(422)->assertJsonFragment(['message' => 'Booking ini tidak memiliki tagihan yang bisa dibayar saat ini.']);
    }

    public function test_tanpa_kunci_midtrans_muncul_pesan_ramah(): void
    {
        config(['services.midtrans.server_key' => null]);
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('belum diaktifkan');
        app(PaymentService::class)->startSnapPayment($booking);
    }

    public function test_midtrans_tidak_terjangkau_muncul_pesan_ramah_bukan_error_teknis(): void
    {
        Http::swap(new HttpFactory);
        Http::fake(['app.sandbox.midtrans.com/*' => Http::failedConnection('cURL error 60: SSL certificate problem')]);
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        $this->postJson(route('booking.pay', $booking))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Pembayaran online sedang mengalami gangguan. Silakan coba beberapa saat lagi atau hubungi kami via WhatsApp.'])
            ->assertDontSee('cURL');

        $this->assertSame(0, $booking->payments()->count());
    }
}
