<?php

namespace Database\Seeders;

use App\Models\AdditionalItem;
use Illuminate\Database\Seeder;

/** Brosur "Additional" (hal. 26): 23 item tambahan, harga apa adanya. */
class AdditionalItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // [nama, kategori, satuan, label satuan brosur, kapasitas, layout, include, harga, min qty]
            ['Aula Mandalawangi (1-2)', 'aula', 'sett', '1 Sett (Max 20 Orang, U Shape)', 20, 'U Shape', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol, Meja', 1750000, 1],
            ['Aula Mandalawangi (1-2)', 'aula', 'sett', '1 Sett (Max 20 Orang, Lesehan)', 20, 'Lesehan', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol', 1160000, 1],
            ['Aula Mandalawangi (3)', 'aula', 'sett', '1 Sett (Max 60 Orang, U Shape)', 60, 'U Shape', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol, Meja', 135000, 1], // TODO: konfirmasi ke resort (Rp135.000 janggal untuk aula 60 orang)
            ['Aula Mandalawangi (3)', 'aula', 'sett', '1 Sett (Max 100 Orang, Lesehan)', 100, 'Lesehan', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol', 2199999, 1], // TODO: konfirmasi ke resort (Rp2.199.999, kemungkinan Rp2.200.000)
            ['Aula Mandalawangi (3)', 'aula', 'sett', '1 Sett (Max 100 Orang, Classroom)', 100, 'Classroom', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol, Meja', 2560000, 1],
            ['Aula Mandalawangi (Full)', 'aula', 'sett', '1 Sett (Max 200 Orang, Class Room)', 200, 'Classroom', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral Botol, Karpet', 4990000, 1],
            ['Aula Mandalawangi (Full)', 'aula', 'sett', '1 Sett (200 Orang, Lesehan)', 200, 'Lesehan', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral, Karpet', 4410000, 1],
            ['Aula Suryakencana (Full)', 'aula', 'sett', '1 Sett (150 Orang, Lesehan)', 150, 'Lesehan', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral, Karpet', 2100000, 1],
            ['Aula Suryakencana (Full)', 'aula', 'sett', '1 Sett (150 Orang, Class Room)', 150, 'Classroom', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral, Karpet, Kursi', 2900000, 1],
            ['Pendopo Sanggar Alam', 'aula', 'sett', '1 Sett (100 Orang, Lesehan)', 100, 'Lesehan', 'Meetingroom, TV/Infocus, Sound, Wifi, Air Mineral, Karpet', 2100000, 1],
            ['Blocking Lapangan Suryakencana', 'lapangan', 'area', '1 area', null, null, 'Sewa area untuk event', 4640000, 1],
            ['Blocking Lapangan Mandalawangi', 'lapangan', 'area', '1 area', null, null, 'Sewa area untuk event', 4225000, 1],
            ['Sleeping Bag', 'perlengkapan', 'pax', '1 pax', null, null, 'Dihitung & diserahkan ke Ketua Panitia, dikembalikan Ketua', 17500000, 1], // TODO: konfirmasi ke resort (Rp17.500.000/pax janggal)
            ['One Day (8 Jam Meeting)', 'meeting', 'pax', '1 pax', null, null, 'Aula, Lapangan, Sound System, TV/Infocus, Coffee Break 2x', 210000, 1],
            ['Half Day (4 Jam Meeting)', 'meeting', 'pax', '1 pax', null, null, 'Aula, Lapangan, Sound System, TV/Infocus, Coffee Break 1x', 1630000, 1], // TODO: konfirmasi ke resort (Half Day Rp1.630.000/pax lebih mahal dari One Day Rp210.000)
            ['Sate Kambing (1 Porsi, 10 tusuk)', 'konsumsi', 'porsi', '5 porsi (Minimal)', null, null, 'Bumbu Sate dan Kecap', 410000, 5], // TODO: konfirmasi ke resort (Rp410.000 per porsi atau per 5 porsi?)
            ['Sop Kambing', 'konsumsi', 'paket', '5 porsi', null, null, '5 Porsi Sop', 290000, 1],
            ['Kursi Standar', 'perlengkapan', 'kursi', '1 Kursi', null, null, 'Kursi Standar', 11600000, 1], // TODO: konfirmasi ke resort (Rp11.600.000 per kursi janggal)
            ['Kursi Meeting', 'perlengkapan', 'kursi', '1 Kursi', null, null, 'Kursi Standar', 23500, 1],
            ['Meja Standar', 'perlengkapan', 'meja', '1 Kursi', null, null, 'Kursi Standar', 29000, 1], // TODO: konfirmasi ke resort (satuan & keterangan tertulis "Kursi")
            ['Meja Meeting', 'perlengkapan', 'meja', '1 Kursi', null, null, 'Kursi Standar', 35000, 1], // TODO: konfirmasi ke resort (satuan & keterangan tertulis "Kursi")
            ['Organ Tunggal', 'hiburan', 'paket', '1 Paket', null, null, '1 Singer, Sound, Organ', 3480000, 1],
            ['Listrik >200 Watt', 'utilitas', 'paket', '1 Paket', null, null, '250 Watt-450 Watt (2 Ampere)', 58000, 1],
        ];

        foreach ($items as $order => [$name, $category, $unit, $label, $capacity, $layout, $includes, $price, $minQty]) {
            AdditionalItem::query()->updateOrCreate(['sort_order' => $order + 1], [
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'unit_label' => $label,
                'capacity' => $capacity,
                'layout' => $layout,
                'includes' => $includes,
                'price' => $price,
                'min_qty' => $minQty,
            ]);
        }
    }
}
