<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

/**
 * Artikel awal, disusun ulang (bukan disalin) dari artikel resmi di elji.id; sumber dicantumkan di tiap artikel.
 * Hanya membuat artikel yang belum ada (firstOrCreate per slug), jadi suntingan admin tidak tertimpa.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'slug' => 'kamar-pangrango-deluxe-dan-junior-suite',
                'title' => 'Kamar Pangrango: Deluxe dan Junior Suite dengan balkon menghadap hutan',
                'category' => 'akomodasi',
                'published_at' => '2025-03-19 09:00:00',
                'source_url' => 'https://elji.id/junior-suite-room/',
                'cover' => 'suite-balkon',
                'excerpt' => 'Dua tipe kamar ber-AC dengan jendela kaca dan balkon yang langsung menghadap hutan dan lembah. Ini perbedaan luas, fasilitas, dan kapasitasnya.',
                'body' => <<<'HTML'
                    <p>Kamar Pangrango adalah pilihan bagi tamu yang ingin tetap dekat dengan alam tanpa meninggalkan kenyamanan kamar. Keduanya punya pintu kaca lebar menuju balkon dengan pemandangan hutan dan lembah.</p>
                    <h2>Pangrango Deluxe</h2>
                    <ul>
                        <li>Luas kamar 22 m²</li>
                        <li>1 king bed, kapasitas 2 orang (brosur 2026)</li>
                        <li>Wifi, kamar mandi dengan water heater, LED TV 32 inci, lemari pakaian, dan teko listrik</li>
                    </ul>
                    <h2>Pangrango Junior Suite</h2>
                    <ul>
                        <li>Luas kamar 26 m²</li>
                        <li>2 queen bed, kapasitas 4 orang</li>
                        <li>Wifi, kamar mandi dengan water heater, Smart TV 43 inci, lemari pakaian, dan teko listrik</li>
                    </ul>
                    <p>Kedua tipe sudah termasuk sarapan untuk 2 orang dan akses kolam renang. Harga terbaru dan ketersediaan bisa dicek langsung lewat tombol <strong>Cek Ketersediaan</strong>.</p>
                    HTML,
            ],
            [
                'slug' => 'tips-berkemah-dengan-camper-van',
                'title' => 'Tips berkemah dengan camper van di alam terbuka',
                'category' => 'tips',
                'published_at' => '2024-09-19 09:00:00',
                'source_url' => 'https://elji.id/tips-melakukan-camper-van-di-alam-terbuka/',
                'cover' => 'campervan-atap',
                'excerpt' => 'Berkemah di samping kendaraan makin digemari keluarga. Empat hal yang perlu disiapkan supaya pengalaman pertama berjalan lancar.',
                'body' => <<<'HTML'
                    <p>Camper van, atau berkemah langsung di samping kendaraan, menjadi cara berlibur yang makin digemari keluarga. Supaya pengalaman pertama berjalan lancar, siapkan empat hal ini.</p>
                    <h2>1. Pilih lokasi dengan fasilitas dasar</h2>
                    <p>Pastikan lokasi punya toilet, sumber air, dan tempat sampah, serta aman dari cuaca ekstrem. Hindari mendirikan tenda terlalu dekat dengan aliran air.</p>
                    <h2>2. Rencanakan kebutuhan listrik</h2>
                    <p>Periksa kondisi aki kendaraan, bawa power bank berkapasitas besar, dan siapkan lampu portabel untuk malam hari.</p>
                    <h2>3. Siapkan peralatan masak dan bekal</h2>
                    <p>Bawa kompor portabel, peralatan masak yang ringkas, serta makanan yang mudah dimasak. Jangan lupa kantong sampah, dan jangan tinggalkan sisa makanan di alam.</p>
                    <h2>4. Utamakan kenyamanan tidur</h2>
                    <p>Selain tenda, kasur tiup atau sleeping pad membuat tidur jauh lebih nyaman, apalagi udara pegunungan bisa dingin di malam hari.</p>
                    <p>Di Lingkung Gunung tersedia area campervan dengan parkir, wifi, listrik, dan akses kolam renang. Untuk pemesanan area campervan, hubungi tim kami lewat WhatsApp.</p>
                    HTML,
            ],
            [
                'slug' => 'tempat-gathering-outbound-dan-camping-di-bogor',
                'title' => 'Tempat gathering, outbound, dan camping di kaki Gunung Gede Pangrango',
                'category' => 'kabar',
                'published_at' => '2024-10-30 09:00:00',
                'source_url' => 'https://elji.id/tempat-camping-outbound-di-lingkung-gunung-bogor/',
                'cover' => 'outbound-lapangan',
                'excerpt' => 'Aula, kolam renang, lapangan luas, dan kafe dalam satu kawasan di Pancawati, Caringin. Cocok untuk keluarga, sekolah, komunitas, hingga perusahaan.',
                'body' => <<<'HTML'
                    <p>Lingkung Gunung berada di Pancawati, Caringin, Bogor, tepat di kaki Gunung Gede Pangrango. Di satu kawasan tersedia pilihan camping, campervan, glamping, dan pondokan dengan suasana pegunungan yang sejuk.</p>
                    <h2>Fasilitas untuk rombongan</h2>
                    <ul>
                        <li>Aula dan meeting room hingga 200 orang</li>
                        <li>Kolam renang</li>
                        <li>Lapangan luas untuk outbound dan upacara</li>
                        <li>Swaka Cafe &amp; Resto</li>
                    </ul>
                    <h2>Cocok untuk</h2>
                    <p>Gathering, outbound, LDK/LDKS, retreat, reuni, dan camping, baik untuk keluarga maupun rombongan sekolah, komunitas, gereja, atau perusahaan.</p>
                    <p>Lihat pilihan paket fullboard di halaman <a href="/gather">Gather</a>, atau hitung sendiri estimasi biayanya di <a href="/rancang-paket">Rancang Paket Rombongan</a>.</p>
                    HTML,
            ],
            [
                'slug' => 'kebaktian-padang-pemuda-hkbp-depok-dan-kisah-pohon-bambu',
                'title' => 'Kebaktian padang pemuda HKBP Depok 1 dan kisah pohon bambu',
                'category' => 'cerita',
                'published_at' => '2024-09-26 09:00:00',
                'source_url' => 'https://elji.id/kebaktian-padang-pemuda-pemudi-hkbp-depok-1-di-lingkung-gunung-adventure-camp/',
                'cover' => 'rombongan-hkbp',
                'excerpt' => 'Pemuda-pemudi HKBP Depok 1 berkemah dan beribadah di alam terbuka Lingkung Gunung. Cerita api unggun tentang kesabaran dan ketekunan.',
                'body' => <<<'HTML'
                    <p>Pemuda-pemudi HKBP Depok 1 mengadakan kebaktian padang di Lingkung Gunung. Berkemah di alam terbuka memberi ruang untuk kebersamaan dan refleksi, termasuk lewat cerita di sekitar api unggun.</p>
                    <h2>Pelajaran dari pohon bambu</h2>
                    <p>Konon, seorang petani menanam benih bambu dan merawatnya setiap hari. Bertahun-tahun tidak ada yang tumbuh di atas tanah, dan orang-orang mulai mengejeknya. Petani itu tetap tekun. Pada tahun kelima, bambu itu tumbuh sangat cepat hingga puluhan meter hanya dalam beberapa minggu.</p>
                    <p>Yang tidak terlihat selama bertahun-tahun adalah akar yang tumbuh kuat di bawah tanah. Kisah ini cocok dibawakan saat api unggun kegiatan LDKS atau pecinta alam: hasil besar sering lahir dari kesabaran dan ketekunan.</p>
                    HTML,
            ],
        ];

        foreach ($articles as $data) {
            $cover = $data['cover'];
            unset($data['cover']);

            $article = Article::query()->firstOrCreate(['slug' => $data['slug']], $data + ['is_published' => true]);

            $path = public_path("images/resort/{$cover}-1600.webp");
            if ($article->wasRecentlyCreated && is_file($path) && ! app()->runningUnitTests()) {
                $article->addMedia($path)->preservingOriginal()->usingFileName("{$cover}.webp")
                    ->withCustomProperties(['alt' => $data['title'], 'resmi' => true, 'sumber' => 'elji.id'])
                    ->toMediaCollection('cover');
            }
        }
    }
}
