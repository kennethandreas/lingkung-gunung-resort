<?php

namespace Tests\Feature;

use App\Livewire\BookingWizard;
use App\Models\CustomerAccount;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Tahap ux-booking-akun: wizard, masuk/daftar di tengah pemesanan, akun & status booking. */
class BookingAccountUxTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        Mail::fake();
        RateLimiter::clear('booking:127.0.0.1');
    }

    private function account(string $email = 'tamu.ux@example.com'): CustomerAccount
    {
        $account = CustomerAccount::query()->create(['name' => 'Tamu Uji', 'email' => $email, 'password' => 'rahasia123']);
        $account->forceFill(['email_verified_at' => now()])->save();

        return $account;
    }

    // ------------------------------------------------------------------ masuk/daftar di tengah pemesanan

    public function test_tamu_dari_wizard_melihat_pesanan_menunggu_di_halaman_masuk_dan_daftar(): void
    {
        $type = $this->makeUnitType(500000, 600000, ['marketing_name' => 'Glamping Uji']);

        Livewire::withQueryParams(['check_in' => '2026-10-16', 'check_out' => '2026-10-18', 'guests' => 3])
            ->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertRedirect(route('akun.masuk'));

        $this->get(route('akun.masuk'))->assertOk()
            ->assertSee('Pesanan Anda menunggu')
            ->assertSee('Glamping Uji')
            ->assertSee('2 malam · 3 tamu')
            ->assertSee('Buat akun baru (gratis)');

        // pindah ke halaman daftar: panel tetap ada (tidak hilang seperti pesan flash)
        $this->get(route('akun.daftar'))->assertOk()->assertSee('Pesanan Anda menunggu')->assertSee('Glamping Uji');

        // setelah daftar (verifikasi email wajib seperti produksi): pesanan tetap tersimpan & panel tetap tampil
        $this->post(route('akun.daftar.store'), ['name' => 'Tamu Baru', 'email' => 'baru.ux@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect(route('akun.verifikasi.notice'));
        $this->assertStringContainsString('lanjut=1', (string) session('url.intended'));
        $this->get(route('akun.verifikasi.notice'))->assertOk()->assertSee('Pesanan Anda menunggu');
    }

    public function test_panel_pesanan_tidak_muncul_tanpa_pesanan_tertunda(): void
    {
        $this->get(route('akun.masuk'))->assertOk()->assertDontSee('Pesanan Anda menunggu');

        // URL tujuan selain wizard (mis. halaman akun) tidak dianggap pesanan
        $this->withSession(['url.intended' => route('akun.booking.index')])->get(route('akun.masuk'))->assertDontSee('Pesanan Anda menunggu');

        // tanggal lampau / rusak diabaikan
        $this->withSession(['url.intended' => route('pesan', ['check_in' => '2026-01-01', 'check_out' => '2026-01-03'])])->get(route('akun.masuk'))->assertDontSee('Pesanan Anda menunggu');
    }

    // ------------------------------------------------------------------ wizard

    public function test_wizard_menampilkan_tinjauan_pesanan_dan_jumlah_yang_dibayar_sekarang(): void
    {
        $type = $this->makeUnitType(500000, 600000, ['marketing_name' => 'Glamping Uji']);
        $this->actingAs($this->account(), 'customer');

        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-14', 'guests' => 2])
            ->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertSet('step', 3)
            ->assertSee('Periksa pesanan Anda')
            ->assertSee('Dibayar sekarang')
            ->assertSee('Rp250.000') // DP 25% dari 2 malam weekday Rp1.000.000
            ->assertSee('kami tahan 60 menit')
            ->set('paymentOption', 'full')
            ->assertSee('Rp1.000.000');
    }

    public function test_tamu_tidak_login_diberi_tahu_sejak_langkah_pertama(): void
    {
        $this->get(route('pesan'))->assertOk()->assertSee('Pilih tanggal dan akomodasi tanpa akun.');
        $this->actingAs($this->account(), 'customer')->get(route('pesan'))->assertOk()->assertDontSee('Pilih tanggal dan akomodasi tanpa akun.');
    }

    // ------------------------------------------------------------------ akun

    public function test_dasbor_akun_menonjolkan_tagihan_yang_perlu_dibayar(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType(500000, 600000);
        $booking = $this->holdBooking($type, '2026-10-12', '2026-10-14', 1, ['customer_account_id' => $account->id]);

        $this->actingAs($account, 'customer')->get(route('akun.dashboard'))->assertOk()
            ->assertSee('Perlu tindakan (1)')
            ->assertSee('bayar DP Rp250.000', false)
            ->assertSee($booking->code)
            ->assertSee(route('booking.show', $booking), false);

        // setelah DP dibayar: tindakan berubah menjadi pelunasan dengan batas waktu
        app(PaymentService::class)->recordManualPayment($booking, 250000, User::factory()->create());
        $this->get(route('akun.booking.show', $booking->code))->assertOk()
            ->assertSee('Langkah Anda:')
            ->assertSee('Rp750.000')
            ->assertSee('paling lambat')
            ->assertSee('Tahapan booking')
            ->assertSee('Lihat e-ticket');
    }

    public function test_booking_kedaluwarsa_tidak_menampilkan_tagihan(): void
    {
        $account = $this->account();
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13', 1, ['customer_account_id' => $account->id]);
        $this->travel(61)->minutes();
        app(BookingService::class)->expireOverdueHolds();

        $this->actingAs($account, 'customer')->get(route('akun.dashboard'))->assertOk()->assertDontSee('Perlu tindakan');
        $this->get(route('akun.booking.show', $booking->code))->assertOk()
            ->assertDontSee('Langkah Anda:')
            ->assertSee('Pesan ulang tanggal yang sama');
    }

    public function test_hold_lewat_sebelum_penjadwal_jalan_tidak_menawarkan_bayar(): void
    {
        $account = $this->account();
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13', 1, ['customer_account_id' => $account->id]);
        $this->travel(61)->minutes(); // status masih Pending, hold sudah lewat

        $this->actingAs($account, 'customer');
        $this->get(route('booking.show', $booking))->assertOk()
            ->assertSee('Waktu pembayaran habis')
            ->assertDontSee('Langkah sekarang')
            ->assertDontSee('Selesaikan pembayaran dalam');
        $this->get(route('akun.booking.show', $booking->code))->assertOk()
            ->assertSee('Pesan ulang tanggal yang sama')
            ->assertDontSee('Langkah sekarang');
    }

    public function test_detail_booking_akun_lain_tetap_tertutup(): void
    {
        $owner = $this->account('pemilik@example.com');
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13', 1, ['customer_account_id' => $owner->id]);

        $this->actingAs($this->account('lain@example.com'), 'customer');
        $this->get(route('akun.booking.show', $booking->code))->assertNotFound();
        $this->get(route('booking.show', $booking))->assertNotFound();
    }

    // ------------------------------------------------------------------ halaman status & pembayaran

    public function test_halaman_status_menampilkan_tahapan_dan_umpan_balik_pembayaran(): void
    {
        $account = $this->account();
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-14', 1, ['customer_account_id' => $account->id]);

        $this->actingAs($account, 'customer')->get(route('booking.show', $booking))->assertOk()
            ->assertSee('Tahapan booking Anda')
            ->assertSee('Bayar DP')
            ->assertSee('Langkah sekarang')
            ->assertSee('Rp750.000 paling lambat') // sisa pelunasan = total - DP, bukan total penuh
            ->assertSee('Jendela pembayaran ditutup') // pesan saat popup Midtrans ditutup (Alpine x-show)
            ->assertSee('Semua booking saya');

        app(PaymentService::class)->recordManualPayment($booking, $booking->dp_amount, User::factory()->create());
        $this->get(route('booking.show', ['booking' => $booking, 'status' => 'berhasil']))->assertOk()
            ->assertSee('Pembayaran terverifikasi')
            ->assertSee('DP sudah diterima, unit Anda terkonfirmasi')
            ->assertSee('Unduh PDF');
    }
}
