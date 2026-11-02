<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Filament\Pages\Reports;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;
use ZipArchive;

class ReportTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        Mail::fake();
    }

    /** Dua booking terkonfirmasi (web DP + manual lunas) dan satu booking yang kedaluwarsa. */
    private function seedBookings(): array
    {
        $type = $this->makeUnitType(500000, 600000, ['total_units' => 2]);
        $admin = User::factory()->create();
        $payments = app(PaymentService::class);

        $web = $this->holdBooking($type, '2026-10-12', '2026-10-14'); // 2 malam WD = 1.000.000
        $payments->recordManualPayment($web, 250000, $admin);

        $manual = $this->holdBooking($type, '2026-10-12', '2026-10-13', 1, ['channel' => BookingChannel::Manual, 'customer' => ['name' => 'Walk-in', 'phone' => '081299990000']]);
        $payments->recordManualPayment($manual, 500000, $admin);

        $expired = $this->holdBooking($type, '2026-10-20', '2026-10-21');
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();

        return compact('type', 'web', 'manual', 'expired');
    }

    public function test_ringkasan_pendapatan_sumber_booking_dan_okupansi(): void
    {
        $seeded = $this->seedBookings();

        $report = app(ReportService::class)->summary(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'));

        $this->assertSame(750000, $report['revenue']['total']);
        $this->assertSame(2, $report['revenue']['count']);
        $this->assertSame(750000, $report['revenue']['by_method']['Transfer Manual']);

        $this->assertSame(3, $report['bookings']['count']);
        $this->assertSame(2, $report['bookings']['confirmed']);
        $this->assertSame(1500000, $report['bookings']['value']);
        $this->assertSame(1, $report['bookings']['by_channel']['Website']['confirmed']);
        $this->assertSame(1, $report['bookings']['by_channel']['Input Manual']['confirmed']);
        $this->assertSame(1, $report['bookings']['by_status']['Kedaluwarsa']);

        // 12 Okt: 2 unit terjual, 13 Okt: 1 unit terjual
        $type = collect($report['occupancy']['types'])->firstWhere('id', $seeded['type']->id);
        $this->assertSame(3, $type['booked']);
        $this->assertSame(62, $type['capacity']); // 31 malam x 2 unit
    }

    public function test_ekspor_excel_berisi_empat_sheet(): void
    {
        $this->seedBookings();

        $path = app(ReportService::class)->exportXlsx(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $workbook = $zip->getFromName('xl/workbook.xml');
        foreach (['Ringkasan', 'Pembayaran', 'Booking', 'Okupansi'] as $sheet) {
            $this->assertStringContainsString('name="'.$sheet.'"', $workbook);
        }
        $zip->close();
        @unlink($path);
    }

    public function test_laporan_hanya_untuk_admin_dan_owner(): void
    {
        $this->seed(RolesSeeder::class);

        $this->actingAs(tap(User::factory()->create())->assignRole('admin'))->get(Reports::getUrl())->assertOk()->assertSee('Okupansi per tipe unit');
        $this->actingAs(tap(User::factory()->create())->assignRole('sales'))->get(Reports::getUrl())->assertForbidden();
        $this->actingAs(tap(User::factory()->create())->assignRole('front_office'))->get(Reports::getUrl())->assertForbidden();
    }

    public function test_tombol_ekspor_mengunduh_file_excel(): void
    {
        $this->seed(RolesSeeder::class);
        $this->seedBookings();
        $this->actingAs(tap(User::factory()->create())->assignRole('owner'));

        Livewire::test(Reports::class)
            ->call('preset', 'bulan-ini')
            ->assertSet('from', '2026-10-01')
            ->callAction('export')
            ->assertFileDownloaded('laporan-lingkung-gunung-2026-10-01-sd-2026-10-31.xlsx');
    }
}
