<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\DailyInventory;
use App\Models\Promo;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00'); // Senin
    }

    private function inventory(int $typeId, string $date): DailyInventory
    {
        return DailyInventory::query()->where('unit_type_id', $typeId)->whereDate('date', $date)->firstOrFail();
    }

    public function test_hold_menahan_stok_dan_menghitung_harga_di_server(): void
    {
        $type = $this->makeUnitType(670000, 815000);

        $booking = $this->holdBooking($type, '2026-10-15', '2026-10-17', 1, ['lines' => [$type->id => 1], 'total' => 1]);

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(670000 + 815000, $booking->total);          // bukan angka dari input
        $this->assertSame((int) ceil(1485000 * 0.25), $booking->dp_amount);
        $this->assertSame('2026-10-12', $booking->due_date->toDateString()); // H-3
        $this->assertTrue($booking->hold_expires_at->equalTo(now()->addMinutes(60)));
        $this->assertMatchesRegularExpression('/^LG[A-Z2-9]{8}$/', $booking->code);
        $this->assertSame(1, $this->inventory($type->id, '2026-10-15')->held);
        $this->assertSame(1, $this->inventory($type->id, '2026-10-16')->held);
        $this->assertSame(0, DailyInventory::query()->whereDate('date', '2026-10-17')->sum('held')); // malam check-out tidak dihitung
    }

    public function test_tidak_bisa_memesan_melebihi_stok(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 2]);
        $this->holdBooking($type, '2026-10-12', '2026-10-14', 2, ['guests' => 4]);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('tidak tersedia');
        $this->holdBooking($type, '2026-10-13', '2026-10-15', 1);
    }

    public function test_gagal_satu_tanggal_membatalkan_seluruh_pemesanan(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 1]);
        $this->holdBooking($type, '2026-10-14', '2026-10-15');

        try {
            $this->holdBooking($type, '2026-10-12', '2026-10-16');
            $this->fail('Seharusnya gagal karena 14 Oktober penuh');
        } catch (BookingException) {
            // tanggal lain tidak boleh ikut tertahan
            $this->assertSame(0, $this->inventory($type->id, '2026-10-12')->held);
            $this->assertSame(0, $this->inventory($type->id, '2026-10-13')->held);
        }
    }

    public function test_tanggal_yang_diblokir_tidak_bisa_dipesan(): void
    {
        $type = $this->makeUnitType();
        app(BookingService::class)->setBlocked($type, CarbonImmutable::parse('2026-10-20'), CarbonImmutable::parse('2026-10-21'), true, 'Perawatan');

        $this->expectExceptionMessage('tidak tersedia');
        $this->holdBooking($type, '2026-10-19', '2026-10-21');
    }

    public function test_jumlah_tamu_tidak_boleh_melebihi_kapasitas(): void
    {
        $type = $this->makeUnitType(attributes: ['capacity_pax' => 4]);

        $this->expectExceptionMessage('melebihi kapasitas');
        $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['guests' => 5]);
    }

    public function test_booking_kurang_dari_h3_wajib_lunas(): void
    {
        $type = $this->makeUnitType();

        try {
            $this->holdBooking($type, '2026-10-07', '2026-10-08'); // H-2, DP
            $this->fail('DP seharusnya ditolak');
        } catch (BookingException $e) {
            $this->assertStringContainsString('harus dibayar lunas', $e->getMessage());
        }

        $booking = $this->holdBooking($type, '2026-10-07', '2026-10-08', 1, ['payment_option' => 'full']);
        $this->assertSame($booking->total, $booking->dp_amount);
        $this->assertNull($booking->due_date);
    }

    public function test_tanggal_check_in_lampau_ditolak_untuk_website(): void
    {
        $type = $this->makeUnitType();

        $this->expectExceptionMessage('masa lalu');
        $this->holdBooking($type, '2026-10-01', '2026-10-02', 1, ['payment_option' => 'full']);
    }

    public function test_hold_yang_lewat_60_menit_dilepas_dan_kedaluwarsa(): void
    {
        $type = $this->makeUnitType();
        $promo = Promo::create(['code' => 'UJI', 'name' => 'Uji', 'type' => 'fixed', 'value' => 10000, 'quota' => 5]);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['promo_code' => 'UJI']);
        $this->assertSame(1, $promo->fresh()->used);

        $this->travel(59)->minutes();
        $this->assertSame(0, app(BookingService::class)->expireOverdueHolds());

        $this->travel(2)->minutes();
        $this->assertSame(1, app(BookingService::class)->expireOverdueHolds());

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
        $this->assertSame(0, $this->inventory($type->id, '2026-10-12')->held);
        $this->assertSame(0, $promo->fresh()->used);
    }

    public function test_pembayaran_dp_memindahkan_stok_ke_booked_lalu_pelunasan(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $admin = User::factory()->create();
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-14'); // 2 malam WD = 1.000.000
        $payments = app(PaymentService::class);

        $payments->recordManualPayment($booking, 250000, $admin);
        $booking->refresh();

        $this->assertSame(BookingStatus::DpPaid, $booking->status);
        $this->assertSame(250000, $booking->paid_amount);
        $this->assertNull($booking->hold_expires_at);
        $this->assertSame(0, $this->inventory($type->id, '2026-10-12')->held);
        $this->assertSame(1, $this->inventory($type->id, '2026-10-12')->booked);

        $payments->recordManualPayment($booking, 750000, $admin);
        $booking->refresh();

        $this->assertSame(BookingStatus::FullyPaid, $booking->status);
        $this->assertSame(1000000, $booking->paid_amount);
        $this->assertSame(1, $this->inventory($type->id, '2026-10-12')->booked); // tidak dobel
    }

    public function test_pembayaran_pertama_kurang_dari_dp_ditolak(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-14');

        $this->expectExceptionMessage('minimal DP');
        app(PaymentService::class)->recordManualPayment($booking, 100000, User::factory()->create());
    }

    public function test_pembayaran_melebihi_sisa_tagihan_ditolak(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');

        $this->expectExceptionMessage('melebihi sisa tagihan');
        app(PaymentService::class)->recordManualPayment($booking, 600000, User::factory()->create());
    }

    public function test_settle_idempoten_tidak_menambah_nominal_dua_kali(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');
        $payment = app(PaymentService::class)->recordManualPayment($booking, 500000, User::factory()->create());

        app(PaymentService::class)->settle($payment); // dipanggil ulang (mis. notifikasi ganda)

        $this->assertSame(500000, $booking->fresh()->paid_amount);
        $this->assertSame(1, $booking->payments()->count());
        $this->assertSame(1, $this->inventory($type->id, '2026-10-12')->booked);
    }

    public function test_pembayaran_untuk_booking_kedaluwarsa_ditolak(): void
    {
        $type = $this->makeUnitType();
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();

        $this->expectExceptionMessage('kedaluwarsa');
        app(PaymentService::class)->recordManualPayment($booking, $booking->total, User::factory()->create());
    }

    public function test_batal_setelah_dp_mengembalikan_stok_booked(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');
        app(PaymentService::class)->recordManualPayment($booking, 125000, User::factory()->create());

        app(BookingService::class)->cancel($booking, 'Tamu berhalangan');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(0, $this->inventory($type->id, '2026-10-12')->booked);
    }

    public function test_check_in_hanya_untuk_booking_lunas_dan_menugaskan_unit(): void
    {
        $type = $this->makeUnitType(500000, 600000);
        $unit = $type->units()->create(['code' => 'GL-01']);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-13');
        $service = app(BookingService::class);

        try {
            $service->checkIn($booking);
            $this->fail('Check-in seharusnya ditolak sebelum lunas');
        } catch (BookingException $e) {
            $this->assertStringContainsString('sudah lunas', $e->getMessage());
        }

        app(PaymentService::class)->recordManualPayment($booking, 500000, User::factory()->create());
        $item = $booking->items()->first();
        $service->checkIn($booking, [$item->id => [$unit->id]]);

        $this->assertSame(BookingStatus::CheckedIn, $booking->fresh()->status);
        $this->assertSame(['GL-01'], $item->units()->pluck('code')->all());
    }

    public function test_input_manual_admin_memakai_aturan_yang_sama(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 1]);
        $this->holdBooking($type, '2026-10-12', '2026-10-13');

        $this->expectExceptionMessage('tidak tersedia');
        $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['channel' => BookingChannel::Manual]);
    }

    public function test_tipe_yang_tidak_dibuka_online_hanya_bisa_dipesan_manual(): void
    {
        $type = $this->makeUnitType(attributes: ['is_bookable_online' => false]);

        $manual = $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['channel' => BookingChannel::Manual]);
        $this->assertSame(BookingChannel::Manual, $manual->channel);

        $this->expectExceptionMessage('tidak bisa dipesan');
        $this->holdBooking($type, '2026-10-14', '2026-10-15');
    }
}
