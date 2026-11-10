<?php

namespace Database\Seeders;

use App\Models\GroupPackage;
use App\Models\MenuSet;
use Illuminate\Database\Seeder;

/** Paket group fullboard dari brosur "Pilihan Paket Group" (hal. 22). Harga per pax. */
class GroupPackageSeeder extends Seeder
{
    public function run(): void
    {
        $menus = MenuSet::query()->pluck('id', 'code');

        $packages = [
            [
                'slug' => 'fullboard-ldks',
                'name' => 'Fullboard LDKS',
                'accommodation' => 'Tenda Dome',
                'description' => 'Paket fullboard untuk LDKS dan kegiatan sekolah dengan akomodasi tenda dome.',
                'variants' => [
                    ['A', 30, 'D', 1, false, 260000, 310000, ['Tenda Dome', 'Wifi', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket D)', 'Coffeebreak 1x']],
                    ['B', 30, 'E', 1, false, 240000, 290000, ['Tenda Dome', 'Wifi', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket E)', 'Coffeebreak 1x']],
                    ['C', 100, 'E', 0, true, 225000, 275000, ['Weekday', 'Tenda Dome', 'Wifi', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket E)']],
                ],
            ],
            [
                'slug' => 'fullboard-camp',
                'name' => 'Fullboard Camp',
                'accommodation' => 'Tenda + Kasur',
                'description' => 'Paket fullboard camping dengan tenda dome dan 4 single bed.',
                'variants' => [
                    ['A', 50, 'A', 2, false, 375000, 425000, ['Tenda Dome', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket A)', 'Coffeebreak 2x']],
                    ['B', 50, 'B', 2, false, 325000, 375000, ['Tenda Dome', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket B)', 'Coffeebreak 2x']],
                    ['C', 100, 'C', 0, false, 285000, 335000, ['Tenda Dome', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket C)']],
                ],
            ],
            [
                'slug' => 'fullboard-room-glamping',
                'name' => 'Fullboard Room Glamping',
                'accommodation' => 'Glamping',
                'description' => 'Paket fullboard dengan akomodasi glamping dan 4 single bed.',
                'variants' => [
                    ['A', 30, 'A', 2, false, 395000, 445000, ['Glamping', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket A)', 'Coffeebreak 2x']],
                    ['B', 30, 'B', 2, false, 355000, 405000, ['Glamping', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket B)', 'Coffeebreak 2x']],
                    ['C', 30, 'C', 0, false, 315000, 365000, ['Glamping', 'Wifi', '4 Single Bed', 'Pool', 'Aula', 'Lapangan', 'Meals 3x (paket C)']],
                ],
            ],
        ];

        foreach ($packages as $order => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $package = GroupPackage::query()->updateOrCreate(['slug' => $data['slug']], $data + ['sort_order' => $order + 1]);

            foreach ($variants as [$code, $minPax, $menu, $coffeeBreaks, $weekdayOnly, $wd, $we, $includes]) {
                $package->variants()->updateOrCreate(['code' => $code], [
                    'name' => "{$package->name} ({$code})",
                    'min_pax' => $minPax,
                    'menu_set_id' => $menus[$menu] ?? null,
                    'coffee_breaks' => $coffeeBreaks,
                    'weekday_only' => $weekdayOnly,
                    'price_weekday' => $wd,
                    'price_weekend' => $we,
                    'includes' => $includes,
                ]);
            }
        }
    }
}
