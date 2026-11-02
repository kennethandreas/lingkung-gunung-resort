<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Tahap ux-redesign (docs/AUDIT-UX.md): navigasi jelas, sudut tajam, alur pesan transparan. */
class UxRedesignTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
    }

    public function test_menu_utama_berbahasa_indonesia_yang_jelas(): void
    {
        $this->seed(ContentSeeder::class);

        $response = $this->get(route('home'))->assertOk();
        foreach (['Menginap', 'Acara &amp; Rombongan', 'Aktivitas', 'Kuliner', 'Galeri', 'Lokasi &amp; Kontak'] as $label) {
            $response->assertSee($label, false);
        }
        $response->assertSee('Resort &amp; adventure camp di kaki Gunung Gede Pangrango', false)
            ->assertDontSee('The Ecosystem of Experience');
    }

    public function test_menu_aktif_ditandai_aria_current(): void
    {
        $this->makeUnitType();

        $this->get(route('akomodasi.index'))->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Kamar &amp; glamping', false)
            ->assertSee('Semua pilihan dalam satu tabel');
    }

    public function test_tampilan_publik_tidak_memakai_sudut_pil_atau_huruf_tulisan_tangan(): void
    {
        $offenders = collect(File::allFiles(resource_path('views')))
            ->reject(fn ($f) => str_contains($f->getPathname(), DIRECTORY_SEPARATOR.'filament'.DIRECTORY_SEPARATOR) || str_contains($f->getPathname(), DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR) || str_contains($f->getFilename(), 'style-guide'))
            ->filter(fn ($f) => preg_match('/\brounded-(full|xl|2xl|3xl)\b|Kaushan/', File::get($f->getPathname())))
            ->map(fn ($f) => $f->getRelativePathname())
            ->values();

        $this->assertSame([], $offenders->all());
    }

    public function test_wizard_memberi_tahu_sejak_awal_bahwa_akun_dibutuhkan(): void
    {
        $this->get(route('pesan'))->assertOk()->assertSee('Pilih tanggal dan akomodasi tanpa akun.');
    }

    public function test_grafik_tren_pindah_ke_laporan_dan_menu_admin_dikelompokkan_per_pekerjaan(): void
    {
        $this->seed(RolesSeeder::class);
        $admin = tap(User::factory()->create())->assignRole('admin');

        $this->actingAs($admin)->get(Reports::getUrl())->assertOk();
        $this->actingAs($admin)->get('/'.config('resort.admin_path'))->assertOk()
            ->assertSee('Operasional Harian')
            ->assertSee('Laporan &amp; Pengaturan', false);
    }
}
