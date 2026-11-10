<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pembaruan data dari sumber resmi (brosur 2026 + elji.id, dicek 25 Okt 2026; lihat docs/SUMBER-DATA.md).
 * Aman untuk data yang sudah diubah admin: baris hanya diubah bila isinya MASIH sama persis dengan nilai seeder lama.
 */
return new class extends Migration
{
    private const BREAKFAST = 'Breakfast: Nasi Goreng / Nasi Uduk, Telur Ceplok / Tempe Mendoan, Air Mineral, Sambal, Kerupuk';

    public function up(): void
    {
        $old = ['Room', 'Wifi', 'TV', 'Breakfast 2 pax', 'Pool', self::BREAKFAST];

        $this->replaceIncludes('pangrango-deluxe', $old, ['Room', 'Kamar 22 m²', 'Wifi', 'LED TV 32 inci', 'Water heater', 'Lemari pakaian', 'Teko listrik', 'Breakfast 2 pax', 'Pool', self::BREAKFAST]);
        $this->replaceIncludes('pangrango-junior-suite', $old, ['Room', 'Kamar 26 m²', 'Wifi', 'Smart TV 43 inci', 'Water heater', 'Lemari pakaian', 'Teko listrik', 'Breakfast 2 pax', 'Pool', self::BREAKFAST]);

        // Brosur hal. 3 & 23: lokasi di kaki Gunung Gede Pangrango dengan panorama Puncak Salak (bukan "kaki Gunung Salak").
        // "Satu jam dari Jakarta" tidak ada di sumber resmi mana pun, jadi dihapus.
        DB::table('pages')->where('slug', 'beranda')->where('title', 'Bangun di antara kabut, tidur di kaki Gunung Salak.')
            ->update(['title' => 'Bangun di antara kabut, menatap Puncak Salak.', 'updated_at' => now()]);
        DB::table('pages')->where('slug', 'beranda')->where('meta_description', 'Glamping, pondokan, dan camping di 4 hektar alam terbuka. Satu jam dari Jakarta, tanpa drama buka-tutup Puncak.')
            ->update(['meta_description' => 'Glamping, pondokan, dan camping di 4 hektar alam terbuka. Lewat Tol Jagorawi–Bocimi, tanpa drama buka-tutup Puncak.', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Pembaruan konten tidak dibatalkan otomatis (ubah lewat admin bila perlu).
    }

    private function replaceIncludes(string $slug, array $old, array $new): void
    {
        $row = DB::table('unit_types')->where('slug', $slug)->first(['id', 'includes']);

        if ($row && json_decode((string) $row->includes, true) === $old) {
            DB::table('unit_types')->where('id', $row->id)->update(['includes' => json_encode($new, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }
};
