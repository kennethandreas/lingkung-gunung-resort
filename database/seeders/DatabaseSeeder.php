<?php

namespace Database\Seeders;

use App\Models\UnitType;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Data awal dari brosur 2026. Aman dijalankan berulang (updateOrCreate).
 * Akun admin TIDAK dibuat di sini (tanpa kredensial di kode):
 * pakai `php artisan make:filament-user` lalu `php artisan shield:super-admin`.
 */
class DatabaseSeeder extends Seeder
{
    // Data brosur tidak perlu tercatat sebagai "perubahan" di log aktivitas.
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UnitTypeSeeder::class,
            MenuSeeder::class,
            GroupPackageSeeder::class,
            ActivitySeeder::class,
            AdditionalItemSeeder::class,
            ContentSeeder::class,
            ArticleSeeder::class,
        ]);

        // Foto sementara dari brosur (dilewati di test agar cepat)
        if (! app()->runningUnitTests()) {
            $this->call(PlaceholderMediaSeeder::class);
            // Foto asli dari elji.id menggantikan gambar brosur sementara (yang belum ada foto aslinya tetap brosur)
            $this->call(OfficialMediaSeeder::class);
        }

        // Siapkan stok harian 1 tahun ke depan
        $today = CarbonImmutable::today();
        app(BookingService::class)->ensureInventory(UnitType::query()->pluck('id')->all(), $today, $today->addDays(365));
    }
}
