<?php

namespace Database\Seeders;

use App\Models\GalleryPhoto;
use App\Models\Page;
use App\Models\Program;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\HasMedia;

/**
 * Foto ASLI resort dari situs resmi elji.id (public/images/resort, sumber tiap file: SUMBER.json).
 * - Hanya mengganti foto sementara (custom property "placeholder") atau mengisi yang kosong;
 *   foto yang diunggah admin tidak pernah ditimpa.
 * - Aman diulang (foto resmi yang sudah terpasang dikenali lewat custom property "resmi").
 * Bale Gunung & Family Suite Camp belum punya foto asli: tetap memakai gambar brosur (lihat docs/SUMBER-DATA.md).
 */
class OfficialMediaSeeder extends Seeder
{
    private const DIR = 'images/resort';

    public function run(): void
    {
        $units = [
            'glamourcamp' => [
                ['glamping-depan', 'Glamping A-frame Imah Lembah dengan jendela kaca'],
                ['glamping-udara', 'Deretan glamping di lereng, dilihat dari udara'],
                ['glamping-taman', 'Area glamping dengan taman dan pemandangan pegunungan'],
                ['glamping-jalan', 'Jalan setapak di antara unit glamping'],
                ['glamping-lembah', 'Glamping menghadap lembah hutan'],
            ],
            'pangrango-deluxe' => [
                ['deluxe-kamar', 'Kamar Pangrango Deluxe dengan jendela kaca ke balkon'],
                ['deluxe-kamar-3', 'Tempat tidur Pangrango Deluxe'],
                ['deluxe-balkon', 'Balkon Pangrango Deluxe menghadap hutan'],
                ['deluxe-kamar-4', 'Pangrango Deluxe: TV, lemari, dan pintu balkon'],
                ['deluxe-kamar-5', 'Sudut kamar Pangrango Deluxe'],
                ['deluxe-balkon-2', 'Pemandangan lembah dari balkon Pangrango'],
            ],
            'pangrango-junior-suite' => [
                ['suite-kamar', 'Pangrango Junior Suite dengan dua tempat tidur'],
                ['suite-kamar-3', 'Junior Suite: tempat tidur dan pintu balkon'],
                ['suite-balkon', 'Balkon Junior Suite dengan kursi santai'],
                ['suite-kamar-2', 'Junior Suite: meja, TV, dan jendela ke hutan'],
                ['suite-balkon-2', 'Pemandangan hutan dari balkon Junior Suite'],
            ],
            'kemping-ceria' => [
                ['kemping-barisan', 'Deretan tenda Kemping Ceria di bawah pinus'],
                ['kemping-lingkaran', 'Tenda Kemping Ceria mengelilingi titik api unggun'],
                ['kemping-api-unggun', 'Area Kemping Ceria dengan tempat api unggun'],
            ],
            'camp-site' => [
                ['campsite-pinus', 'Camp site di padang rumput berlatar pohon pinus'],
                ['campsite-tenda-mobil', 'Tamu berkemah dengan tenda sendiri di camp site'],
                ['campsite-mobil', 'Camp site luas untuk rombongan dan kendaraan'],
            ],
            'kavling-campervan' => [
                ['campervan-tenda', 'Tenda di samping mobil di area campervan'],
                ['campervan-keluarga', 'Keluarga berkemah di samping mobil'],
                ['campervan-atap', 'Mobil dengan tenda atap dan awning di area campervan'],
                ['campervan-malam', 'Suasana sore di area campervan'],
            ],
        ];

        foreach ($units as $slug => $photos) {
            if ($type = UnitType::query()->where('slug', $slug)->first()) {
                $this->replacePlaceholders($type, 'photos', $photos);
            }
        }

        if ($home = Page::query()->where('slug', 'beranda')->first()) {
            $this->replacePlaceholders($home, 'hero', [['glamping-udara', 'Deretan glamping Lingkung Gunung di lereng hijau, dilihat dari udara']]);
        }

        $programs = [
            'play-adventure-adrenaline-trekking' => ['trekking-sungai', 'Trekking menyusuri sungai di kaki Gunung Pangrango'],
            'play-adventure-adrenaline-fun-games' => ['outbound-lapangan', 'Permainan outbound rombongan di lapangan'],
            'play-adventure-adrenaline-team-building' => ['senam-rombongan', 'Rombongan berkegiatan di lapangan berlatar pinus'],
            'play-adventure-adrenaline-rafting' => ['rafting', 'Arung jeram'],
            'play-adventure-adrenaline-paintball' => ['paintball', 'Permainan paintball di hutan'],
            'play-adventure-adrenaline-offroad' => ['offroad', 'Kendaraan offroad di jalur tanah'],
            'play-eco-culture-experience-menangkap-ikan' => ['kolam-ikan', 'Kolam dan saung di area resort'],
            'play-eco-culture-experience-flyingfox' => ['flying-fox', 'Flying fox di antara pohon pinus'],
            'learn-school-outing' => ['outbound-lapangan', 'Kegiatan luar kelas di lapangan'],
            'learn-school-ldks' => ['senam-rombongan', 'Kegiatan rombongan siswa di lapangan'],
            'learn-school-pramuka' => ['pramuka-upacara', 'Upacara pramuka di lapangan Lingkung Gunung'],
            'gather-corporate-meeting' => ['aula', 'Aula berdinding kaca untuk meeting'],
            'gather-corporate-gathering' => ['gathering-panggung', 'Gathering di panggung terbuka'],
            'gather-corporate-team-building' => ['outbound-lapangan', 'Team building di lapangan'],
            'gather-family-camping-ceria' => ['kemping-barisan', 'Tenda Kemping Ceria di bawah pinus'],
            'gather-family-glamping' => ['glamping-depan', 'Glamping A-frame Imah Lembah'],
            'gather-family-swimming' => ['kolam-renang', 'Kolam renang Lingkung Gunung'],
            'gather-family-family-gathering' => ['makan-bersama', 'Keluarga bersantap bersama di area resort'],
            'gather-community-campervan' => ['campervan-keluarga', 'Berkemah di samping mobil'],
            'gather-community-gowes' => ['gowes', 'Komunitas gowes di gerbang Lingkung Gunung'],
            'gather-community-community-gathering' => ['rombongan-hkbp', 'Kebaktian padang pemuda HKBP Depok 1 di Lingkung Gunung (2024)'],
            'gather-team-building-games-flyingfox' => ['flying-fox-2', 'Flying fox untuk team building'],
        ];

        foreach ($programs as $slug => [$file, $alt]) {
            if ($program = Program::query()->where('slug', $slug)->first()) {
                $this->replacePlaceholders($program, 'cover', [[$file, $alt]]);
            }
        }

        $this->gallery();
    }

