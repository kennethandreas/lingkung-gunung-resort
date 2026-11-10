<?php

namespace Tests\Feature;

use App\Enums\DayType;
use App\Exceptions\BookingException;
use App\Models\CalendarDay;
use App\Models\Promo;
use App\Models\Setting;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/**
 * Perhitungan harga WD/WE (test wajib).
 * Kalender acuan Oktober 2026: Jumat 16, Sabtu 17, Minggu 18, Senin 19.
 */
class PricingServiceTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->pricing = app(PricingService::class);
    }

    private function date(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    public function test_malam_senin_sampai_kamis_memakai_tarif_weekday(): void
    {
        $type = $this->makeUnitType(670000, 815000);

        foreach (['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-18'] as $night) {
            $rate = $this->pricing->nightlyRate($type, $this->date($night));
            $this->assertSame(DayType::Weekday, $rate['day_type'], "Malam {$night} seharusnya weekday");
            $this->assertSame(670000, $rate['price']);
        }
    }

    public function test_malam_jumat_dan_sabtu_memakai_tarif_weekend(): void
    {
        $type = $this->makeUnitType(670000, 815000);

        foreach (['2026-10-16', '2026-10-17'] as $night) {
            $rate = $this->pricing->nightlyRate($type, $this->date($night));
            $this->assertSame(DayType::Weekend, $rate['day_type'], "Malam {$night} seharusnya weekend");
            $this->assertSame(815000, $rate['price']);
        }
    }

    public function test_total_menginap_campuran_wd_dan_we_dihitung_per_malam(): void
    {
        $type = $this->makeUnitType(670000, 815000);

        // Kamis 15 -> Minggu 18: Kamis (WD) + Jumat (WE) + Sabtu (WE)
        $quote = $this->pricing->quoteStay($type, $this->date('2026-10-15'), $this->date('2026-10-18'), 2);

        $this->assertCount(3, $quote['nights']);
        $this->assertSame(670000 + 815000 + 815000, $quote['per_unit']);
        $this->assertSame(2 * (670000 + 815000 + 815000), $quote['subtotal']);
    }

    public function test_aturan_weekend_bisa_diubah_dari_pengaturan(): void
    {
        $type = $this->makeUnitType(670000, 815000);
        Setting::put('weekend_nights', [6]); // hanya malam Sabtu

        $this->assertSame(DayType::Weekday, $this->pricing->nightlyRate($type, $this->date('2026-10-16'))['day_type']);
        $this->assertSame(DayType::Weekend, (new PricingService)->nightlyRate($type, $this->date('2026-10-17'))['day_type']);
    }

    public function test_hari_libur_di_kalender_menimpa_aturan_hari(): void
    {
        $type = $this->makeUnitType(670000, 815000);
        CalendarDay::create(['date' => '2026-10-13', 'day_type' => 'weekend', 'note' => 'Libur uji']);

        $rate = $this->pricing->nightlyRate($type, $this->date('2026-10-13'));

        $this->assertSame(DayType::Weekend, $rate['day_type']);
        $this->assertSame(815000, $rate['price']);
    }

    public function test_musim_ramai_tanpa_tarif_khusus_memakai_tarif_weekend(): void
    {
        $type = $this->makeUnitType(670000, 815000);
        CalendarDay::create(['date' => '2026-12-24', 'day_type' => 'high_season']);

        $this->assertSame(815000, $this->pricing->nightlyRate($type, $this->date('2026-12-24'))['price']);

        $type->rates()->create(['day_type' => 'high_season', 'price' => 950000, 'valid_from' => '2026-12-01', 'valid_to' => '2026-12-31']);
        $this->assertSame(950000, (new PricingService)->nightlyRate($type, $this->date('2026-12-24'))['price']);
    }

    public function test_harga_terbaru_dipakai_sesuai_masa_berlaku(): void
    {
        $type = $this->makeUnitType(670000, 815000);
        $type->rates()->create(['day_type' => 'weekday', 'price' => 700000, 'valid_from' => '2027-01-01']);

        $this->assertSame(670000, $this->pricing->nightlyRate($type, $this->date('2026-12-30'))['price']);
        $this->assertSame(700000, $this->pricing->nightlyRate($type, $this->date('2027-01-04'))['price']);
    }

    public function test_harga_belum_diatur_menghasilkan_pesan_ramah(): void
    {
        $type = $this->makeUnitType();
        $type->rates()->delete();

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('belum diatur');

        $this->pricing->nightlyRate($type, $this->date('2026-10-12'));
    }

    public function test_dp_25_persen_dibulatkan_ke_atas(): void
    {
        $this->assertSame(167500, $this->pricing->dpAmount(670000));
        $this->assertSame(250001, $this->pricing->dpAmount(1000001)); // 250000,25 -> 250001
    }

    public function test_promo_persen_dengan_batas_maksimal(): void
    {
        $type = $this->makeUnitType(1000000, 1000000);
        Promo::create(['code' => 'hemat10', 'name' => 'Hemat', 'type' => 'percent', 'value' => 10, 'max_discount' => 150000]);

        $quote = $this->pricing->quoteBooking([$type->id => 2], $this->date('2026-10-12'), $this->date('2026-10-13'), 'HEMAT10');

        $this->assertSame(2000000, $quote['subtotal']);
        $this->assertSame(150000, $quote['discount']); // 10% = 200rb, dibatasi 150rb
        $this->assertSame(1850000, $quote['total']);
        $this->assertSame(462500, $quote['dp_amount']);
    }

    public function test_promo_yang_kuotanya_habis_ditolak(): void
    {
        $type = $this->makeUnitType();
        Promo::create(['code' => 'HABIS', 'name' => 'Habis', 'type' => 'fixed', 'value' => 50000, 'quota' => 1, 'used' => 1]);

        $this->expectExceptionMessage('Kuota kode promo sudah habis');
        $this->pricing->quoteBooking([$type->id => 1], $this->date('2026-10-12'), $this->date('2026-10-13'), 'HABIS');
    }
}
