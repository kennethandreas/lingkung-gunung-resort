<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\OccupancyCalendar;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\AdditionalItems\AdditionalItemResource;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\CalendarDays\CalendarDayResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\GalleryPhotos\GalleryPhotoResource;
use App\Filament\Resources\GalleryPhotos\Pages\CreateGalleryPhoto;
use App\Filament\Resources\GroupPackages\GroupPackageResource;
use App\Filament\Resources\MenuSets\MenuSetResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\Promos\PromoResource;
use App\Filament\Resources\UnitTypes\UnitTypeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WebhookLogs\WebhookLogResource;
use App\Models\GalleryPhoto;
use App\Models\GroupPackage;
use App\Models\MenuSet;
use App\Models\Page;
use App\Models\Program;
use App\Models\UnitType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use CreatesResortData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-05 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return tap(User::factory()->create())->assignRole($role);
    }

    public function test_admin_panel_tidak_memakai_alamat_admin_default(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/kelola/login')->assertOk();
    }

    public function test_owner_bisa_membuka_semua_halaman_admin(): void
    {
        $this->actingAs($this->userWithRole('owner'));

        $urls = [
            '/kelola',
            OccupancyCalendar::getUrl(),
            ManageSettings::getUrl(),
            BookingResource::getUrl('index'),
            BookingResource::getUrl('create'),
            CustomerResource::getUrl('index'),
            UnitTypeResource::getUrl('index'),
            UnitTypeResource::getUrl('edit', ['record' => UnitType::first()]),
            CalendarDayResource::getUrl('index'),
            PromoResource::getUrl('index'),
            GroupPackageResource::getUrl('index'),
            GroupPackageResource::getUrl('edit', ['record' => GroupPackage::first()]),
            MenuSetResource::getUrl('index'),
            MenuSetResource::getUrl('edit', ['record' => MenuSet::first()]),
            ActivityResource::getUrl('index'),
            AdditionalItemResource::getUrl('index'),
            UserResource::getUrl('index'),
            ActivityLogResource::getUrl('index'),
            GalleryPhotoResource::getUrl('index'),
            GalleryPhotoResource::getUrl('create'),
            ProgramResource::getUrl('index'),
            ProgramResource::getUrl('edit', ['record' => Program::first()]),
            PageResource::getUrl('index'),
            PageResource::getUrl('edit', ['record' => Page::first()]),
            FaqResource::getUrl('index'),
            BannerResource::getUrl('create'),
            WebhookLogResource::getUrl('index'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_halaman_detail_booking_bisa_dibuka(): void
    {
        $this->actingAs($this->userWithRole('owner'));
        $booking = $this->holdBooking($this->makeUnitType(), '2026-10-12', '2026-10-13');

        $this->get(BookingResource::getUrl('view', ['record' => $booking]))
            ->assertOk()
            ->assertSee($booking->code);
    }

    public function test_pengguna_tanpa_role_tidak_bisa_masuk_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/kelola')->assertForbidden();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_panel(): void
    {
        $user = $this->userWithRole('admin');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/kelola')->assertForbidden();
    }

    public function test_front_office_bisa_melihat_harga_tetapi_tidak_bisa_mengubahnya(): void
    {
        $this->actingAs($this->userWithRole('front_office'));

        $this->get(UnitTypeResource::getUrl('view', ['record' => UnitType::first()]))->assertOk();
        $this->get(UnitTypeResource::getUrl('edit', ['record' => UnitType::first()]))->assertForbidden();
        $this->get(BookingResource::getUrl('create'))->assertOk();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(ManageSettings::getUrl())->assertForbidden();
    }

    public function test_sales_tidak_bisa_mengubah_harga_dan_pengguna(): void
    {
        $sales = $this->userWithRole('sales');
        $this->actingAs($sales);

        $this->get(BookingResource::getUrl('index'))->assertOk();
        $this->get(UnitTypeResource::getUrl('edit', ['record' => UnitType::first()]))->assertForbidden();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->assertFalse($sales->can('Cancel:Booking'));
    }

    public function test_admin_tidak_bisa_mengelola_pengguna_dan_role(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->get(UnitTypeResource::getUrl('edit', ['record' => UnitType::first()]))->assertOk();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_foto_unggahan_admin_disimpan_di_disk_publik_dan_tampil_di_galeri(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(CreateGalleryPhoto::class)
            ->fillForm([
                'photo' => [UploadedFile::fake()->image('glamping.jpg', 1200, 800)],
                'title' => 'Glamping di pagi hari',
                'category' => array_key_first(GalleryPhoto::CATEGORIES),
                'is_featured' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $media = GalleryPhoto::query()->where('title', 'Glamping di pagi hari')->firstOrFail()->getFirstMedia('photo');
        $this->assertSame('public', $media->disk);
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot('md'));

        // Galeri tidak lagi di beranda (tahap ux-redesign): foto tampil di halaman Galeri
        $this->get(route('galeri'))->assertSee($media->getUrl('md'), false);
    }
}
