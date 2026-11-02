<?php

namespace Tests\Feature;

use App\Mail\SettlementReminderMail;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PaymentService;
use Carbon\Carbon;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\AdditionalItemSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\GroupPackageSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->seed([UnitTypeSeeder::class, MenuSeeder::class, GroupPackageSeeder::class, ActivitySeeder::class, AdditionalItemSeeder::class, ContentSeeder::class]);
    }

    public function test_semua_halaman_publik_bisa_dibuka(): void
    {
        $pages = [
            '/' => 'Apa rencana kunjungan Anda?',
            '/akomodasi' => 'Glamping Imah Lembah',
            '/akomodasi/glamourcamp' => 'Rp670.000',
            '/play' => 'Rafting',
            '/learn' => 'Sanggar Alam',
            '/gather' => 'Fullboard LDKS',
            '/taste' => 'Menu Makan A',
            '/galeri' => 'Galeri',
            '/rute-dan-akses' => 'Gerbang Tol Caringin',
            '/kontak' => 'Pertanyaan yang sering diajukan',
            '/info/kebijakan-privasi' => 'Kebijakan Privasi',
            '/info/mekanisme-pembayaran' => '133-00-1868-7988',
            '/pesan' => 'Kapan Anda datang?',
        ];

        foreach ($pages as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text, false);
        }
    }

    public function test_beranda_memuat_data_terstruktur_seo(): void
    {
        $this->get('/')
            ->assertSee('"@type":"LodgingBusiness"', false)
            ->assertSee('<html lang="id"', false);
        // FAQ dipindah dari beranda ke halaman Kontak (tahap ux-redesign)
        $this->get(route('kontak'))->assertSee('"@type":"FAQPage"', false);
    }

    public function test_akomodasi_nonaktif_dan_halaman_beranda_tidak_bisa_diakses_langsung(): void
    {
        UnitType::query()->where('slug', 'camp-site')->update(['is_active' => false]);

        $this->get('/akomodasi/camp-site')->assertNotFound();
        $this->get('/info/beranda')->assertNotFound();
        $this->get('/akomodasi/tidak-ada')->assertNotFound();
    }

    public function test_sitemap_berisi_halaman_utama_dan_akomodasi(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('akomodasi.show', 'glamourcamp'), false)->assertSee(route('gather'), false);
    }

    public function test_halaman_booking_memakai_kode_acak_dan_bukan_id(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        $this->get('/booking/'.$booking->code)->assertOk()->assertSee($booking->code)->assertSee('Selesaikan pembayaran dalam');
        $this->get('/booking/'.$booking->id)->assertNotFound();
    }

    public function test_e_ticket_pdf_hanya_tersedia_setelah_dibayar(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(500000, 600000), '2026-10-12', '2026-10-13');
        $this->get(route('booking.eticket', $booking))->assertNotFound();

        Mail::fake();
        app(PaymentService::class)->recordManualPayment($booking, 500000, User::factory()->create());

        $this->get(route('booking.show', $booking))->assertSee('Unduh PDF')->assertSee('<svg', false);
        $response = $this->get(route('booking.eticket', $booking))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pengingat_pelunasan_dikirim_h7_dan_h4(): void
    {
        Mail::fake();
        $type = $this->makeUnitType(500000, 600000, ['total_units' => 10]);
        $admin = User::factory()->create();

        foreach (['2026-10-12', '2026-10-09', '2026-10-15'] as $checkIn) { // H-7, H-4, H-10
            $booking = $this->holdBooking($type, $checkIn, Carbon::parse($checkIn)->addDay()->toDateString());
            app(PaymentService::class)->recordManualPayment($booking, $booking->dp_amount, $admin);
        }

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Mail::assertQueued(SettlementReminderMail::class, 2);
    }
}
