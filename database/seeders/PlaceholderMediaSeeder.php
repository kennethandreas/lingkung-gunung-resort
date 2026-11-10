<?php

namespace Database\Seeders;

use App\Models\GalleryPhoto;
use App\Models\Page;
use App\Models\Program;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

/**
 * Foto SEMENTARA dari brosur (public/images/placeholder) supaya website tidak kosong.
 * Hanya mengisi yang belum punya foto, jadi foto asli yang diunggah admin tidak tertimpa.
 * Ganti lewat admin panel begitu foto asli tersedia (lihat docs/DATA-TODO.md).
 */
class PlaceholderMediaSeeder extends Seeder
{
    private const DIR = 'images/placeholder';

    public function run(): void
    {
        $unitPhotos = [
            'bale-gunung' => ['bale-gunung'],
            'glamourcamp' => ['glamping-luar', 'glamping-dalam'],
            'pangrango-deluxe' => ['pangrango'],
            'pangrango-junior-suite' => ['pangrango'],
            'kemping-ceria' => ['kemping-ceria'],
            'family-suite-camp' => ['family-suite'],
        ];

        foreach ($unitPhotos as $slug => $files) {
            $type = UnitType::query()->where('slug', $slug)->first();
            if ($type && $type->getMedia('photos')->isEmpty()) {
                foreach ($files as $file) {
                    $this->attach($type, $file, 'photos', $type->marketing_name);
                }
            }
        }

        $gallery = [
            ['glamping-luar', 'Glamping Imah Lembah di antara kabut pegunungan', 'akomodasi', true],
            ['kemping-ceria', 'Tenda Kemping Ceria di bawah rindang pinus', 'akomodasi', true],
            ['swaka-teras', 'Teras Swaka Cafe & Resto menghadap lembah', 'kuliner', true],
            ['meeting-luar', 'Meeting room dikelilingi pohon cemara', 'fasilitas', true],
            ['glamping-dalam', 'Interior glamping A-frame dengan jendela ke gunung', 'akomodasi', false],
            ['bale-gunung', 'Pondokan Bale Gunung bergaya tradisional modern', 'akomodasi', false],
            ['family-suite', 'Family Suite Camp dengan view pegunungan', 'akomodasi', false],
            ['pangrango', 'Kamar Pangrango dengan balkon ke alam terbuka', 'akomodasi', false],
        ];

        if (GalleryPhoto::query()->doesntExist()) {
            foreach ($gallery as $i => [$file, $title, $category, $featured]) {
                $photo = GalleryPhoto::query()->create(['title' => $title, 'category' => $category, 'is_featured' => $featured, 'sort_order' => $i + 1]);
                $this->attach($photo, $file, 'photo', $title);
            }
        }

        $home = Page::query()->where('slug', 'beranda')->first();
        if ($home && ! $home->hasMedia('hero')) {
            $this->attach($home, 'glamping-luar', 'hero', 'Glamping Imah Lembah di antara kabut dan pegunungan');
        }

        $programCovers = [
            'gather-corporate-meeting' => 'meeting-luar',
            'gather-family-glamping' => 'glamping-luar',
            'gather-family-camping-ceria' => 'kemping-ceria',
            'gather-community-campervan' => 'family-suite',
        ];

        foreach ($programCovers as $slug => $file) {
            $program = Program::query()->where('slug', $slug)->first();
            if ($program && ! $program->hasMedia('cover')) {
                $this->attach($program, $file, 'cover', $program->name);
            }
        }
    }

    private function attach($model, string $file, string $collection, string $alt): void
    {
        $path = public_path(self::DIR."/{$file}-1600.webp");

        if (is_file($path)) {
            $model->addMedia($path)
                ->preservingOriginal()
                ->withCustomProperties(['alt' => $alt, 'placeholder' => true])
                ->toMediaCollection($collection);
        }
    }
}
