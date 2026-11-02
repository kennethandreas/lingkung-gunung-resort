<?php

namespace App\Http\Controllers;

use App\Enums\DayType;
use App\Models\UnitType;
use App\Support\BrandColors;
use Illuminate\View\View;

/**
 * Halaman /style-guide: semua design token & komponen UI dalam satu halaman
 * (hanya aktif di luar produksi, lihat routes/web.php).
 */
class StyleGuideController extends Controller
{
    /** Placeholder sementara dari brosur, sampai foto asli diunggah lewat admin (F2). */
    private const PLACEHOLDERS = [
        'bale-gunung' => 'images/placeholder/bale-gunung',
        'glamourcamp' => 'images/placeholder/glamping-luar',
        'pangrango-deluxe' => 'images/placeholder/pangrango',
        'kemping-ceria' => 'images/placeholder/kemping-ceria',
        'family-suite-camp' => 'images/placeholder/family-suite',
    ];

    public function __invoke(): View
    {
        $unitTypes = UnitType::query()->active()->ordered()->with('rates')->get()->map(fn (UnitType $type) => [
            'name' => $type->marketing_name,
            'tagline' => $type->tagline,
            'image' => self::PLACEHOLDERS[$type->slug] ?? null,
            'capacity' => $type->isPricedPerPerson() ? null : $type->capacity_pax,
            'price' => $type->rates->where('day_type', DayType::Weekday)->sortByDesc('valid_from')->first()?->price,
            'unit' => match ($type->pricing_unit->value) {
                'person' => 'orang/malam',
                'tent' => 'tenda/malam',
                default => 'malam',
            },
            'features' => collect($type->includes ?? [])->reject(fn ($i) => str_starts_with($i, 'Breakfast:'))->take(3)->values()->all(),
        ]);

        return view('style-guide', [
            'unitTypes' => $unitTypes,
            'palette' => $this->palette(),
            'contrasts' => $this->contrasts(),
        ]);
    }

    private function palette(): array
    {
        return [
            'hutan' => ['label' => 'Hutan · warna utama', 'note' => 'Header, tombol utama, judul, latar gelap. Diambil dari hijau tua brosur.', 'shades' => BrandColors::HUTAN],
            'emas' => ['label' => 'Emas · aksen', 'note' => 'Tombol "Cek Ketersediaan", garis & ikon dekoratif. Untuk teks pakai 700 ke atas.', 'shades' => BrandColors::EMAS],
            'tanah' => ['label' => 'Tanah · pendukung', 'note' => 'Nuansa kayu & tanah untuk Play/Gather, ilustrasi, latar section hangat.', 'shades' => BrandColors::TANAH],
            'kabut' => ['label' => 'Kabut · netral hangat', 'note' => 'Latar kertas, teks isi, garis. Lebih hangat dari abu-abu biasa.', 'shades' => BrandColors::KABUT],
        ];
    }

    /** Rasio kontras WCAG untuk pasangan warna yang paling sering dipakai. */
    private function contrasts(): array
    {
        $pairs = [
            ['Teks isi di latar kertas', '#26231e', '#fbf9f4'],
            ['Teks sekunder di latar kertas', '#6e6452', '#fbf9f4'],
            ['Judul hijau di latar kertas', '#1a3526', '#fbf9f4'],
            ['Tombol utama (teks putih di hutan-800)', '#fbf9f4', '#1f402d'],
            ['Tombol aksen (teks hutan-950 di emas-400)', '#0d1e15', '#d4a43c'],
            ['Teks emas-700 di latar kertas', '#84521f', '#fbf9f4'],
            ['Teks kabut-200 di footer hutan-950', '#ebe3d3', '#0d1e15'],
            ['Teks emas-400 di latar kertas (hindari)', '#d4a43c', '#fbf9f4'],
        ];

        return array_map(function (array $pair) {
            [$label, $fg, $bg] = $pair;
            $ratio = $this->ratio($fg, $bg);

            return compact('label', 'fg', 'bg', 'ratio') + [
                'level' => $ratio >= 7 ? 'AAA' : ($ratio >= 4.5 ? 'AA' : ($ratio >= 3 ? 'AA (teks besar)' : 'Gagal')),
            ];
        }, $pairs);
    }

    private function ratio(string $a, string $b): float
    {
        [$l1, $l2] = [$this->luminance($a), $this->luminance($b)];

        return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
    }

    private function luminance(string $hex): float
    {
        $rgb = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
        $rgb = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);

        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }
}