    /** Galeri: foto resmi ditambahkan sekali; galeri sementara dari brosur dinonaktifkan (tidak dihapus). */
    private function gallery(): void
    {
        $photos = [
            ['glamping-udara', 'Glamping Imah Lembah di lereng hijau, dilihat dari udara', 'akomodasi', true],
            ['deck-lembah', 'Deck pandang menghadap lembah dan pegunungan', 'alam', true],
            ['kafe-teras', 'Teras makan di antara pinus dengan pemandangan lembah', 'kuliner', true],
            ['kolam-renang', 'Kolam renang untuk tamu', 'fasilitas', true],
            ['kemping-lingkaran', 'Tenda Kemping Ceria mengelilingi tempat api unggun', 'akomodasi', true],
            ['deluxe-balkon', 'Balkon kamar Pangrango menghadap hutan', 'akomodasi', false],
            ['glamping-depan', 'Glamping A-frame dengan pintu kaca', 'akomodasi', false],
            ['suite-kamar', 'Pangrango Junior Suite', 'akomodasi', false],
            ['deluxe-kamar', 'Pangrango Deluxe', 'akomodasi', false],
            ['kafe-deck', 'Menikmati kopi di deck pandang', 'kuliner', false],
            ['kafe-deck-2', 'Deck pandang di tepi lembah', 'alam', false],
            ['deck-pandang', 'Papan nama Lingkung Gunung di tepi lembah', 'alam', false],
            ['glamping-lembah', 'Glamping menghadap hutan dan lembah', 'akomodasi', false],
            ['aula', 'Aula untuk meeting dan acara', 'fasilitas', false],
            ['trekking-sungai', 'Trekking menyusuri sungai', 'aktivitas', false],
            ['paintball', 'Paintball di hutan', 'aktivitas', false],
            ['offroad', 'Offroad di jalur tanah', 'aktivitas', false],
            ['rafting', 'Arung jeram', 'aktivitas', false],
            ['flying-fox', 'Flying fox di antara pinus', 'aktivitas', false],
            ['pramuka-upacara', 'Upacara pramuka di lapangan', 'aktivitas', false],
            ['senam-rombongan', 'Kegiatan rombongan di lapangan', 'aktivitas', false],
            ['gowes', 'Komunitas gowes', 'aktivitas', false],
            ['campsite-pinus', 'Camp site berlatar pinus', 'akomodasi', false],
            ['campervan-atap', 'Area campervan', 'akomodasi', false],
            ['makan-bersama', 'Bersantap bersama di area resort', 'kuliner', false],
            ['glamping-taman', 'Taman di area glamping', 'alam', false],
        ];

        if (GalleryPhoto::query()->whereHas('media', fn ($q) => $q->where('custom_properties->resmi', true))->exists()) {
            return;
        }

        // galeri lama yang hanya berisi gambar brosur sementara: disembunyikan, admin bisa mengaktifkan lagi
        GalleryPhoto::query()->whereHas('media', fn ($q) => $q->where('custom_properties->placeholder', true))->update(['is_active' => false]);

        $categories = array_keys(GalleryPhoto::CATEGORIES);
        foreach ($photos as $i => [$file, $title, $category, $featured]) {
            $photo = GalleryPhoto::query()->create([
                'title' => $title,
                'category' => in_array($category, $categories, true) ? $category : 'fasilitas',
                'is_featured' => $featured,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
            $this->attach($photo, $file, 'photo', $title);
        }
    }

    /** @param  list<array{0: string, 1: string}>  $photos */
    private function replacePlaceholders(Model&HasMedia $model, string $collection, array $photos): void
    {
        $existing = $model->getMedia($collection);

        if ($existing->contains(fn ($media) => $media->getCustomProperty('resmi'))) {
            return; // sudah pernah diisi foto resmi
        }
        if ($existing->contains(fn ($media) => ! $media->getCustomProperty('placeholder'))) {
            return; // ada foto unggahan admin: jangan diubah
        }

        $existing->each->delete();
        foreach ($photos as [$file, $alt]) {
            $this->attach($model, $file, $collection, $alt);
        }
    }

    private function attach(Model&HasMedia $model, string $file, string $collection, string $alt): void
    {
        $path = public_path(self::DIR."/{$file}-1600.webp");

        if (is_file($path)) {
            $model->addMedia($path)
                ->preservingOriginal()
                ->usingFileName("{$file}.webp")
                ->withCustomProperties(['alt' => $alt, 'resmi' => true, 'sumber' => 'elji.id'])
                ->toMediaCollection($collection);
        }
    }
}
