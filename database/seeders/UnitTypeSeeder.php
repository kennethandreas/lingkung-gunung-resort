<?php

namespace Database\Seeders;

use App\Models\UnitType;
use Illuminate\Database\Seeder;

/**
 * Akomodasi dan harga dari brosur "Pilihan Akomodasi" 2026 (hal. 24),
 * nama pemasaran dari dokumen teknis 4.1 dan halaman unit di brosur.
 */
class UnitTypeSeeder extends Seeder
{
    public const BREAKFAST = 'Breakfast: Nasi Goreng / Nasi Uduk, Telur Ceplok / Tempe Mendoan, Air Mineral, Sambal, Kerupuk';

    public const DELUXE_INCLUDES = ['Room', 'Kamar 22 m²', 'Wifi', 'LED TV 32 inci', 'Water heater', 'Lemari pakaian', 'Teko listrik', 'Breakfast 2 pax', 'Pool', self::BREAKFAST];

    public const JUNIOR_SUITE_INCLUDES = ['Room', 'Kamar 26 m²', 'Wifi', 'Smart TV 43 inci', 'Water heater', 'Lemari pakaian', 'Teko listrik', 'Breakfast 2 pax', 'Pool', self::BREAKFAST];

    public function run(): void
    {
        $types = [
            [
                'slug' => 'bale-gunung',
                'name' => 'Bale Gunung',
                'marketing_name' => 'Pondokan Bale Gunung',
                'tagline' => 'Ruang Hangat untuk Keluarga',
                'description' => 'Pondokan berdesain tradisional modern dengan suasana sejuk dan asri, cocok untuk keluarga yang ingin beristirahat, berkumpul, dan lebih dekat dengan alam pegunungan.',
                'bed_configuration' => '1 Queen, 1 Single',
                'capacity_pax' => 3,
                'breakfast_pax' => 2,
                'total_units' => 5,
                'pricing_unit' => 'room',
                'includes' => ['Room', 'Wifi', 'TV', 'Breakfast 2 pax', 'Pool', self::BREAKFAST],
                'prices' => [525000, 640000],
                'unit_prefix' => 'BG',
            ],
            [
                'slug' => 'glamourcamp',
                'name' => 'Glamourcamp',
                'marketing_name' => 'Glamping Imah Lembah',
                'tagline' => 'Estetika di Ketinggian',
                'description' => 'Glamping A-frame dengan pemandangan lembah dan pegunungan, udara sejuk dan bersih, desain modern dan instagramable. Cocok untuk keluarga dan komunitas.',
                'bed_configuration' => '4 Single Bed',
                'capacity_pax' => 4,
                'breakfast_pax' => 2,
                'total_units' => 10,
                'pricing_unit' => 'room',
                'includes' => ['Room', 'Wifi', 'TV', 'Breakfast 2 pax', 'Pool', self::BREAKFAST],
                'prices' => [670000, 815000],
                'unit_prefix' => 'GL',
            ],
            [
                'slug' => 'pangrango-deluxe',
                'name' => 'Pangrango Deluxe',
                'marketing_name' => 'Pangrango Deluxe',
                'tagline' => 'Kemewahan Paripurna',
                'description' => 'Kamar ber-AC dengan pemandangan langsung ke alam terbuka, tempat tidur empuk kelas premium, dan fasilitas modern yang lengkap.',
                'bed_configuration' => '1 King Bed',
                'capacity_pax' => 2,
                'breakfast_pax' => 2,
                'total_units' => 5,
                'pricing_unit' => 'room',
                // Luas, TV, water heater, lemari, kettle: elji.id/deluxe-room (lihat docs/SUMBER-DATA.md)
                'includes' => self::DELUXE_INCLUDES,
                'prices' => [670000, 815000],
                'unit_prefix' => 'PD',
            ],
            [
                'slug' => 'pangrango-junior-suite',
                'name' => 'Pangrango Junior Suite',
                'marketing_name' => 'Pangrango Junior Suite',
                'tagline' => 'Lebih luas, lebih nyaman, lebih berkesan',
                'description' => 'Suite luas ber-AC dengan konfigurasi tempat tidur yang leluasa dan pemandangan pegunungan dari balkon.',
                'bed_configuration' => '2 Queen Bed',
                'capacity_pax' => 4,
                'breakfast_pax' => 2,
                'total_units' => 3,
                'pricing_unit' => 'room',
                // Luas, Smart TV, water heater, lemari, kettle: elji.id/junior-suite-room
                'includes' => self::JUNIOR_SUITE_INCLUDES,
                'prices' => [930000, 1105000],
                'unit_prefix' => 'PJ',
            ],
            [
                'slug' => 'kemping-ceria',
                'name' => 'Camping Ceria',
                'marketing_name' => 'Kemping Ceria',
                'tagline' => 'Imersi Alam Tanpa Kompromi',
                'description' => 'Tenda nyaman di bawah rindang pinus dengan kasur single, selimut, dan bantal. Tidur nyaman, udara segar, pengalaman bermakna bersama keluarga dan komunitas.',
                'bed_configuration' => '4 Single Bed',
                'capacity_pax' => 4,
                'breakfast_pax' => 2,
                'total_units' => 35,
                'pricing_unit' => 'tent',
                'includes' => ['Tenda', 'Wifi', 'Breakfast 2 pax', 'Pool', 'Kasur Single 4 pax', 'Selimut', 'Bantal', 'Listrik', 'Service', self::BREAKFAST],
                'prices' => [580000, 640000],
                'unit_prefix' => 'CC',
            ],
            [
                'slug' => 'family-suite-camp',
                'name' => 'Family Suite Camp',
                'marketing_name' => 'Family Suite Camp (Two Room)',
                'tagline' => 'Lebih Dekat dengan Alam, Lebih Dekat dengan Diri',
                'description' => 'Tenda keluarga dua ruang yang luas dengan perlengkapan camping lengkap dan view pegunungan yang menakjubkan.',
                'bed_configuration' => '2 Single Bed',
                'capacity_pax' => 4,
                'breakfast_pax' => 2,
                'total_units' => 2,
                'pricing_unit' => 'tent',
                'includes' => ['Tenda Alltek', 'Single Bed 2 pax', 'Matras & Selimut', '2 Kursi + Meja', '2 Lentera', 'Lampu Hias', 'Kompor Portable & Gas', 'Nesting', 'Grill Pan', 'Terminal Listrik', self::BREAKFAST],
                'prices' => [8700000, 990000], // TODO: konfirmasi ke resort (WD Rp8.700.000 kemungkinan Rp870.000)
                'unit_prefix' => 'FS',
            ],
            [
                'slug' => 'camp-site',
                'name' => 'Camp Site (Area Camping)',
                'marketing_name' => 'Camp Site',
                'tagline' => 'Bawa tenda sendiri, nikmati alamnya',
                'description' => 'Area camping untuk tamu yang membawa perlengkapan sendiri, lengkap dengan parkir, wifi, listrik, dan akses kolam renang.',
                'bed_configuration' => null,
                'capacity_pax' => 1,
                'breakfast_pax' => 0,
                'total_units' => 100, // TODO: konfirmasi ke resort (kapasitas camp site per malam belum ada di brosur)
                'pricing_unit' => 'person',
                'includes' => ['Area Camping', 'Parkir', 'Wifi', 'Listrik', 'Pool'],
                'prices' => [58000, 64000],
                'unit_prefix' => null,
                'is_bookable_online' => false, // dibuka setelah kapasitas dikonfirmasi
            ],
            [
                'slug' => 'kavling-campervan',
                'name' => 'Campervan (Kavling)',
                'marketing_name' => 'Kavling Campervan',
                'tagline' => 'Parkir campervan dengan pemandangan pegunungan',
                'description' => 'Kavling untuk campervan lengkap dengan parkir, wifi, listrik, dan akses kolam renang.',
                'bed_configuration' => null,
                'capacity_pax' => 1,
                'breakfast_pax' => 0,
                'total_units' => 20, // TODO: konfirmasi ke resort (jumlah kavling & harga per orang atau per kavling)
                'pricing_unit' => 'person', // brosur: satuan "1 Orang"
                'includes' => ['Area Campervan', 'Parkir', 'Wifi', 'Listrik', 'Pool'],
                'prices' => [270000, 425000],
                'unit_prefix' => null,
                'is_bookable_online' => false, // dibuka setelah satuan harga dikonfirmasi
            ],
        ];

        foreach ($types as $order => $data) {
            [$weekday, $weekend] = $data['prices'];
            $prefix = $data['unit_prefix'];
            unset($data['prices'], $data['unit_prefix']);

            $type = UnitType::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['sort_order' => $order + 1, 'is_active' => true, 'is_bookable_online' => $data['is_bookable_online'] ?? true],
            );

            foreach (['weekday' => $weekday, 'weekend' => $weekend] as $dayType => $price) {
                $type->rates()->updateOrCreate(
                    ['day_type' => $dayType, 'valid_from' => '2026-01-01'],
                    ['price' => $price, 'valid_to' => null],
                );
            }

            if ($prefix) {
                foreach (range(1, $type->total_units) as $n) {
                    $type->units()->firstOrCreate(['code' => sprintf('%s-%02d', $prefix, $n)]);
                }
            }
        }
    }
}
