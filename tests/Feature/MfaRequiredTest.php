<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2FA wajib untuk login admin (dokumen teknis bab 7).
 * Rute pemasangan 2FA hanya didaftarkan saat aplikasi menyala dengan 2FA wajib,
 * jadi kelas ini menyalakan aplikasi dengan ADMIN_REQUIRE_MFA=true.
 */
class MfaRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $this->setEnv('true');

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->setEnv('false');
    }

    private function setEnv(string $value): void
    {
        putenv("ADMIN_REQUIRE_MFA={$value}");
        $_ENV['ADMIN_REQUIRE_MFA'] = $value;
        $_SERVER['ADMIN_REQUIRE_MFA'] = $value;
    }

    public function test_admin_tanpa_2fa_diarahkan_ke_pemasangan_authenticator(): void
    {
        $this->seed(RolesSeeder::class);
        $owner = tap(User::factory()->create())->assignRole('owner');

        $this->actingAs($owner)
            ->get('/kelola')
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
    }

    public function test_admin_yang_sudah_memasang_2fa_bisa_masuk(): void
    {
        $this->seed(RolesSeeder::class);
        $owner = tap(User::factory()->create())->assignRole('owner');
        $owner->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $this->actingAs($owner)->get('/kelola')->assertOk();
    }
}
