<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Enums\QuotationStatus;
use App\Exceptions\BookingException;
use App\Mail\ProposalMail;
use App\Models\Activity;
use App\Models\AdditionalItem;
use App\Models\DailyInventory;
use App\Models\GroupPackageVariant;
use App\Models\Quotation;
use App\Models\UnitType;
use App\Models\User;
use App\Services\QuotationService;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\AdditionalItemSeeder;
use Database\Seeders\GroupPackageSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Package Builder B2B. Kalender Okt 2026: Senin 12, Kamis 15, Jumat 16, Sabtu 17.
 * Asumsi harga paket = per peserta per malam (docs/DATA-TODO.md).
 */
class QuotationServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->seed([UnitTypeSeeder::class, MenuSeeder::class, GroupPackageSeeder::class, ActivitySeeder::class, AdditionalItemSeeder::class]);
        $this->service = app(QuotationService::class);
        Storage::fake('local');
        Mail::fake();
    }

    private function variant(string $package, string $code): GroupPackageVariant
    {
        return GroupPackageVariant::query()->whereHas('groupPackage', fn ($q) => $q->where('slug', $package))->where('code', $code)->firstOrFail();
    }

    private function input(GroupPackageVariant $variant, array $overrides = []): array
    {
        $nights = $overrides['nights'] ?? 1;

        return $overrides + [
            'variant_id' => $variant->id,
            'event_date' => '2026-10-12',
            'nights' => $nights,
            'pax' => 30,
            'menu_plan' => $this->service->defaultMenuPlan($variant->load('menuSet.options'), $nights),
            'activities' => [],
            'items' => [],
        ];
    }

    private function activity(string $slug): Activity
    {
        return Activity::query()->where('slug', $slug)->firstOrFail();
    }

    public function test_harga_paket_weekday_per_peserta_per_malam(): void
    {
        $estimate = $this->service->estimate($this->input($this->variant('fullboard-ldks', 'A')));

        $this->assertTrue($estimate['valid'], implode(', ', $estimate['errors']));
        $this->assertSame(260000 * 30, $estimate['total']);
        $this->assertSame((int) ceil(260000 * 30 * 0.25), $estimate['dp_amount']);
    }

    public function test_malam_weekend_memakai_harga_weekend(): void
    {
        $estimate = $this->service->estimate($this->input($this->variant('fullboard-ldks', 'A'), ['event_date' => '2026-10-16']));

        $this->assertSame(310000 * 30, $estimate['total']);
    }

    public function test_acara_dua_malam_campuran_wd_dan_we(): void
    {
        $variant = $this->variant('fullboard-camp', 'A'); // 375rb WD / 425rb WE, min 50
        $estimate = $this->service->estimate($this->input($variant, ['event_date' => '2026-10-15', 'nights' => 2, 'pax' => 50]));

        $this->assertTrue($estimate['valid'], implode(', ', $estimate['errors']));
        $this->assertSame((375000 + 425000) * 50, $estimate['total']);
        $this->assertSame('package', $estimate['lines'][0]['item_type']);
        $this->assertCount(2, $estimate['lines'][0]['meta']['nights']);
    }

    public function test_peserta_kurang_dari_minimal_ditolak(): void
    {
        $estimate = $this->service->estimate($this->input($this->variant('fullboard-ldks', 'C'), ['pax' => 80]));

        $this->assertFalse($estimate['valid']);
        $this->assertContains('Fullboard LDKS (C) minimal 100 peserta.', $estimate['errors']);
    }

    public function test_ldks_c_hanya_untuk_malam_weekday(): void
    {
        $variant = $this->variant('fullboard-ldks', 'C');

        $this->assertTrue($this->service->estimate($this->input($variant, ['pax' => 100]))['valid']);

        $weekend = $this->service->estimate($this->input($variant, ['pax' => 100, 'event_date' => '2026-10-16']));
        $this->assertFalse($weekend['valid']);
        $this->assertStringContainsString('hanya tersedia untuk malam weekday', implode(' ', $weekend['errors']));
    }

    public function test_aktivitas_dan_item_tambahan_dihitung_dan_minimalnya_divalidasi(): void
    {
        $variant = $this->variant('fullboard-ldks', 'A');
        $rafting = $this->activity('rafting'); // 290rb, min 8
        $sate = AdditionalItem::query()->where('name', 'like', 'Sate Kambing%')->firstOrFail(); // min 5

        $ok = $this->service->estimate($this->input($variant, ['activities' => [$rafting->id => 30], 'items' => [$sate->id => 5]]));
        $this->assertTrue($ok['valid'], implode(', ', $ok['errors']));
        $this->assertSame(260000 * 30 + 290000 * 30 + 410000 * 5, $ok['total']);

        $bad = $this->service->estimate($this->input($variant, ['activities' => [$rafting->id => 5], 'items' => [$sate->id => 2]]));
        $this->assertContains('Rafting minimal 8 peserta.', $bad['errors']);
        $this->assertContains('Sate Kambing (1 Porsi, 10 tusuk) minimal 5 porsi.', $bad['errors']);
    }

    public function test_kambing_guling_butuh_rombongan_minimal_40_orang(): void
    {
        $estimate = $this->service->estimate($this->input($this->variant('fullboard-ldks', 'A'), ['activities' => [$this->activity('kambing-guling')->id => 1]]));

        $this->assertContains('Kambing Guling untuk rombongan minimal 40 orang.', $estimate['errors']);
    }

    public function test_pilihan_menu_harus_sesuai_set_menu_dan_coffee_break_dua_macam(): void
    {
        $variant = $this->variant('fullboard-camp', 'A')->load('menuSet.options'); // menu A, coffee break 2x
        $plan = $this->service->defaultMenuPlan($variant, 1);
        $plan[0]['lunch'] = 'Z';
        $plan[0]['coffee_breaks'][0] = ['pisang'];

        $estimate = $this->service->estimate($this->input($variant, ['pax' => 50, 'menu_plan' => $plan]));

        $this->assertContains('Pilih menu makan siang untuk hari ke-1.', $estimate['errors']);
        $this->assertContains('Coffee break ke-1 hari ke-1: pilih 2 macam menu.', $estimate['errors']);
    }

    public function test_membuat_penawaran_nomor_berurutan_pdf_tersimpan_dan_email_terkirim(): void
    {
        $variant = $this->variant('fullboard-ldks', 'A');
        $customer = ['name' => 'Bu Rina', 'organization' => 'SMA Harapan', 'phone' => '081234567890', 'email' => 'rina@sma.sch.id'];

        $first = $this->service->create($this->input($variant) + ['customer' => $customer]);
        $second = $this->service->create($this->input($variant) + ['customer' => $customer]);

        $this->assertSame('LG-Q-2610-0001', $first->number);
        $this->assertSame('LG-Q-2610-0002', $second->number);
        $this->assertMatchesRegularExpression('/^Q[A-Z0-9]{10}$/', $first->code);
        $this->assertSame(QuotationStatus::Baru, $first->status);
        $this->assertSame(7800000, $first->total);
        $this->assertSame('SMA Harapan', $first->customer->organization);
        $this->assertCount(1, $first->menu_plan);
        Storage::disk('local')->assertExists($first->pdf_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($first->pdf_path));
        Mail::assertQueued(ProposalMail::class, 2);
    }

    public function test_penawaran_yang_melanggar_aturan_tidak_bisa_dibuat(): void
    {
        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('minimal 100 peserta');

        $this->service->create($this->input($this->variant('fullboard-ldks', 'C'), ['pax' => 50]) + ['customer' => ['name' => 'X', 'phone' => '0812345678']]);
    }

    public function test_ubah_item_menghitung_ulang_total(): void
    {
        $quotation = $this->service->create($this->input($this->variant('fullboard-ldks', 'A')) + ['customer' => ['name' => 'Bu Rina', 'phone' => '081234567890']]);
        $quotation->items()->create(['item_type' => 'custom', 'description' => 'Diskon negosiasi', 'qty' => 1, 'unit_price' => 0, 'subtotal' => 0]);
        $quotation->items()->first()->update(['unit_price' => 250000]);

        $quotation = $this->service->recalculate($quotation->fresh('items'));

        $this->assertSame(250000 * 30, $quotation->total);
    }

    public function test_konversi_ke_booking_mengunci_stok_dan_memakai_total_penawaran(): void
    {
        $variant = $this->variant('fullboard-room-glamping', 'A');
        $quotation = $this->service->create($this->input($variant, ['event_date' => '2026-10-20']) + ['customer' => ['name' => 'PT Maju', 'phone' => '081200000000']]);
        $glamping = UnitType::query()->where('slug', 'glamourcamp')->first();

        $booking = $this->service->convertToBooking($quotation, [$glamping->id => 8], User::factory()->create(), holdMinutes: 2880);

        $this->assertSame(BookingChannel::B2b, $booking->channel);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($quotation->total, $booking->total);
        $this->assertSame((int) ceil($quotation->total * 0.25), $booking->dp_amount);
        $this->assertTrue($booking->hold_expires_at->equalTo(now()->addMinutes(2880)));
        $this->assertSame(8, DailyInventory::query()->where('unit_type_id', $glamping->id)->whereDate('date', '2026-10-20')->value('held'));
        $this->assertSame(QuotationStatus::Deal, $quotation->fresh()->status);
        $this->assertSame($booking->id, $quotation->fresh()->booking_id);

        $this->expectExceptionMessage('sudah memiliki booking');
        $this->service->convertToBooking($quotation->fresh(), [], User::factory()->create());
    }

    public function test_konversi_gagal_bila_stok_unit_tidak_cukup(): void
    {
        $quotation = $this->service->create($this->input($this->variant('fullboard-room-glamping', 'A'), ['event_date' => '2026-10-20']) + ['customer' => ['name' => 'PT Maju', 'phone' => '081200000000']]);
        $glamping = UnitType::query()->where('slug', 'glamourcamp')->first(); // 10 unit

        try {
            $this->service->convertToBooking($quotation, [$glamping->id => 11], User::factory()->create());
            $this->fail('Seharusnya gagal');
        } catch (BookingException $e) {
            $this->assertStringContainsString('tidak tersedia', $e->getMessage());
        }

        $this->assertNull($quotation->fresh()->booking_id);
        $this->assertSame(0, (int) DailyInventory::query()->where('unit_type_id', $glamping->id)->sum('held'));
    }

    public function test_konversi_tanpa_alokasi_unit_tetap_bisa_untuk_paket_tenda_rombongan(): void
    {
        $quotation = $this->service->create($this->input($this->variant('fullboard-ldks', 'A')) + ['customer' => ['name' => 'SMA', 'phone' => '081200000000']]);

        $booking = $this->service->convertToBooking($quotation, [], User::factory()->create());

        $this->assertSame(1, $booking->items()->count());
        $this->assertSame('package', $booking->items()->first()->item_type);
    }

    public function test_penawaran_batal_tidak_bisa_dikonversi_dan_status_deal_terkunci_setelah_booking(): void
    {
        $quotation = $this->service->create($this->input($this->variant('fullboard-ldks', 'A')) + ['customer' => ['name' => 'SMA', 'phone' => '081200000000']]);
        $this->service->updateStatus($quotation, QuotationStatus::Batal);

        $this->expectExceptionMessage('dibatalkan');
        $this->service->convertToBooking($quotation->fresh(), [], User::factory()->create());
    }

    public function test_url_proposal_memakai_kode_acak(): void
    {
        $quotation = $this->service->create($this->input($this->variant('fullboard-ldks', 'A')) + ['customer' => ['name' => 'SMA', 'phone' => '081200000000']]);

        $this->get(route('proposal.show', $quotation))->assertOk()->assertSee($quotation->number);
        $this->get(route('proposal.pdf', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/proposal/'.$quotation->id)->assertNotFound();
        $this->assertSame(1, Quotation::query()->count());
    }
}
