<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Filament\Resources\Quotations\Pages\ViewQuotation;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Livewire\PackageBuilder;
use App\Models\Activity;
use App\Models\GroupPackageVariant;
use App\Models\Quotation;
use App\Models\UnitType;
use App\Models\User;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\AdditionalItemSeeder;
use Database\Seeders\GroupPackageSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PackageBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->seed([UnitTypeSeeder::class, MenuSeeder::class, GroupPackageSeeder::class, ActivitySeeder::class, AdditionalItemSeeder::class]);
        Storage::fake('local');
        Mail::fake();
        RateLimiter::clear('quotation:127.0.0.1');
    }

    private function ldks(string $code = 'A'): GroupPackageVariant
    {
        return GroupPackageVariant::query()->whereHas('groupPackage', fn ($q) => $q->where('slug', 'fullboard-ldks'))->where('code', $code)->firstOrFail();
    }

    public function test_klien_merancang_paket_sampai_proposal(): void
    {
        $rafting = Activity::query()->where('slug', 'rafting')->first();

        Livewire::test(PackageBuilder::class)
            ->call('selectVariant', $this->ldks()->id)
            ->set('eventDate', '2026-10-12')
            ->set('pax', 40)
            ->call('next')
            ->assertSet('step', 2)
            ->assertSee('Opsi A')
            ->set('menuPlan.0.lunch', 'C')
            ->call('next')
            ->assertSet('step', 3)
            ->call('toggleActivity', $rafting->id)
            ->assertSet('activities.'.$rafting->id, 40)
            ->call('next')
            ->assertSet('step', 4)
            ->set(['organization' => 'SMA Harapan Bangsa', 'name' => 'Bu Rina', 'phone' => '0812 3456 7890', 'email' => 'rina@sma.sch.id', 'agree' => true])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $quotation = Quotation::query()->with('items')->sole();
        $this->assertSame(40, $quotation->pax);
        $this->assertSame(260000 * 40 + 290000 * 40, $quotation->total);
        $this->assertSame('C', $quotation->menu_plan[0]['lunch']);
        $this->assertSame('081234567890', $quotation->customer->phone);
    }

    public function test_tidak_bisa_lanjut_bila_peserta_kurang_dari_minimal(): void
    {
        Livewire::test(PackageBuilder::class)
            ->call('selectVariant', $this->ldks('C')->id)
            ->set('eventDate', '2026-10-12')
            ->set('pax', 50)
            ->call('next')
            ->assertSet('step', 1)
            ->assertSee('Fullboard LDKS (C) minimal 100 peserta.');
    }

    public function test_template_2_days_outbound_mengisi_aktivitas(): void
    {
        $component = Livewire::withQueryParams(['template' => '2-days-outbound'])->test(PackageBuilder::class);

        $slugs = Activity::query()->whereIn('id', array_keys($component->get('activities')))->pluck('slug')->sort()->values()->all();
        $this->assertSame(['paintball', 'rafting', 'team-building'], $slugs);
        $component->assertSet('nights', 1);
    }

    public function test_data_instansi_wajib_dan_pesan_error_ramah(): void
    {
        Livewire::test(PackageBuilder::class)
            ->call('selectVariant', $this->ldks()->id)
            ->set('step', 4)
            ->set('phone', '123')
            ->call('submit')
            ->assertHasErrors(['organization', 'name', 'phone', 'email', 'agree'])
            ->assertSee('Nama instansi / perusahaan / sekolah wajib diisi.');

        $this->assertSame(0, Quotation::query()->count());
    }

    public function test_halaman_builder_dan_gather_bisa_dibuka(): void
    {
        $this->get('/rancang-paket')->assertOk()->assertSee('Rancang paket rombongan');
        $this->get('/gather')->assertOk()->assertSee('Mulai dari contoh 2 Days Outbound');
    }

    public function test_admin_mengonversi_penawaran_dari_panel_dan_sales_tidak_bisa(): void
    {
        $this->seed(RolesSeeder::class);
        Livewire::test(PackageBuilder::class)
            ->call('selectVariant', GroupPackageVariant::query()->whereHas('groupPackage', fn ($q) => $q->where('slug', 'fullboard-room-glamping'))->where('code', 'A')->first()->id)
            ->set('eventDate', '2026-10-20')
            ->set('pax', 30)
            ->call('next')->call('next')->call('next')
            ->set(['organization' => 'PT Maju', 'name' => 'Pak Budi', 'phone' => '081200000000', 'email' => 'budi@maju.co.id', 'agree' => true])
            ->call('submit');
        $quotation = Quotation::query()->sole();
        $glamping = UnitType::query()->where('slug', 'glamourcamp')->first();

        $sales = tap(User::factory()->create())->assignRole('sales');
        $this->actingAs($sales);
        $this->get(QuotationResource::getUrl('view', ['record' => $quotation]))->assertOk();
        Livewire::test(ViewQuotation::class, ['record' => $quotation->getRouteKey()])->assertActionHidden('convert');

        $admin = tap(User::factory()->create())->assignRole('admin');
        $this->actingAs($admin);
        Livewire::test(ViewQuotation::class, ['record' => $quotation->getRouteKey()])
            ->callAction('convert', data: ['lines' => [['unit_type_id' => $glamping->id, 'qty' => 8]], 'hold_minutes' => 1440])
            ->assertHasNoActionErrors();

        $booking = $quotation->fresh()->booking;
        $this->assertNotNull($booking);
        $this->assertSame(BookingChannel::B2b, $booking->channel);
        $this->assertSame($quotation->total, $booking->total);
    }
}
