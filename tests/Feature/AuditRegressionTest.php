<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\GalleryPhotos\Pages\CreateGalleryPhoto;
use App\Livewire\BookingWizard;
use App\Livewire\PackageBuilder;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\CustomerAccount;
use App\Models\DailyInventory;
use App\Models\GalleryPhoto;
use App\Models\GroupPackageVariant;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\ETicket;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\AdditionalItemSeeder;
use Database\Seeders\GroupPackageSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Regresi untuk temuan audit keamanan & QA (Oktober 2026). */
class AuditRegressionTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        RateLimiter::clear('booking:127.0.0.1');
        Mail::fake();
    }

    private function verifiedAccount(): CustomerAccount
    {
        $account = CustomerAccount::query()->create(['name' => 'Siti Aminah', 'email' => 'siti'.uniqid().'@example.com', 'password' => 'rahasia123', 'phone' => '081234567890']);
        $account->forceFill(['email_verified_at' => now()])->save();

        return $account;
    }

    private function wizard(array $query = [])
    {
        return Livewire::withQueryParams($query + ['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])->test(BookingWizard::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Booking ganda & penimbunan stok
    |--------------------------------------------------------------------------
    */

    public function test_kirim_dua_kali_tidak_membuat_booking_ganda(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 5]);
        $this->actingAs($this->verifiedAccount(), 'customer');

        $wizard = $this->wizard()->call('changeQty', $type->id, 1)->call('toCustomer')->set('agree', true);
        $wizard->call('submit')->assertRedirect();
        $first = Booking::query()->sole();

        $wizard->call('submit')->assertRedirect(route('booking.show', ['booking' => $first, 'bayar' => 1]));

        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(1, (int) DailyInventory::query()->where('unit_type_id', $type->id)->whereDate('date', '2026-10-12')->value('held'));
    }

    public function test_satu_akun_tidak_bisa_menimbun_stok_dengan_banyak_booking_belum_dibayar(): void
    {
        config(['resort.max_pending_holds_per_account' => 3]);
        $type = $this->makeUnitType(attributes: ['total_units' => 20]);
        $this->actingAs($this->verifiedAccount(), 'customer');

        foreach (['2026-10-12', '2026-10-14', '2026-10-16'] as $date) {
            RateLimiter::clear('booking:127.0.0.1');
            $out = date('Y-m-d', strtotime($date.' +1 day'));
            $this->wizard(['check_in' => $date, 'check_out' => $out])->call('changeQty', $type->id, 1)->call('toCustomer')->set('agree', true)->call('submit')->assertRedirect();
        }

        RateLimiter::clear('booking:127.0.0.1');
        $this->wizard(['check_in' => '2026-10-20', 'check_out' => '2026-10-21'])->call('changeQty', $type->id, 1)->call('toCustomer')->set('agree', true)->call('submit')
            ->assertNoRedirect()
            ->assertSee('booking yang menunggu pembayaran');

        $this->assertSame(3, Booking::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Rekonsiliasi pembayaran (webhook gagal, tamu tidak kembali)
    |--------------------------------------------------------------------------
    */

    private function pendingMidtransPayment(): Payment
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-uji', 'services.midtrans.client_key' => 'SB-Mid-client-uji']);
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        return $booking->payments()->create([
            'type' => 'dp', 'method' => 'midtrans', 'amount' => $booking->dp_amount, 'status' => 'pending',
            'gateway_order_id' => $booking->code.'-DP-UJI01', 'expires_at' => now()->addHour(),
        ]);
    }

    public function test_rekonsiliasi_menangkap_pembayaran_sukses_yang_webhooknya_tidak_sampai(): void
    {
        $payment = $this->pendingMidtransPayment();
        Http::fake(['*/v2/*/status' => Http::response([
            'order_id' => $payment->gateway_order_id, 'gross_amount' => $payment->amount.'.00', 'transaction_status' => 'settlement',
            'fraud_status' => 'accept', 'transaction_id' => 'trx-1', 'payment_type' => 'bank_transfer',
        ])]);

        $this->artisan('payments:reconcile')->expectsOutputToContain('1 berubah status')->assertSuccessful();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(BookingStatus::DpPaid, $payment->booking->fresh()->status);

        // Dijalankan ulang: tidak dobel
        $this->artisan('payments:reconcile')->expectsOutputToContain('Dicek 0 transaksi')->assertSuccessful();
        $this->assertSame($payment->amount, $payment->booking->fresh()->paid_amount);
    }

    public function test_rekonsiliasi_menolak_status_yang_nominalnya_tidak_cocok_dan_mencatat_kedaluwarsa(): void
    {
        $payment = $this->pendingMidtransPayment();
        Http::fake(['*/v2/*/status' => Http::response(['order_id' => $payment->gateway_order_id, 'gross_amount' => '1.00', 'transaction_status' => 'settlement', 'fraud_status' => 'accept'])]);
        app(PaymentService::class)->reconcilePending();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::Pending, $payment->booking->fresh()->status);

        Http::swap(new HttpFactory);
        Http::fake(['*/v2/*/status' => Http::response(['order_id' => $payment->gateway_order_id, 'gross_amount' => $payment->amount.'.00', 'transaction_status' => 'expire'])]);
        app(PaymentService::class)->reconcilePending();
        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
    }

    public function test_rekonsiliasi_terjadwal_tiap_lima_menit(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('payments:reconcile')->assertSuccessful();
    }

    /*
    |--------------------------------------------------------------------------
    | Upload file aman
    |--------------------------------------------------------------------------
    */

    public function test_unggahan_svg_ditolak_dengan_pesan_validasi(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesSeeder::class);
        $this->actingAs(tap(User::factory()->create())->assignRole('admin'));

        $svg = UploadedFile::fake()->createWithContent('serangan.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        Livewire::test(CreateGalleryPhoto::class)
            ->fillForm(['photo' => [$svg], 'title' => 'Uji', 'category' => array_key_first(GalleryPhoto::CATEGORIES)])
            ->call('create')
            ->assertHasFormErrors(['photo']);

        $this->assertSame(0, GalleryPhoto::query()->count());
    }

    public function test_bukti_transfer_ditampilkan_tanpa_izin_menjalankan_script(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-transfer/bukti.jpg', UploadedFile::fake()->image('bukti.jpg')->getContent());
        $this->seed(RolesSeeder::class);
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');
        $payment = $booking->payments()->create(['type' => 'dp', 'method' => 'transfer', 'amount' => 125000, 'status' => 'paid', 'proof_path' => 'bukti-transfer/bukti.jpg']);

        $this->actingAs(tap(User::factory()->create())->assignRole('admin'))
            ->get(route('admin.payment-proof', $payment))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
    }

    /*
    |--------------------------------------------------------------------------
    | Package Builder: item yang dinonaktifkan admin tidak membuat halaman error
    |--------------------------------------------------------------------------
    */

    public function test_aktivitas_yang_dinonaktifkan_saat_dipilih_tidak_membuat_builder_error(): void
    {
        $this->seed([UnitTypeSeeder::class, MenuSeeder::class, GroupPackageSeeder::class, ActivitySeeder::class, AdditionalItemSeeder::class]);
        $rafting = Activity::query()->where('slug', 'rafting')->first();
        $ldks = GroupPackageVariant::query()->whereHas('groupPackage', fn ($q) => $q->where('slug', 'fullboard-ldks'))->where('code', 'A')->first();

        $builder = Livewire::test(PackageBuilder::class)
            ->call('selectVariant', $ldks->id)
            ->set('eventDate', '2026-10-12')
            ->set('pax', 40)
            ->call('toggleActivity', $rafting->id)
            ->assertSet('activities.'.$rafting->id, 40);

        $rafting->update(['is_active' => false]);

        $builder->call('next')->assertOk()->assertSet('activities', []);
    }

    /*
    |--------------------------------------------------------------------------
    | E-ticket PDF & halaman publik
    |--------------------------------------------------------------------------
    */

    public function test_eticket_pdf_satu_halaman_dan_ringan(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');
        $booking->forceFill(['status' => BookingStatus::DpPaid, 'paid_amount' => $booking->dp_amount])->save();

        $pdf = ETicket::pdf($booking->fresh());

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf), 'E-ticket harus muat dalam satu halaman A5');
        $this->assertLessThan(200 * 1024, strlen($pdf), 'Font harus di-subset agar lampiran email kecil');
    }

    public function test_halaman_kontak_punya_judul_utama_dan_galeri_tanpa_gambar_kosong(): void
    {
        $this->get('/kontak')->assertOk()->assertSee('<h1', false);
        $this->get('/galeri')->assertOk()->assertDontSee('x-bind:src="current?.src"', false);
    }
}
