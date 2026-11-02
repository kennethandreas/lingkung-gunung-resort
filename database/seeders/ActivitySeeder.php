<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

/** Brosur "Pilihan Aktifitas" (hal. 25) dan "Paket Kegiatan Sanggar Alam" (hal. 23). */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['trekking', 'Trekking', 'adventure', 120000, 'pax', 20, 'Guide ke Air Terjun Eiji atau Pangrango, Air Mineral', null],
            ['fun-games', 'Fun Games', 'adventure', 175000, 'pax', 15, '1 day outbound + fasilitator', null],
            ['team-building', 'Team Building', 'adventure', 235000, 'pax', 15, '1 day outbound + fasilitator', null],
            ['rafting', 'Rafting', 'adventure', 290000, 'pax', 8, 'Kendaraan antar-jemput ke sungai, perahu, nahkoda, tim rescue, snack', null],
            ['paintball', 'Paintball', 'adventure', 175000, 'pax', 20, 'Perlengkapan + fasilitator', null],
            ['kambing-guling', 'Kambing Guling', 'konsumsi', 2900000, 'ekor', 40, 'Kambing guling utuh + penyajian', null],
            ['offroad', 'Offroad', 'adventure', 410000, 'pax', 10, 'Kendaraan Offroad, Driver, Snack, Minum', null],
            // TODO: konfirmasi ke resort - jam Half-day tertulis 10.00-14.00 tetapi jadwalnya 08.00-13.00
            ['sanggar-alam-half-day', 'Sanggar Alam Half-Day', 'sanggar_alam', 150000, 'pax', 1, 'Free Kolam Renang, Makan Siang Botram, Edukasi Budaya, Prakarya Wayang Singkong', '10.00 - 14.00'],
            // TODO: konfirmasi ke resort - jam Full-day tertulis 09.00-15.00 tetapi jadwalnya 08.00-15.00
            ['sanggar-alam-full-day', 'Sanggar Alam Full-Day', 'sanggar_alam', 180000, 'pax', 1, 'Free Kolam Renang, Makan Siang Botram, Bertani dan Berkebun, Edukasi Budaya, Prakarya Wayang Singkong, Souvenir', '09.00 - 15.00'],
        ];

        foreach ($activities as $order => [$slug, $name, $category, $price, $unit, $minPax, $includes, $schedule]) {
            Activity::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'price_unit' => $unit,
                'min_pax' => $minPax,
                'includes' => $includes,
                'schedule' => $schedule,
                'sort_order' => $order + 1,
            ]);
        }
    }
}
