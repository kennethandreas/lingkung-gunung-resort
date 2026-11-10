<?php

namespace Database\Seeders;

use App\Models\MenuSet;
use Illuminate\Database\Seeder;

/**
 * Menu Makan A-E dari brosur "Pilihan Menu Group" (hal. 27-29).
 * Ditulis apa adanya sesuai brosur (termasuk ejaannya).
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            'A' => [
                'breakfast' => [
                    'A' => ['nasi uduk betawi', 'ayam bumbu kelapa', 'soun pengantin', 'sambal', 'kerupuk', 'tea', 'buah'],
                    'B' => ['nasi goreng cabe ijo', 'ayam kalasan', 'mie goreng jawa', 'sambal', 'kerupuk', 'coffe', 'buah'],
                    'C' => ['nasi kuning', 'tempe orek', 'telur bumbu urak', 'sambal', 'kerupuk', 'tea', 'buah'],
                    'D' => ['nasi goreng kampung', 'ayam bumbu sereh', 'tempe tepung', 'sambal', 'kerupuk', 'coffe', 'buah'],
                    'E' => ['nasi goreng kencur', 'ayam kecap', 'mie goreng', 'sambal', 'kerupuk', 'tea', 'buah'],
                ],
                'lunch' => [
                    'A' => ['nasi putih', 'sayur asem', 'ayam bumbu empal', 'tempe bacem', 'sambal', 'kerupuk', 'mineral water'],
                    'B' => ['nasi putih', 'nasi putih', 'ikan mas paniki', 'bakwan jagung', 'sambal', 'kerupuk', 'mineral water'],
                    'C' => ['nasi putih', 'gulai kapau', 'ayam rendang', 'bakwan sayur', 'sambal', 'kerupuk', 'mineral water'],
                    'D' => ['nasi putih', 'soto sokaraja', 'ayam goreng lengkuas', 'tahu walik', 'sambal', 'kerupuk', 'mineral water'],
                    'E' => ['nasi putih', 'acara ingcolocolo', 'asam asam buncis', 'tahu brontak', 'sambal', 'kerupuk', 'mineral water'],
                ],
                'dinner' => [
                    'A' => ['nasi putih', 'soup ayam jamur', 'ayam kluyuk', 'bakwan sayur', 'sambal', 'kerupuk', 'mineral water'],
                    'B' => ['nasi putih', 'ayam lada hitam', 'sapo tahu', 'bakwan jagung', 'sambal', 'kerupuk', 'mineral water'],
                    'C' => ['nasi putih', 'soup kimlo', 'dori asam manis', 'tahu isi', 'sambal', 'kerupuk', 'mineral water'],
                    'D' => ['nasi putih', 'soup bemabon', 'ayam kunpao', 'bakwan sayur', 'sambal', 'kerupuk', 'mineral water'],
                    'E' => ['nasi putih', 'ayam ingkung', 'capcay', 'tempe panir', 'sambal', 'kerupuk', 'mineral water'],
                ],
                'coffee_break' => [
                    'goreng_rebus' => ['Goreng / Rebus', ['pisang', 'singkong', 'ubi', 'jagung']],
                    'tradisional' => ['Tradisional', ['risoles', 'sosis solo', 'pastel', 'cantik manis']],
                ],
            ],
            'B' => [
                'breakfast' => [
                    'A' => ['nasi goreng oriental', 'ayam GR mentega', 'kwetiaw GR sayur', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi uduk kuning', 'telur balado', 'orek tempe', 'sambal', 'kerupuk', 'air mineral'],
                    'C' => ['nasi ulam', 'ayam GR kalasan', 'mie GR sayuran', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['Nasi Kebuli Ayam', 'Semur Telut', 'Soun GR jamur', 'sambal', 'kerupuk', 'air mineral', 'Tea'],
                    'E' => ['Lontong', 'Sayur Godog', 'Ayam Opor', 'sambal', 'kerupuk', 'air mineral', 'Coffe'],
                ],
                'lunch' => [
                    'A' => ['nasi putih', 'ayam GR kecap', 'capcay', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi putih', 'ikan mas pesmol', 'sayur lodeh', 'sambal', 'kerupuk', 'air mineral'],
                    'C' => ['nasi putih', 'ayam bumbu bali', 'tumis toge tahu', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['nasi putih', 'Soto Kudus', 'Ayam saus nanas', 'sambal', 'kerupuk', 'air mineral'],
                    'E' => ['nasi putih', 'Gulai Nangka', 'Ayam Pop', 'sambal', 'kerupuk', 'air mineral'],
                ],
                'dinner' => [
                    'A' => ['nasi putih', 'ayam bakar taliwang', 'soup baso sayur', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi putih', 'ayam lada hitam', 'soup kimlo', 'sambal', 'kerupuk', 'air mineral'],
                    'C' => ['nasi putih', 'nila goreng crispy', 'sayur acar kuning', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['nasi putih', 'Soup Mutiara', 'Ayam saus nanas', 'sambal', 'kerupuk', 'air mineral'],
                    'E' => ['nasi putih', 'Ayam Malbi', 'Tempe Krispi', 'sambal', 'kerupuk', 'air mineral'],
                ],
                'coffee_break' => [
                    'goreng_rebus' => ['Goreng / Rebus', ['pisang', 'singkong', 'ubi', 'jagung']],
                    'minuman' => ['Minuman', ['coffe & tea']],
                ],
            ],
            'C' => [
                'breakfast' => [
                    'A' => ['nasi goreng kemangi', 'telur gulung', 'kerupuk', 'mineral water'],
                    'B' => ['nasi goreng oriental', 'telur mata sapi', 'kerupuk', 'mineral water'],
                    'C' => ['nasi goreng bakso', 'ayam GR mentega', 'kerupuk', 'mineral water'],
                    'D' => ['nasi uduk kuning', 'telur rawis', 'kerupuk', 'mineral water'],
                    'E' => ['nasi ulam', 'ayam kremes', 'kerupuk', 'mineral water'],
                ],
                'lunch' => [
                    'A' => ['nasi putih', 'ayam GR rempah', 'capcay', 'kerupuk', 'mineral water'],
                    'B' => ['nasi putih', 'ayam kunpao', 'cah buncis jagung', 'kerupuk', 'mineral water'],
                    'C' => ['nasi putih', 'soto sokaraja', 'ayam bakar madu', 'kerupuk', 'mineral water'],
                    'D' => ['nasi putih', 'ayam GR koloke', 'tumis campur sawi', 'kerupuk', 'mineral water'],
                    'E' => ['nasi putih', 'ayam rendang', 'cha putren', 'kerupuk', 'mineral water'],
                ],
                'dinner' => [
                    'A' => ['nasi putih', 'ayam woku', 'soup jagung manis', 'kerupuk', 'mineral water'],
                    'B' => ['nasi putih', 'ayam katsu', 'capcay goreng', 'kerupuk', 'mineral water'],
                    'C' => ['nasi putih', 'ayam kuluyuk', 'cha kombinasi', 'kerupuk', 'mineral water'],
                    'D' => ['nasi putih', 'ayam taliwang', 'cha buncis jagung', 'kerupuk', 'mineral water'],
                    'E' => ['nasi putih', 'soto bandung', 'ayam GR rempah', 'kerupuk', 'mineral water'],
                ],
                'coffee_break' => [
                    'goreng_rebus' => ['Goreng / Rebus', ['pisang', 'singkong', 'ubi', 'jagung']],
                ],
            ],
            'D' => [
                'breakfast' => [
                    'A' => ['nasi goreng oriental', 'telur mata sapi', 'mie GR jawa', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi uduk betawi', 'telur rawis', 'orek tempe', 'Sambal', 'kerupuk', 'air mineral'],
                    'C' => ['Nasi ulam', 'ayam GR sayur', 'kwetiaw goreng', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['Nasi Kebuli', 'Telur Balado', 'sambal', 'kerupuk', 'air mineral', 'air mineral'],
                    'E' => ['Nasi Lemak', 'Ayam GR kecap', 'sambal', 'kerupuk', 'air mineral', 'air mineral'],
                ],
                'lunch' => [
                    'A' => ['nasi putih', 'telur balado', 'soup ayam sayuran', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi putih', 'telur dadar padang', 'sapo tahu', 'sambal', 'kerupuk', 'air mineral'],
                    'C' => ['nasi putih', 'ayam serundeng', 'tumis toge tahu', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['Nasi Putih', 'Ikan Mas Kalio', 'Soup Sayur Bakso', 'sambal', 'kerupuk', 'air mineral'],
                    'E' => ['Nasi Putih', 'Ayam Kentuky', 'Sopo Tahu', 'sambal', 'kerupuk', 'air mineral'],
                ],
                'dinner' => [
                    'A' => ['nasi putih', 'ikan mas paniki', 'tumis buncis jagung', 'sambal', 'kerupuk', 'air mineral'],
                    'B' => ['nasi putih', 'telur bumbu rijak', 'soup makaroni sayur', 'sambal', 'kerupuk', 'air mineral'],
                    'C' => ['nasi putih', 'puyunghai', 'soup sosis jagung', 'sambal', 'kerupuk', 'air mineral'],
                    'D' => ['Nasi Putih', 'Ikan Acar Kuning', 'Tumis toge tahu', 'sambal', 'kerupuk', 'air mineral'],
                    'E' => ['Nasi Putih', 'Ayam Bumbu bali', 'Cha sawi tahu', 'sambal', 'kerupuk', 'air mineral'],
                ],
                'coffee_break' => [
                    'goreng_rebus' => ['Goreng / Rebus', ['pisang', 'singkong', 'ubi', 'jagung']],
                    'minuman' => ['Minuman', ['coffee & tea']],
                ],
            ],
            'E' => [
                'breakfast' => [
                    'A' => ['nasi goreng oriental', 'telur mata sapi', 'kerupuk', 'air mineral'],
                    'B' => ['nasi uduk', 'telur dadar gulung', 'kerupuk', 'air mineral'],
                    'C' => ['nasi ulam', 'telur balado', 'kerupuk', 'air mineral'],
                    'D' => ['Nasi Kebuli', 'Telur Balado', 'kerupuk', 'air mineral'],
                    'E' => ['Nasi Lemak', 'Ayam GR kecap', 'kerupuk', 'air mineral'],
                ],
                'lunch' => [
                    'A' => ['nasi putih', 'puyunghai', 'buncis secuan', 'air mineral'],
                    'B' => ['nasi putih', 'telur balado', 'soup sayuran', 'air mineral'],
                    'C' => ['nasi putih', 'soto ayam bogor', 'bakwan jagung', 'air mineral'],
                    'D' => ['Nasi Putih', 'Ikan Mas Kalio', 'Soup Sayur Bakso', 'kerupuk', 'air mineral'],
                    'E' => ['Nasi Putih', 'Ayam Kentuky', 'Sopo Tahu', 'kerupuk', 'air mineral'],
                ],
                'dinner' => [
                    'A' => ['nasi putih', 'telur bumbu acar', 'tumis toge tahu', 'air mineral'],
                    'B' => ['nasi putih', 'telur dadar padang', 'soup jagung sosis', 'air mineral'],
                    'C' => ['nasi putih', 'telur balado', 'capcay', 'air mineral'],
                    'D' => ['Nasi Putih', 'Ikan Acar Kuning', 'Tumis toge tahu', 'kerupuk', 'air mineral'],
                    'E' => ['Nasi Putih', 'Ayam Bumbu Bali', 'Cha sawi Tahu', 'kerupuk', 'air mineral'],
                ],
                // TODO: konfirmasi ke resort - brosur tidak memuat pilihan coffee break untuk Menu E,
                // padahal Fullboard LDKS (B) yang memakai Menu E termasuk coffee break 1x.
                'coffee_break' => [],
            ],
        ];

        foreach ($sets as $code => $meals) {
            $set = MenuSet::query()->updateOrCreate(['code' => $code], ['name' => "Menu Makan {$code}", 'coffee_break_choices' => 2]);
            $order = 0;

            foreach (['breakfast', 'lunch', 'dinner'] as $meal) {
                foreach ($meals[$meal] as $option => $items) {
                    $set->options()->updateOrCreate(
                        ['meal' => $meal, 'option_code' => $option],
                        ['label' => "Opsi {$option}", 'items' => $items, 'sort_order' => ++$order],
                    );
                }
            }

            foreach ($meals['coffee_break'] as $option => [$label, $items]) {
                $set->options()->updateOrCreate(
                    ['meal' => 'coffee_break', 'option_code' => $option],
                    ['label' => $label, 'items' => $items, 'sort_order' => ++$order],
                );
            }
        }
    }
}
