<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\QuotationStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Quotations\Pages\ViewQuotation;
use App\Filament\Widgets\BookingTrendChart;
use App\Filament\Widgets\NeedsAttention;
use App\Filament\Widgets\ResortStatsOverview;
use App\Filament\Widgets\RevenueTrendChart;
use App\Mail\InquiryReceivedMail;
use App\Models\GroupPackageVariant;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\DashboardService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\QuotationService;
use Carbon\CarbonImmutable;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\AdditionalItemSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\GroupPackageSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Tahap peningkatan (Okt 2026): form kontak & Pesan Masuk, dashboard, data resmi, perbaikan rate limit. */
class EnhancementTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        Mail::fake();
    }

    private function validInquiry(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rina Pratama',
            'phone' => '0812 3456 7890',
            'email' => 'Rina@Example.com',
            'topic' => 'rombongan',
            'visit_date' => '2026-11-20',
            'participants' => '80',
            'message' => 'Apakah paket LDKS bisa untuk 80 siswa di bulan November?',
            'consent' => '1',
            'website' => '',
        ], $overrides);
    }

    // ------------------------------------------------------------------ form kontak

    public function test_form_kontak_menyimpan_pesan_dan_memberi_tahu_email_resmi(): void
    {
        $this->post(route('kontak.store'), $this->validInquiry())
            ->assertRedirect(route('kontak').'#kirim-pertanyaan')
            ->assertSessionHas('inquiry_sent');

        $inquiry = Inquiry::query()->sole();
        $this->assertSame('Rina Pratama', $inquiry->name);
        $this->assertSame('081234567890', $inquiry->phone);
        $this->assertSame('rina@example.com', $inquiry->email);
        $this->assertSame(InquiryStatus::Baru, $inquiry->status);
        $this->assertSame(80, $inquiry->participants);
        $this->assertSame('2026-11-20', $inquiry->visit_date->toDateString());

        Mail::assertQueued(InquiryReceivedMail::class, fn ($mail) => $mail->hasTo('marketing@elji.id') && $mail->inquiry->is($inquiry));
        $this->get(route('kontak'))->assertSee('Pertanyaan terkirim');
    }

    public function test_jumlah_peserta_diabaikan_untuk_topik_menginap(): void
    {
        $this->post(route('kontak.store'), $this->validInquiry(['topic' => 'menginap']))->assertSessionHasNoErrors();

        $this->assertNull(Inquiry::query()->sole()->participants);
    }

    public function test_form_kontak_menolak_isian_tidak_valid_dengan_pesan_indonesia(): void
    {
        $this->from(route('kontak'))
            ->post(route('kontak.store'), $this->validInquiry(['phone' => '12345', 'message' => 'halo', 'consent' => null, 'topic' => 'xx', 'visit_date' => '2026-01-01']))
            ->assertSessionHasErrors([
                'phone' => 'Nomor WhatsApp sepertinya belum benar. Contoh: 0812 3456 7890.',
                'message' => 'Pertanyaan terlalu singkat (minimal 10 karakter).',
                'consent' => 'Centang persetujuan agar kami boleh menghubungi Anda.',
                'topic' => 'Pilih topik pertanyaan dari daftar.',
                'visit_date' => 'Rencana tanggal tidak boleh di masa lalu.',
            ]);

        $this->assertSame(0, Inquiry::query()->count());
        Mail::assertNothingQueued();
    }

    public function test_honeypot_terisi_ditolak(): void
    {
        $this->post(route('kontak.store'), $this->validInquiry(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

        $this->assertSame(0, Inquiry::query()->count());
    }

    public function test_form_kontak_dibatasi_lima_pesan_per_sepuluh_menit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('kontak.store'), $this->validInquiry())->assertRedirect();
        }

        $this->post(route('kontak.store'), $this->validInquiry())->assertStatus(429);
        $this->assertSame(5, Inquiry::query()->count());
    }

    public function test_batas_percobaan_tiap_rute_tidak_saling_berbagi(): void
    {
        // Sebelumnya semua throttle memakai satu penghitung per IP: 5x kirim pesan membuat form daftar ikut 429.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('kontak.store'), $this->validInquiry());
        }

        $this->post(route('akun.daftar.store'), [
            'name' => 'Tamu Baru', 'email' => 'tamu.baru@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect();
        $this->assertDatabaseHas('customer_accounts', ['email' => 'tamu.baru@example.com']);
    }

    // ------------------------------------------------------------------ admin: Pesan Masuk

    public function test_pesan_masuk_bisa_dibuka_staf_dan_status_tercatat_saat_dibalas(): void
    {
        $this->seed(RolesSeeder::class);
        $this->post(route('kontak.store'), $this->validInquiry());
        $inquiry = Inquiry::query()->sole();

        $sales = tap(User::factory()->create())->assignRole('sales');
        $this->actingAs($sales)->get(InquiryResource::getUrl('index'))->assertOk()->assertSee('Rina Pratama');
        $this->actingAs($sales)->get(InquiryResource::getUrl('view', ['record' => $inquiry]))->assertOk()->assertSee('Apakah paket LDKS');

        Livewire::actingAs($sales)->test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])->call('markContacted');

        $inquiry->refresh();
        $this->assertSame(InquiryStatus::Diproses, $inquiry->status);
        $this->assertTrue($inquiry->handler->is($sales));
        $this->assertNotNull($inquiry->handled_at);
    }

    public function test_staf_bisa_mengubah_status_dan_catatan_internal(): void
    {
        $this->seed(RolesSeeder::class);
        $this->post(route('kontak.store'), $this->validInquiry());
        $inquiry = Inquiry::query()->sole();
        $fo = tap(User::factory()->create())->assignRole('front_office');

        Livewire::actingAs($fo)->test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->callAction('status', ['status' => 'selesai', 'staff_note' => 'Sudah dikirim penawaran via WA.'])
            ->assertHasNoActionErrors();

        $inquiry->refresh();
        $this->assertSame(InquiryStatus::Selesai, $inquiry->status);
        $this->assertSame('Sudah dikirim penawaran via WA.', $inquiry->staff_note);
    }

    public function test_pesan_masuk_tidak_bisa_dibuat_dari_admin_dan_tertutup_untuk_tamu(): void
    {
        $this->assertFalse(InquiryResource::canCreate());
        $this->get(InquiryResource::getUrl('index'))->assertRedirect();
    }

    public function test_chat_klien_penawaran_mengubah_status_menjadi_dihubungi(): void
    {
        $this->seed(RolesSeeder::class);
        $quotation = $this->makeQuotation();
        $sales = tap(User::factory()->create())->assignRole('sales');

        Livewire::actingAs($sales)->test(ViewQuotation::class, ['record' => $quotation->getRouteKey()])->call('markContacted');

        $this->assertSame(QuotationStatus::Dihubungi, $quotation->refresh()->status);
    }

    // ------------------------------------------------------------------ dashboard

    public function test_dashboard_membedakan_uang_diterima_nilai_booking_dan_sisa_tagihan(): void
    {
        $type = $this->makeUnitType(500000, 600000, ['total_units' => 3]);
        $admin = User::factory()->create();
        $payments = app(PaymentService::class);

        $dp = $this->holdBooking($type, '2026-10-12', '2026-10-14'); // 1.000.000, bayar DP 250.000
        $payments->recordManualPayment($dp, 250000, $admin);
        $paid = $this->holdBooking($type, '2026-10-12', '2026-10-13'); // 500.000 lunas
        $payments->recordManualPayment($paid, 500000, $admin);
        $this->holdBooking($type, '2026-10-20', '2026-10-21'); // dibiarkan kedaluwarsa
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();

        $dashboard = app(DashboardService::class);
        $month = [CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31')];

        $this->assertSame(750000, $dashboard->received(...$month));
        $this->assertSame(['count' => 2, 'value' => 1500000, 'lost' => 1], $dashboard->bookingsCreated(...$month));
        $this->assertSame(['count' => 1, 'amount' => 750000, 'overdue' => 0], $dashboard->outstanding());

        $trend = $dashboard->dailyTrend(7);
        $this->assertCount(7, $trend['labels']);
        $this->assertSame(750000, array_sum($trend['received']));
        $this->assertSame(2, array_sum($trend['confirmed']));
        $this->assertSame(1, array_sum($trend['lost']));
    }

    public function test_panel_perlu_tindakan_hanya_menampilkan_yang_ada(): void
    {
        $dashboard = app(DashboardService::class);
        $this->assertSame([], $dashboard->attentionItems());

        $this->post(route('kontak.store'), $this->validInquiry());
        $items = collect($dashboard->attentionItems());

        $this->assertSame(['inquiries'], $items->pluck('key')->all());
        $this->assertSame(1, $items->first()['count']);
    }

    public function test_dashboard_tampil_untuk_admin_dan_front_office(): void
    {
        $this->seed(RolesSeeder::class);

        $admin = tap(User::factory()->create())->assignRole('admin');
        $fo = tap(User::factory()->create())->assignRole('front_office');
        $this->post(route('kontak.store'), $this->validInquiry());

        // Widget Filament dimuat lazy, jadi dirender langsung lewat Livewire
        $this->actingAs($admin)->get('/'.config('resort.admin_path'))->assertOk();
        Livewire::actingAs($admin)->test(ResortStatsOverview::class)->assertSee('Uang diterima')->assertSee('Nilai booking terkonfirmasi')->assertSee('Sisa tagihan');
        Livewire::actingAs($admin)->test(NeedsAttention::class)->assertSee('Perlu tindakan')->assertSee('Pesan masuk baru');
        Livewire::actingAs($admin)->test(RevenueTrendChart::class)->assertOk();
        Livewire::actingAs($admin)->test(BookingTrendChart::class)->assertOk();

        $this->actingAs($fo);
        $this->assertTrue(NeedsAttention::canView());
        $this->assertFalse(RevenueTrendChart::canView(), 'Front office tidak melihat grafik keuangan');
    }

    // ------------------------------------------------------------------ data resmi & halaman publik

    public function test_kontak_resmi_tampil_di_halaman_publik(): void
    {
        $this->seed(ContentSeeder::class);

        $this->get(route('kontak'))->assertOk()
            ->assertSee('marketing@elji.id')
            ->assertSee('https://www.facebook.com/LingkungGunungBogor', false)
            ->assertSee('Kirim pertanyaan');

        $home = $this->get(route('home'))->assertOk();
        $home->assertSee('"email":"marketing@elji.id"', false)->assertSee('https://elji.id', false);
        $home->assertDontSee('kaki Gunung Salak')->assertDontSee('Satu jam dari Jakarta');
    }

    public function test_tabel_perbandingan_akomodasi_memakai_harga_database_dan_label_weekend_pengaturan(): void
    {
        $this->makeUnitType(525000, 640000, ['marketing_name' => 'Pondokan Uji']);
        Setting::put('weekend_nights', [6]);

        $this->assertSame('malam Sabtu', app(PricingService::class)->weekendLabel());
        $this->get(route('akomodasi.index'))->assertOk()
            ->assertSee('Semua pilihan dalam satu tabel')
            ->assertSee('Rp525.000')->assertSee('Rp640.000')
            ->assertSee('malam Sabtu');
    }

    public function test_migrasi_data_resmi_tidak_menimpa_isi_yang_sudah_diubah_admin(): void
    {
        $migration = require database_path('migrations/2026_10_25_100000_update_official_resort_data.php');

        $deluxe = $this->makeUnitType(670000, 815000, ['slug' => 'pangrango-deluxe', 'includes' => ['Room', 'Wifi', 'TV', 'Breakfast 2 pax', 'Pool', 'Breakfast: Nasi Goreng / Nasi Uduk, Telur Ceplok / Tempe Mendoan, Air Mineral, Sambal, Kerupuk']]);
        $junior = $this->makeUnitType(930000, 1105000, ['slug' => 'pangrango-junior-suite', 'includes' => ['Diubah admin']]);

        $migration->up();

        $this->assertContains('Kamar 22 m²', $deluxe->refresh()->includes);
        $this->assertSame(['Diubah admin'], $junior->refresh()->includes);
    }

    private function makeQuotation(): Quotation
    {
        Storage::fake('local');
        $this->seed([UnitTypeSeeder::class, MenuSeeder::class, GroupPackageSeeder::class, ActivitySeeder::class, AdditionalItemSeeder::class]);
        $service = app(QuotationService::class);
        $variant = GroupPackageVariant::query()->whereHas('groupPackage', fn ($q) => $q->where('slug', 'fullboard-ldks'))->where('code', 'A')->firstOrFail();

        return $service->create([
            'variant_id' => $variant->id,
            'event_date' => '2026-10-13',
            'nights' => 1,
            'pax' => 40,
            'menu_plan' => $service->defaultMenuPlan($variant->load('menuSet.options'), 1),
            'activities' => [],
            'items' => [],
            'customer' => ['name' => 'Bu Sari', 'phone' => '081211112222', 'email' => 'sari@example.com', 'organization' => 'SMA Uji'],
        ]);
    }
}
