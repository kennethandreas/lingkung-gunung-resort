<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Livewire\BookingWizard;
use App\Models\Booking;
use App\Models\CustomerAccount;
use App\Models\UnitType;
use App\Notifications\Customer\ResetPasswordNotification;
use App\Notifications\Customer\VerifyEmailNotification;
use App\Services\BookingService;
use App\Services\CustomerAccountService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Akun pelanggan: daftar, verifikasi, login, reset password, dashboard, booking, tersimpan, profil. */
class CustomerAccountTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        RateLimiter::clear('booking:127.0.0.1');
        Notification::fake();
    }

    private function account(array $attributes = []): CustomerAccount
    {
        $verifiedAt = array_key_exists('email_verified_at', $attributes) ? $attributes['email_verified_at'] : now();
        unset($attributes['email_verified_at']);

        $account = CustomerAccount::query()->create($attributes + [
            'name' => 'Siti Aminah',
            'email' => 'siti'.CustomerAccount::query()->count().'@example.com',
            'password' => 'rahasia123',
        ]);
        $account->forceFill(['email_verified_at' => $verifiedAt])->save();

        return $account;
    }

    private function ownedBooking(CustomerAccount $account, UnitType $type, string $in = '2026-10-12', string $out = '2026-10-13'): Booking
    {
        return $this->holdBooking($type, $in, $out, overrides: ['customer_account_id' => $account->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pendaftaran & verifikasi email
    |--------------------------------------------------------------------------
    */

    public function test_pendaftaran_membuat_akun_dan_mengirim_email_verifikasi(): void
    {
        $this->post(route('akun.daftar.store'), [
            'name' => 'Siti Aminah',
            'email' => 'Siti@Example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('akun.verifikasi.notice'));

        $account = CustomerAccount::query()->sole();
        $this->assertSame('siti@example.com', $account->email);
        $this->assertTrue(Hash::check('rahasia123', $account->password));
        $this->assertNotSame('rahasia123', $account->password);
        $this->assertNull($account->email_verified_at);
        $this->assertAuthenticatedAs($account, 'customer');
        Notification::assertSentTo($account, VerifyEmailNotification::class);
    }

    public function test_email_ganda_dan_password_lemah_ditolak(): void
    {
        $this->account(['email' => 'siti@example.com']);

        $this->from(route('akun.daftar'))->post(route('akun.daftar.store'), [
            'name' => 'Siti Lain',
            'email' => 'SITI@example.com',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertRedirect(route('akun.daftar'))
            ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan "Lupa password".', 'password']);

        $this->assertSame(1, CustomerAccount::query()->count());
        $this->assertGuest('customer');
    }

    public function test_akun_belum_verifikasi_tidak_bisa_membuka_fitur_akun(): void
    {
        $account = $this->account(['email_verified_at' => null]);
        $this->actingAs($account, 'customer');

        foreach (['akun.dashboard', 'akun.booking.index', 'akun.tersimpan'] as $route) {
            $this->get(route($route))->assertRedirect(route('akun.verifikasi.notice'));
        }

        $this->post(route('akun.tersimpan.toggle', $this->makeUnitType()))->assertRedirect(route('akun.verifikasi.notice'));
        $this->get(route('akun.verifikasi.notice'))->assertOk()->assertSee('Kirim ulang tautan');
        $this->get(route('akun.profil'))->assertOk()->assertSee('Email belum diverifikasi');
    }

    public function test_saat_verifikasi_dimatikan_akun_langsung_bisa_dipakai_tanpa_email(): void
    {
        config(['resort.customer_email_verification' => false]);
        $type = $this->makeUnitType();

        $this->post(route('akun.daftar.store'), [
            'name' => 'Siti Aminah', 'email' => 'siti@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('akun.dashboard'));

        $account = CustomerAccount::query()->sole();
        Notification::assertNothingSent();
        $this->assertNull($account->email_verified_at);
        $this->assertTrue($account->hasVerifiedEmail());

        $this->get(route('akun.dashboard'))->assertOk()->assertDontSee('Terverifikasi');
        $this->get(route('akun.booking.index'))->assertOk();
        $this->postJson(route('akun.tersimpan.toggle', $type))->assertJson(['saved' => true]);
        $this->get(route('akun.profil'))->assertOk()->assertDontSee('Email belum diverifikasi')->assertDontSee('diverifikasi ulang');
        $this->get(route('akun.verifikasi.notice'))->assertRedirect(route('akun.dashboard'));

        // Ganti email: tidak diminta verifikasi ulang
        $this->put(route('akun.profil.update'), ['name' => 'Siti', 'email' => 'siti.baru@example.com'])->assertRedirect();
        $this->get(route('akun.tersimpan'))->assertOk();
        Notification::assertNothingSent();

        // Booking saat login tetap otomatis masuk akun
        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])
            ->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->set('phone', '081234567890')
            ->set('agree', true)
            ->call('submit')
            ->assertRedirect();
        $this->assertSame($account->id, Booking::query()->sole()->customer_account_id);

        // Saat verifikasi dinyalakan lagi (produksi), akun yang belum verifikasi wajib verifikasi
        config(['resort.customer_email_verification' => true]);
        $this->get(route('akun.dashboard'))->assertRedirect(route('akun.verifikasi.notice'));
    }

    public function test_tautan_verifikasi_memverifikasi_email_dan_tautan_palsu_ditolak(): void
    {
        $account = $this->account(['email_verified_at' => null]);
        $valid = URL::temporarySignedRoute('akun.verifikasi.verify', now()->addHour(), ['id' => $account->id, 'hash' => sha1($account->email)]);
        $wrongHash = URL::temporarySignedRoute('akun.verifikasi.verify', now()->addHour(), ['id' => $account->id, 'hash' => sha1('lain@example.com')]);

        $this->get(route('akun.verifikasi.verify', ['id' => $account->id, 'hash' => sha1($account->email)]))->assertForbidden(); // tanpa tanda tangan
        $this->get($wrongHash)->assertForbidden();
        $this->assertFalse($account->fresh()->hasVerifiedEmail());

        $this->actingAs($account, 'customer')->get($valid)->assertRedirect(route('akun.dashboard'));
        $this->assertTrue($account->fresh()->hasVerifiedEmail());

        // Tautan kedaluwarsa
        $expired = URL::temporarySignedRoute('akun.verifikasi.verify', now()->addHour(), ['id' => $account->id, 'hash' => sha1($account->email)]);
        $this->travel(2)->hours();
        $this->get($expired)->assertForbidden();
    }

    public function test_kirim_ulang_verifikasi_dibatasi(): void
    {
        $account = $this->account(['email_verified_at' => null]);
        $this->actingAs($account, 'customer');

        $this->post(route('akun.verifikasi.kirim'))->assertSessionHas('status');
        Notification::assertSentToTimes($account, VerifyEmailNotification::class, 1);

        $this->post(route('akun.verifikasi.kirim'));
        $this->post(route('akun.verifikasi.kirim'));
        $this->post(route('akun.verifikasi.kirim'))->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Login, logout, lupa password
    |--------------------------------------------------------------------------
    */

    public function test_login_dan_logout(): void
    {
        $account = $this->account(['email' => 'siti@example.com']);

        $this->from(route('akun.masuk'))->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'salah123'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest('customer');

        $this->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'rahasia123'])
            ->assertRedirect(route('akun.dashboard'));
        $this->assertAuthenticatedAs($account, 'customer');
        $this->assertGuest('web'); // tidak ikut login sebagai staf

        $this->get(route('akun.masuk'))->assertRedirect(route('akun.dashboard'));

        $this->post(route('akun.keluar'))->assertRedirect(route('home'));
        $this->assertGuest('customer');
    }

    public function test_login_dibatasi_setelah_lima_kali_gagal(): void
    {
        $this->account(['email' => 'siti@example.com']);

        foreach (range(1, 5) as $i) {
            $this->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'salah'.$i]);
        }

        $this->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('customer');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
    }

    public function test_lupa_password_dan_atur_ulang(): void
    {
        $account = $this->account(['email' => 'siti@example.com', 'email_verified_at' => null]);

        // Email tidak terdaftar: pesan sama (tidak membocorkan daftar pelanggan)
        $this->post(route('akun.password.email'), ['email' => 'tidakada@example.com'])->assertSessionHas('status');
        $this->post(route('akun.password.email'), ['email' => 'siti@example.com'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($account, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return str_contains($notification->toMail(CustomerAccount::query()->first())->actionUrl, '/akun/reset-password/');
        });

        $this->post(route('akun.password.update'), [
            'token' => 'token-palsu', 'email' => 'siti@example.com', 'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHasErrors('email');

        $this->post(route('akun.password.update'), [
            'token' => $token, 'email' => 'siti@example.com', 'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertRedirect(route('akun.masuk'));

        $this->assertTrue(Hash::check('baru12345', $account->fresh()->password));
        $this->assertTrue($account->fresh()->hasVerifiedEmail());

        $this->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'baru12345'])->assertRedirect(route('akun.dashboard'));
    }

    public function test_akun_pelanggan_tidak_bisa_masuk_admin_panel(): void
    {
        $this->actingAs($this->account(), 'customer')
            ->get('/kelola')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard & riwayat booking
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_menampilkan_perjalanan_berikutnya_dan_keadaan_kosong(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->get(route('akun.dashboard'))->assertOk()->assertSee('Belum ada perjalanan yang akan datang');

        $booking = $this->ownedBooking($account, $this->makeUnitType());

        $this->get(route('akun.dashboard'))->assertOk()
            ->assertSee('Perjalanan berikutnya')
            ->assertSee('Glamping Uji')
            ->assertSee($booking->code)
            ->assertSee('Bayar DP Rp125.000'); // tagihan = DP 25%, bukan total

        // Hold habis: tombol bayar tidak ditawarkan lagi
        $this->travel(61)->minutes();
        $this->get(route('akun.dashboard'))->assertDontSee('Bayar DP');
        $this->get(route('akun.booking.show', $booking->code))->assertDontSee('Bayar DP')->assertSee('Halaman status booking');
    }

    public function test_riwayat_booking_dikelompokkan_dan_menampilkan_data_lengkap(): void
    {
        $account = $this->account();
        $type = $this->makeUnitType(attributes: ['total_units' => 5]);
        $bookings = app(BookingService::class);

        $upcoming = $this->ownedBooking($account, $type, '2026-10-12', '2026-10-14');
        $cancelled = $this->ownedBooking($account, $type, '2026-10-20', '2026-10-21');
        $bookings->cancel($cancelled, 'Batal uji');
        $completed = $this->holdBooking($type, '2026-10-06', '2026-10-07', overrides: ['customer_account_id' => $account->id, 'payment_option' => 'full']);
        $bookings->confirm($completed);

        $this->travelTo('2026-10-09 10:00:00');
        $groups = app(CustomerAccountService::class)->groupedBookings($account);
        $this->assertSame([$upcoming->id], $groups['upcoming']->pluck('id')->all());
        $this->assertSame([$completed->id], $groups['completed']->pluck('id')->all());
        $this->assertSame([$cancelled->id], $groups['cancelled']->pluck('id')->all());

        $this->actingAs($account, 'customer')->get(route('akun.booking.index'))->assertOk()
            ->assertSee($upcoming->code)->assertSee($completed->code)->assertSee($cancelled->code)
            ->assertSee('Akan datang')->assertSee('Selesai')->assertSee('Dibatalkan');

        $this->get(route('akun.booking.show', $upcoming->code))->assertOk()
            ->assertSee($upcoming->code)
            ->assertSee('Glamping Uji')
            ->assertSee('12 Oktober 2026')
            ->assertSee('14 Oktober 2026')
            ->assertSee('2 orang')
            ->assertSee('Rp1.000.000')
            ->assertSee(BookingStatus::Pending->getLabel())
            ->assertSee('Belum dibayar')
            ->assertSee(route('booking.show', $upcoming));
    }

    public function test_pelanggan_tidak_bisa_melihat_booking_orang_lain(): void
    {
        $type = $this->makeUnitType();
        $mine = $this->account();
        $other = $this->account();
        $othersBooking = $this->ownedBooking($other, $type);
        $guestBooking = $this->holdBooking($type, '2026-10-14', '2026-10-15');

        $this->actingAs($mine, 'customer');
        $this->get(route('akun.booking.show', $othersBooking->code))->assertNotFound();
        $this->get(route('akun.booking.show', $guestBooking->code))->assertNotFound();
        $this->get(route('akun.booking.index'))->assertDontSee($othersBooking->code)->assertDontSee($guestBooking->code);
    }

    public function test_booking_lewat_wizard_saat_login_otomatis_masuk_akun(): void
    {
        $type = $this->makeUnitType();
        $account = $this->account(['phone' => '081298765432']);
        $this->actingAs($account, 'customer');

        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])
            ->test(BookingWizard::class)
            ->assertSet('name', 'Siti Aminah')
            ->assertSet('email', $account->email)
            ->assertSet('phone', '081298765432')
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->set('agree', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame($account->id, Booking::query()->sole()->customer_account_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Pemesanan online wajib akun
    |--------------------------------------------------------------------------
    */

    public function test_tamu_bisa_cek_harga_tetapi_diminta_masuk_saat_isi_data_dan_pilihan_tersimpan(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 5]);
        $query = ['check_in' => '2026-10-12', 'check_out' => '2026-10-14', 'guests' => 4];

        // Langkah 1-2 bebas tanpa akun
        $this->get(route('pesan', $query))->assertOk()->assertSee('Anda akan diminta masuk atau daftar (gratis', false);

        Livewire::withQueryParams($query)->test(BookingWizard::class)
            ->assertSet('step', 2)
            ->call('changeQty', $type->id, 2)
            ->set('promoCode', 'HEMAT')
            ->call('toCustomer')
            ->assertRedirect(route('akun.masuk'));

        $intended = session('url.intended');
        parse_str((string) parse_url($intended, PHP_URL_QUERY), $saved);
        $this->assertSame(['check_in' => '2026-10-12', 'check_out' => '2026-10-14', 'guests' => '4', 'pilih' => [$type->id => '2'], 'promo' => 'HEMAT', 'lanjut' => '1'], $saved);
        $this->assertStringContainsString('Tanggal dan akomodasi pilihan Anda tersimpan', session('notice'));

        // Masuk → kembali ke wizard dengan pilihan yang sama, langsung di langkah data pemesan
        $account = $this->account(['email' => 'siti@example.com']);
        $this->post(route('akun.masuk.store'), ['email' => 'siti@example.com', 'password' => 'rahasia123'])->assertRedirect($intended);

        Livewire::withQueryParams($saved)->test(BookingWizard::class)
            ->assertSet('step', 3)
            ->assertSet('qty.'.$type->id, 2)
            ->assertSet('guests', 4)
            ->assertSet('promoCode', 'HEMAT')
            ->assertSet('email', $account->email)
            ->assertSee('Memesan sebagai');
    }

    public function test_daftar_dari_wizard_kembali_ke_wizard(): void
    {
        config(['resort.customer_email_verification' => false]);
        $type = $this->makeUnitType();

        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertRedirect(route('akun.masuk'));
        $intended = session('url.intended');

        $this->post(route('akun.daftar.store'), ['name' => 'Siti Aminah', 'email' => 'siti@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect($intended);
    }

    public function test_akun_belum_verifikasi_diminta_verifikasi_sebelum_memesan(): void
    {
        $type = $this->makeUnitType();
        $this->actingAs($this->account(['email_verified_at' => null]), 'customer');

        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->call('toCustomer')
            ->assertRedirect(route('akun.verifikasi.notice'));

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_server_menolak_kirim_booking_tanpa_login_walau_langkah_dimanipulasi(): void
    {
        $type = $this->makeUnitType();

        // Tamu memaksa langkah 3 dari browser (properti publik Livewire bisa diubah klien)
        Livewire::withQueryParams(['check_in' => '2026-10-12', 'check_out' => '2026-10-13', 'guests' => 2])->test(BookingWizard::class)
            ->call('changeQty', $type->id, 1)
            ->set(['step' => 3, 'name' => 'Siti', 'phone' => '081234567890', 'email' => 's@example.com', 'agree' => true])
            ->call('submit')
            ->assertRedirect(route('akun.masuk'));

        $this->assertSame(0, Booking::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Halaman status / bayar / e-ticket /booking/{kode}
    |--------------------------------------------------------------------------
    */

    public function test_halaman_booking_milik_akun_hanya_untuk_pemiliknya(): void
    {
        $type = $this->makeUnitType();
        $owner = $this->account(['email' => 'pemilik@example.com']);
        $booking = $this->ownedBooking($owner, $type);
        app(BookingService::class)->confirm($booking);
        $booking->forceFill(['status' => BookingStatus::FullyPaid, 'paid_amount' => $booking->total])->save(); // e-ticket terbit

        // Tamu: diminta masuk, lalu kembali ke halaman booking
        $this->get(route('booking.show', $booking))->assertRedirect(route('akun.masuk'));
        $this->get(route('booking.eticket', $booking))->assertRedirect(route('akun.masuk'));
        $this->postJson(route('booking.pay', $booking))->assertUnauthorized();

        $this->get(route('booking.show', $booking));
        $this->post(route('akun.masuk.store'), ['email' => 'pemilik@example.com', 'password' => 'rahasia123'])
            ->assertRedirect(route('booking.show', $booking));
        $this->get(route('booking.show', $booking))->assertOk()->assertSee($booking->code);
        $this->get(route('booking.eticket', $booking))->assertOk();

        // Akun lain: 404 (tidak membocorkan bahwa booking ada)
        $this->actingAs($this->account(), 'customer');
        $this->get(route('booking.show', $booking))->assertNotFound();
        $this->get(route('booking.eticket', $booking))->assertNotFound();
        $this->postJson(route('booking.pay', $booking))->assertNotFound();
    }

    public function test_booking_tanpa_akun_dari_staf_tetap_dibuka_lewat_link_kode(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13'); // tanpa akun (mis. walk-in/telepon)

        $this->get(route('booking.show', $booking))->assertOk()->assertSee($booking->code);
        $this->actingAs($this->account(), 'customer')->get(route('booking.show', $booking))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Batas pelanggan vs staf
    |--------------------------------------------------------------------------
    */

    public function test_pelanggan_tidak_bisa_membuka_fitur_staf(): void
    {
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');
        $payment = $booking->payments()->create(['type' => 'dp', 'method' => 'transfer', 'amount' => 125000, 'status' => 'pending', 'proof_path' => 'bukti-transfer/x.jpg']);

        $this->actingAs($this->account(), 'customer');
        $this->get('/kelola')->assertRedirect(route('filament.admin.auth.login'));
        $this->get('/kelola/bookings')->assertRedirect(route('filament.admin.auth.login'));
        $this->get(route('admin.payment-proof', $payment))->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest('web');
    }

    public function test_navigasi_menyesuaikan_status_login(): void
    {
        $type = $this->makeUnitType();

        foreach (['/', '/akomodasi', '/akomodasi/'.$type->slug, '/gather'] as $url) {
            $this->get($url)->assertSee('Masuk')->assertDontSee('Akun Saya');
        }

        $this->actingAs($this->account(), 'customer');
        foreach (['/', '/akomodasi', '/akomodasi/'.$type->slug, '/gather'] as $url) {
            $this->get($url)->assertSee('Akun Saya')->assertSee(route('akun.dashboard'));
        }
    }

    public function test_tautkan_booking_lama_butuh_kode_dan_nomor_yang_cocok(): void
    {
        $type = $this->makeUnitType();
        $account = $this->account(['email' => 'budi@example.com']); // email sama dengan booking: tetap TIDAK otomatis ditautkan
        $guestBooking = $this->holdBooking($type, '2026-10-12', '2026-10-13'); // HP 081234567890
        $this->actingAs($account, 'customer');

        $this->get(route('akun.booking.index'))->assertDontSee($guestBooking->code);

        $this->post(route('akun.booking.tautkan'), ['code' => $guestBooking->code, 'phone' => '081111111111'])
            ->assertSessionHasErrors('code', null, 'claim');
        $this->post(route('akun.booking.tautkan'), ['code' => 'LGTIDAKADA', 'phone' => '081234567890'])
            ->assertSessionHasErrors('code', null, 'claim');
        $this->assertNull($guestBooking->fresh()->customer_account_id);

        $this->post(route('akun.booking.tautkan'), ['code' => strtolower($guestBooking->code), 'phone' => '+62 812-3456-7890'])
            ->assertRedirect(route('akun.booking.show', $guestBooking->code));
        $this->assertSame($account->id, $guestBooking->fresh()->customer_account_id);

        // Booking yang sudah milik akun lain tidak bisa diambil
        $this->actingAs($this->account(), 'customer')
            ->post(route('akun.booking.tautkan'), ['code' => $guestBooking->code, 'phone' => '081234567890'])
            ->assertSessionHasErrors('code', null, 'claim');
        $this->assertSame($account->id, $guestBooking->fresh()->customer_account_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Akomodasi tersimpan
    |--------------------------------------------------------------------------
    */

    public function test_simpan_dan_hapus_akomodasi_tanpa_mempengaruhi_stok(): void
    {
        $type = $this->makeUnitType();
        $account = $this->account();
        $in = CarbonImmutable::parse('2026-10-12');
        $before = app(BookingService::class)->availability($in, $in->addDay())->firstWhere('unit_type.id', $type->id)['available'];

        $this->actingAs($account, 'customer');
        $this->postJson(route('akun.tersimpan.toggle', $type))->assertOk()->assertJson(['saved' => true]);

        $this->assertSame([$type->id], $account->savedUnitTypes()->pluck('unit_types.id')->all());
        $this->assertSame(0, Booking::query()->count());
        $this->assertSame($before, app(BookingService::class)->availability($in, $in->addDay())->firstWhere('unit_type.id', $type->id)['available']);

        $this->get(route('akun.tersimpan'))->assertOk()->assertSee('Glamping Uji');
        $this->get(route('akomodasi.show', $type))->assertOk()->assertSee('Tersimpan di akun Anda');

        // Tanpa JS (form biasa): kembali ke halaman sebelumnya
        $this->from(route('akun.tersimpan'))->post(route('akun.tersimpan.toggle', $type))->assertRedirect(route('akun.tersimpan'));
        $this->assertSame(0, $account->savedUnitTypes()->count());
        $this->get(route('akun.tersimpan'))->assertSee('Belum ada akomodasi tersimpan');
    }

    public function test_akomodasi_tersimpan_tidak_bisa_dobel_dan_terpisah_per_akun(): void
    {
        $type = $this->makeUnitType();
        $account = $this->account();
        $other = $this->account();
        $service = app(CustomerAccountService::class);

        $service->toggleSaved($account, $type);
        $service->toggleSaved($other, $type);
        $this->assertSame(1, DB::table('saved_unit_types')->where('customer_account_id', $account->id)->count());

        $this->expectException(QueryException::class);
        DB::table('saved_unit_types')->insert(['customer_account_id' => $account->id, 'unit_type_id' => $type->id]);
    }

    public function test_menghapus_simpanan_hanya_mempengaruhi_akun_sendiri(): void
    {
        $type = $this->makeUnitType();
        $account = $this->account();
        $other = $this->account();
        app(CustomerAccountService::class)->toggleSaved($other, $type);

        $this->actingAs($account, 'customer')->postJson(route('akun.tersimpan.toggle', $type))->assertJson(['saved' => true]);
        $this->postJson(route('akun.tersimpan.toggle', $type))->assertJson(['saved' => false]);

        $this->assertSame(1, $other->savedUnitTypes()->count());
    }

    public function test_tamu_yang_menyimpan_diarahkan_ke_halaman_masuk(): void
    {
        $type = $this->makeUnitType();

        $this->get(route('akomodasi.show', $type))->assertOk()->assertSee('Simpan akomodasi ini');
        $this->from(route('akomodasi.show', $type))->post(route('akun.tersimpan.toggle', $type))->assertRedirect(route('akun.masuk'));
        $this->postJson(route('akun.tersimpan.toggle', $type))->assertUnauthorized();
        $this->assertSame(0, DB::table('saved_unit_types')->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Profil
    |--------------------------------------------------------------------------
    */

    public function test_ubah_profil_dan_email_baru_wajib_verifikasi_ulang(): void
    {
        $account = $this->account(['email' => 'siti@example.com']);
        $this->actingAs($account, 'customer');

        $this->put(route('akun.profil.update'), ['name' => 'Siti Nurhaliza', 'email' => 'siti@example.com', 'phone' => '0812-9876-5432'])
            ->assertSessionHasNoErrors();
        $account->refresh();
        $this->assertSame('Siti Nurhaliza', $account->name);
        $this->assertSame('081298765432', $account->phone);
        $this->assertTrue($account->hasVerifiedEmail());
        Notification::assertNothingSentTo($account);

        $this->put(route('akun.profil.update'), ['name' => 'Siti Nurhaliza', 'email' => 'siti.baru@example.com', 'phone' => ''])
            ->assertRedirect(route('akun.verifikasi.notice'));
        $account->refresh();
        $this->assertSame('siti.baru@example.com', $account->email);
        $this->assertFalse($account->hasVerifiedEmail());
        Notification::assertSentTo($account, VerifyEmailNotification::class);

        // Email milik akun lain ditolak
        $this->account(['email' => 'dipakai@example.com']);
        $this->put(route('akun.profil.update'), ['name' => 'Siti', 'email' => 'dipakai@example.com'])->assertSessionHasErrors('email');
    }

    public function test_ganti_password_butuh_password_lama(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->put(route('akun.profil.password'), ['current_password' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->assertTrue(Hash::check('rahasia123', $account->fresh()->password));

        $this->put(route('akun.profil.password'), ['current_password' => 'rahasia123', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('baru12345', $account->fresh()->password));
    }

    public function test_kolom_terlindungi_tidak_bisa_diubah_lewat_form_profil(): void
    {
        $account = $this->account(['email_verified_at' => null]);

        $this->actingAs($account, 'customer')->put(route('akun.profil.update'), [
            'name' => 'Siti', 'email' => $account->email, 'email_verified_at' => now()->toDateTimeString(), 'password' => 'diretas123',
        ]);

        $this->assertFalse($account->fresh()->hasVerifiedEmail());
        $this->assertTrue(Hash::check('rahasia123', $account->fresh()->password));
    }

    /*
    |--------------------------------------------------------------------------
    | Website publik tetap terbuka tanpa akun
    |--------------------------------------------------------------------------
    */

    public function test_halaman_publik_tetap_bisa_dibuka_tanpa_akun(): void
    {
        $type = $this->makeUnitType();

        foreach (['/', '/akomodasi', '/akomodasi/'.$type->slug, '/pesan', '/akun/masuk', '/akun/daftar', '/akun/lupa-password'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/')->assertSee(route('akun.masuk'));
        $this->get(route('akun.dashboard'))->assertRedirect(route('akun.masuk'));
    }
}
