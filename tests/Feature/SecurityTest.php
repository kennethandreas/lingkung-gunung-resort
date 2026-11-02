<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebhookLog;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Checklist keamanan dokumen teknis bab 7. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_keamanan_ada_di_website_admin_dan_webhook(): void
    {
        foreach (['/', '/kelola/login'] as $url) {
            $response = $this->get($url);
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('X-Powered-By');
            $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        }

        $this->postJson('/webhook/midtrans', [])->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_hanya_dikirim_lewat_https(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_halaman_error_ramah_dalam_bahasa_indonesia_tanpa_detail_teknis(): void
    {
        config(['app.debug' => false]);

        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke beranda')
            ->assertDontSee('NotFoundHttpException');

        $this->get('/booking/LGTIDAKADA1')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_login_admin_dibatasi_setelah_lima_kali_gagal(): void
    {
        User::factory()->create(['email' => 'staf@resort.test']);

        $component = Livewire::test(Login::class);
        foreach (range(1, 5) as $i) {
            $component->fillForm(['email' => 'staf@resort.test', 'password' => 'salah-'.$i])->call('authenticate');
        }

        $component->fillForm(['email' => 'staf@resort.test', 'password' => 'salah-lagi'])
            ->call('authenticate')
            ->assertNotified();

        $this->assertGuest();
    }

    public function test_rahasia_2fa_disimpan_terenkripsi_di_database(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $raw = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $raw);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->fresh()->getAppAuthenticationSecret());
    }

    public function test_log_webhook_lebih_dari_setahun_dibersihkan(): void
    {
        $old = WebhookLog::query()->create(['order_id' => 'LAMA', 'payload' => []]);
        $old->forceFill(['created_at' => now()->subYear()->subDay()])->save();
        WebhookLog::query()->create(['order_id' => 'BARU', 'payload' => []]);

        $this->artisan('model:prune', ['--model' => [WebhookLog::class]])->assertSuccessful();

        $this->assertSame(['BARU'], WebhookLog::query()->pluck('order_id')->all());
    }

    public function test_jadwal_backup_dan_pembersihan_terdaftar(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:run')
            ->expectsOutputToContain('backup:clean')
            ->expectsOutputToContain('backup:monitor')
            ->expectsOutputToContain('model:prune')
            ->expectsOutputToContain('bookings:expire-holds')
            ->assertSuccessful();
    }

    public function test_backup_dienkripsi_dan_tidak_menyimpan_kode_program(): void
    {
        config(['backup.backup.password' => 'rahasia-uji']);

        $this->assertSame('default', config('backup.backup.encryption'));
        $this->assertSame([storage_path('app')], config('backup.backup.source.files.include'));
        $this->assertContains(env('DB_CONNECTION', 'mysql'), config('backup.backup.source.databases'));
    }

    public function test_cek_produksi_gagal_di_lingkungan_development(): void
    {
        $this->artisan('resort:cek-produksi')
            ->expectsOutputToContain('APP_DEBUG')
            ->expectsOutputToContain('Hak user database')
            ->assertFailed();
    }
}
