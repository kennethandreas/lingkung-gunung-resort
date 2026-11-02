<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Models\Article;
use App\Models\GalleryPhoto;
use App\Models\UnitType;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\OfficialMediaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/** Tahap transformasi (Okt 2026): artikel, foto resmi, logo, navigasi baru. */
class TransformationTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        // WAJIB: media library memakai storage/app/public/{id}; tanpa disk palsu, ID media test bisa menimpa/menghapus foto di laptop
        Storage::fake('public');
        Storage::fake('local');
    }

    // ------------------------------------------------------------------ artikel

    public function test_artikel_terbit_tampil_dan_draf_atau_terjadwal_tersembunyi(): void
    {
        $terbit = Article::query()->create(['slug' => 'kabar-baru', 'title' => 'Kabar baru dari resort', 'category' => 'kabar', 'excerpt' => 'Ringkasan kabar.', 'body' => '<p>Isi artikel <script>alert(1)</script>lengkap.</p>', 'is_published' => true, 'published_at' => now()->subDay()]);
        Article::query()->create(['slug' => 'masih-draf', 'title' => 'Artikel masih draf', 'category' => 'tips', 'body' => '<p>x</p>', 'is_published' => false, 'published_at' => now()->subDay()]);
        Article::query()->create(['slug' => 'terjadwal', 'title' => 'Artikel terjadwal', 'category' => 'tips', 'body' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->addWeek()]);

        $this->get(route('artikel.index'))->assertOk()
            ->assertSee('Kabar baru dari resort')
            ->assertDontSee('Artikel masih draf')
            ->assertDontSee('Artikel terjadwal');

        $this->get(route('artikel.show', $terbit))->assertOk()
            ->assertSee('Isi artikel', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('"@type":"Article"', false);

        $this->get('/artikel/masih-draf')->assertNotFound();
        $this->get('/artikel/terjadwal')->assertNotFound();
    }

    public function test_filter_kategori_artikel_dan_kategori_tidak_dikenal_diabaikan(): void
    {
        Article::query()->create(['slug' => 'a', 'title' => 'Tips camping hemat', 'category' => 'tips', 'body' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay()]);
        Article::query()->create(['slug' => 'b', 'title' => 'Kabar renovasi aula', 'category' => 'kabar', 'body' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay()]);

        $this->get(route('artikel.index', ['kategori' => 'tips']))->assertOk()->assertSee('Tips camping hemat')->assertDontSee('Kabar renovasi aula');
        $this->get(route('artikel.index', ['kategori' => '<script>']))->assertOk()->assertSee('Tips camping hemat')->assertSee('Kabar renovasi aula');
    }

    public function test_seeder_artikel_mencantumkan_sumber_resmi_dan_tidak_menimpa_suntingan(): void
    {
        $this->seed(ArticleSeeder::class);
        $this->assertSame(4, Article::query()->published()->count());
        $this->assertTrue(Article::query()->get()->every(fn (Article $a) => str_starts_with((string) $a->source_url, 'https://elji.id/')));

        Article::query()->where('slug', 'tips-berkemah-dengan-camper-van')->update(['title' => 'Judul diubah admin']);
        $this->seed(ArticleSeeder::class);
        $this->assertSame('Judul diubah admin', Article::query()->where('slug', 'tips-berkemah-dengan-camper-van')->value('title'));
    }

    public function test_admin_bisa_menulis_artikel_dan_sales_hanya_melihat(): void
    {
        $this->seed(RolesSeeder::class);
        $admin = tap(User::factory()->create())->assignRole('admin');

        Livewire::actingAs($admin)->test(CreateArticle::class)
            ->fillForm([
                'title' => 'Jadwal libur akhir tahun',
                'slug' => 'jadwal-libur-akhir-tahun',
                'category' => 'kabar',
                'excerpt' => 'Info operasional akhir tahun.',
                'body' => '<p>Resort tetap buka.</p>',
                'is_published' => true,
                'published_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = Article::query()->where('slug', 'jadwal-libur-akhir-tahun')->sole();
        $this->assertTrue($article->author->is($admin));
        $this->get(route('artikel.show', $article))->assertOk()->assertSee('Resort tetap buka.');

        $sales = tap(User::factory()->create())->assignRole('sales');
        $this->actingAs($sales)->get(ArticleResource::getUrl('index'))->assertOk();
        $this->actingAs($sales)->get(ArticleResource::getUrl('create'))->assertForbidden();
    }

    public function test_slug_artikel_wajib_unik(): void
    {
        $this->seed(RolesSeeder::class);
        Article::query()->create(['slug' => 'sudah-ada', 'title' => 'Lama', 'category' => 'kabar', 'body' => '<p>x</p>']);

        Livewire::actingAs(tap(User::factory()->create())->assignRole('admin'))->test(CreateArticle::class)
            ->fillForm(['title' => 'Baru', 'slug' => 'sudah-ada', 'category' => 'kabar', 'body' => '<p>y</p>'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    // ------------------------------------------------------------------ foto resmi & logo

    public function test_aset_foto_resmi_dan_logo_tersedia_dengan_catatan_sumber(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('images/resort/SUMBER.json')), true);
        $this->assertGreaterThanOrEqual(40, count($manifest));

        foreach ($manifest as $name => $info) {
            $this->assertStringStartsWith('https://elji.id/wp-content/uploads/', $info['sumber'], $name);
            foreach ([480, 960, 1600] as $w) {
                $this->assertFileExists(public_path("images/resort/{$name}-{$w}.webp"));
            }
        }

        foreach (['logo-mark.png', 'logo-mark-terang.png', 'ikon-512.png', 'apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path("images/brand/{$file}"));
        }
    }

    public function test_seeder_foto_resmi_mengganti_gambar_sementara_tapi_tidak_unggahan_admin(): void
    {
        $glamping = UnitType::query()->create(['slug' => 'glamourcamp', 'name' => 'Glamourcamp', 'marketing_name' => 'Glamping Imah Lembah', 'capacity_pax' => 4, 'total_units' => 10, 'pricing_unit' => 'room', 'is_active' => true]);
        $deluxe = UnitType::query()->create(['slug' => 'pangrango-deluxe', 'name' => 'Pangrango Deluxe', 'marketing_name' => 'Pangrango Deluxe', 'capacity_pax' => 2, 'total_units' => 5, 'pricing_unit' => 'room', 'is_active' => true]);

        // glamping: gambar brosur sementara; deluxe: foto unggahan admin
        $glamping->addMedia(UploadedFile::fake()->image('brosur.jpg', 800, 600))->withCustomProperties(['placeholder' => true])->toMediaCollection('photos');
        $deluxe->addMedia(UploadedFile::fake()->image('admin.jpg', 800, 600))->toMediaCollection('photos');

        $this->seed(OfficialMediaSeeder::class);

        $glampingPhotos = $glamping->refresh()->getMedia('photos');
        $this->assertGreaterThanOrEqual(3, $glampingPhotos->count());
        $this->assertTrue($glampingPhotos->every(fn ($m) => $m->getCustomProperty('resmi') && ! $m->getCustomProperty('placeholder')));

        $deluxePhotos = $deluxe->refresh()->getMedia('photos');
        $this->assertCount(1, $deluxePhotos);
        $this->assertSame('admin.jpg', $deluxePhotos->first()->file_name);

        $this->assertGreaterThanOrEqual(20, GalleryPhoto::query()->visible()->count());

        // diulang: tidak menambah foto/galeri ganda
        $count = GalleryPhoto::query()->count();
        $this->seed(OfficialMediaSeeder::class);
        $this->assertSame($count, GalleryPhoto::query()->count());
        $this->assertCount($glampingPhotos->count(), $glamping->refresh()->getMedia('photos'));
    }

    // ------------------------------------------------------------------ navigasi & halaman publik

    public function test_beranda_memakai_foto_resmi_logo_dan_menu_dropdown(): void
    {
        $this->seed(ContentSeeder::class);
        $this->makeUnitType(500000, 600000, ['marketing_name' => 'Glamping Uji']);

        $this->get(route('home'))->assertOk()
            ->assertSee('images/brand/logo-mark', false)
            ->assertSee('images/resort/deck-lembah', false)
            ->assertSee('id="menu-menginap"', false)
            ->assertSee('Glamping Uji')
            ->assertSee('Apa rencana kunjungan Anda?')
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee(route('artikel.index'), false);
    }

    public function test_halaman_publik_utama_terbuka_dan_sitemap_berisi_artikel(): void
    {
        $this->seed(ContentSeeder::class);
        Article::query()->create(['slug' => 'masuk-sitemap', 'title' => 'Masuk sitemap', 'category' => 'kabar', 'body' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay()]);

        foreach (['home', 'akomodasi.index', 'play', 'learn', 'gather', 'taste', 'galeri', 'artikel.index', 'rute', 'kontak'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('play'))->assertSee('id="adventure"', false)->assertSee('id="eco-culture"', false);
        $this->get(route('sitemap'))->assertOk()->assertSee(route('artikel.show', 'masuk-sitemap'), false);
    }
}
