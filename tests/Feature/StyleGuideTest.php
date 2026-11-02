<?php

namespace Tests\Feature;

use Database\Seeders\UnitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StyleGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_guide_menampilkan_komponen_dan_data_akomodasi(): void
    {
        $this->seed(UnitTypeSeeder::class);

        $this->get('/style-guide')
            ->assertOk()
            ->assertSee('lang="id"', false)
            ->assertSee('noindex', false)
            ->assertSee('Glamping Imah Lembah')
            ->assertSee('Rp670.000')
            ->assertSee('Lompat ke konten')
            ->assertSee('images/placeholder/glamping-luar-1600.webp', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Foto menyusul');
    }
}
