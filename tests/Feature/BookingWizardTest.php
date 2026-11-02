<?php

namespace Tests\Feature;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Livewire\BookingWizard;
use App\Models\Booking;
use App\Models\CustomerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Alur booking publik lewat komponen Livewire (tamu yang sudah masuk akun; alur tamu belum login ada di CustomerAccountTest). */
class BookingWizardTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        RateLimiter::clear('booking:127.0.0.1');

        // Pemesanan online wajib akun pelanggan yang terverifikasi
        $account = CustomerAccount::query()->create(['name' => 'Akun Uji', 'email' => 'akun.uji@example.com', 'password' => 'rahasia123']);
        $account->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($account, 'customer');
    }

    private function wizard(array $query = [])
    {
        return Livewire::withQueryParams($query + ['check_in' => '2026-10-16', 'check_out' => '2026-10-18', 'guests' => 3])
            ->test(BookingWizard::class);
    }

    public function test_tamu_memesan_dari_awal_sampai_halaman_pembayaran(): void
    {
        $type = $this->makeUnitType(670000, 815000);

        $this->wizard()
            ->assertSet('step', 2)
            ->assertSee('Glamping Uji')
            ->assertSee('Rp1.630.000') // 2 malam weekend (Jum + Sab)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertSet('step', 3)
            ->set('name', 'Siti Aminah')
            ->set('phone', '0812-3456-7890')
            ->set('email', 'siti@example.com')
            ->set('agree', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $booking = Booking::query()->sole();
        $this->assertSame(BookingChannel::Web, $booking->channel);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(1630000, $booking->total);
        $this->assertSame(407500, $booking->dp_amount);
        $this->assertSame('081234567890', $booking->customer->phone);
    }

    public function test_pesan_error_ramah_dalam_bahasa_indonesia(): void
    {
        $type = $this->makeUnitType();

        $this->wizard()
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->set('name', '')
            ->set('phone', '12345')
            ->set('email', 'bukan-email')
            ->call('submit')
            ->assertHasErrors(['name', 'phone', 'email', 'agree'])
            ->assertSee('Nomor WhatsApp sepertinya belum benar')
            ->assertSee('Mohon setujui syarat');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_tidak_bisa_lanjut_bila_kapasitas_kurang_dari_jumlah_tamu(): void
    {
        $type = $this->makeUnitType(attributes: ['capacity_pax' => 2, 'total_units' => 5]);

        $this->wizard(['guests' => 5])
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertSet('step', 2)
            ->assertSee('belum cukup untuk 5 tamu');
    }

    public function test_jumlah_unit_tidak_bisa_melebihi_stok_tersisa(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 2]);

        $this->wizard()
            ->call('changeQty', $type->id, 1)
            ->call('changeQty', $type->id, 1)
            ->call('changeQty', $type->id, 1)
            ->assertSet('qty.'.$type->id, 2)
            ->assertSee('Tersisa 2 kamar');
    }

    public function test_kedatangan_kurang_dari_h3_hanya_bisa_lunas(): void
    {
        $type = $this->makeUnitType();

        $this->wizard(['check_in' => '2026-10-06', 'check_out' => '2026-10-07', 'guests' => 2])
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertSet('paymentOption', 'full')
            ->assertSee('harus langsung lunas');
    }

    public function test_tanggal_lampau_ditolak(): void
    {
        $this->makeUnitType();

        $this->wizard(['check_in' => '2026-10-01', 'check_out' => '2026-10-02'])
            ->assertSet('step', 1)
            ->call('toAccommodation')
            ->assertHasErrors('checkIn');
    }

    public function test_tipe_yang_tidak_dibuka_online_tidak_ditampilkan(): void
    {
        $this->makeUnitType(attributes: ['marketing_name' => 'Kavling Rahasia', 'is_bookable_online' => false]);

        $this->wizard()->assertDontSee('Kavling Rahasia');
    }

    public function test_honeypot_terisi_tidak_membuat_booking(): void
    {
        $type = $this->makeUnitType();

        $this->wizard()
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->set(['name' => 'Bot', 'phone' => '081234567890', 'email' => 'bot@example.com', 'agree' => true, 'website' => 'http://spam.test'])
            ->call('submit');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_percobaan_berlebihan_dibatasi(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 20]);

        foreach (range(1, 5) as $i) {
            RateLimiter::hit('booking:127.0.0.1', 60);
        }

        $this->wizard()
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->set(['name' => 'Siti', 'phone' => '081234567890', 'email' => 's@example.com', 'agree' => true])
            ->call('submit')
            ->assertSee('Terlalu banyak percobaan');

        $this->assertSame(0, Booking::query()->count());
    }
}
