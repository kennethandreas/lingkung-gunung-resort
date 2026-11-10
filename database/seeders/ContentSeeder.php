<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Konten website dari brosur 2026 (hal. 13-19) dan dokumen teknis.
 * Semua bisa diubah dari admin panel (menu Konten).
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->programs();
        $this->faqs();
        $this->pages();
    }

    private function programs(): void
    {
        $data = [
            'play' => [
                'Adventure & Adrenaline' => [
                    ['Trekking', 'Walk into nature', 'Menyusuri jalur hutan bersama pemandu menuju Air Terjun Eiji atau Pangrango.'],
                    ['Fun Games', 'Play together', 'Permainan outbound seru untuk mencairkan suasana keluarga, sekolah, atau kantor.'],
                    ['Team Building', 'Build stronger connections', 'Rangkaian tantangan kelompok dengan fasilitator untuk membangun kerja sama tim.'],
                    ['Rafting', 'Feel the river', 'Arung jeram 8-12 km dengan perahu, nahkoda, dan tim rescue berpengalaman.'],
                    ['Paintball', 'Challenge the team', 'Strategi, ketepatan, dan kerja sama di arena paintball dengan perlengkapan lengkap.'],
                    ['Offroad', 'Explore beyond the road', 'Taklukkan jalur menantang dengan kendaraan offroad dan driver berpengalaman.'],
                ],
                'Eco-Culture Experience' => [
                    ['Bertani & Berkebun', null, 'Menanam dan merawat kebun langsung bersama petani setempat.'],
                    ['Menangkap Ikan', null, 'Serunya menangkap ikan di kolam dengan tangan atau alat tradisional.'],
                    ['Main Angklung', null, 'Belajar memainkan angklung, alat musik bambu khas Sunda.'],
                    ['Tarian Sunda', null, 'Mengenal gerak dasar tari tradisional Sunda.'],
                    ['Melukis Caping', null, 'Berkreasi melukis caping, topi petani tradisional.'],
                    ['Membatik', null, 'Mengenal dan mencoba membatik dengan motif sederhana.'],
                    ['Flyingfox', null, 'Meluncur di atas lembah: melatih keberanian dan kepercayaan diri.'],
                ],
            ],
            'learn' => [
                'School' => [
                    ['Outing', null, 'Kegiatan luar kelas yang menyenangkan dan mendidik.'],
                    ['LDKS', null, 'Latihan Dasar Kepemimpinan Siswa di alam terbuka.'],
                    ['Pramuka', null, 'Perkemahan dan kegiatan kepramukaan.'],
                    ['Supercamp', null, 'Perkemahan intensif untuk pengembangan karakter.'],
                ],
                'Campus' => [
                    ['Leadership Camp', null, 'Pelatihan kepemimpinan untuk organisasi mahasiswa.'],
                    ['Diksar Mapala', null, 'Pendidikan dasar pecinta alam.'],
                ],
                'Youth/Children' => [
                    ['Nature Education', null, 'Belajar mengenal alam lewat pengalaman langsung.'],
                    ['Sanggar Alam', null, 'Ruang belajar imersif di kaki Gunung Gede Pangrango: budaya Sunda, permainan tradisional, berkebun, dan makan siang botram. Tersedia Half-Day dan Full-Day.'],
                ],
                'Tailor-Made Program' => [
                    ['Bivak Survival & Leadership Camp', 'Modul pelatihan intensif untuk transformasi karakter dan ketahanan mental.', "1. Teknik Bertahan Hidup di Alam Bebas: simulasi bertahan di kondisi tidak terduga.\n2. Navigasi Darat: pemetaan taktis dan pengambilan keputusan kolektif.\n3. Fisik, Mental & Disiplin: pengkondisian ketahanan individu.\n4. Kepemimpinan Siswa/Tim: simulasi rantai komando dan eksekusi lapangan."],
                ],
            ],
            'gather' => [
                'Corporate' => [
                    ['Team Building', null, null], ['Outbound', null, null], ['Leadership Training', null, null],
                    ['Meeting', null, 'Meeting room berkapasitas maksimal 200 orang dengan LCD projector, sound system, wifi, dan whiteboard.'],
                    ['Gathering', null, null], ['Employee Engagement', null, null],
                ],
                'Family' => [
                    ['Camping Ceria', null, null], ['Family Gathering', null, null], ['Glamping', null, null],
                    ['Swimming', null, null], ['Outdoor Family Experience', null, null],
                ],
                'Community' => [
                    ['Jambore', null, null], ['Campervan', null, null], ['Gowes', null, null], ['Community Gathering', null, null],
                ],
                'Team Building Games' => [
                    ['Life Seat', 'Duduk bersandar tanpa kursi!', 'Melatih kekompakan & kepercayaan.'],
                    ['Time Tunel', 'Raih tujuan, lewati rintangan!', 'Strategi & problem solving.'],
                    ['Building Tower', 'Bangun menara dengan kerjasama!', 'Komunikasi & koordinasi tim.'],
                    ['Balance Ball', 'Seimbang bersama, menang bersama!', 'Fokus & kerja sama tim.'],
                    ['Helium Stick', 'Jaga stik tetap turun, jangan sampai jatuh!', 'Konsentrasi & komunikasi.'],
                    ["Don't Touch Me", 'Raih target tanpa menyentuh air!', 'Strategi & koordinasi.'],
                    ['Flyingfox', 'Melatih keberanian & kepercayaan diri!', 'Tantangan ketinggian, percaya diri & fokus.'],
                    ['Spiderweb', 'Bersinergi untuk lewati rintangan!', 'Kerja sama tim & strategi.'],
                    ['Collector Ball', 'Kumpulkan bola sebanyak mungkin, raih kemenangan!', 'Strategi, kecepatan & kerja sama.'],
                    ['Triangle Line', 'Jaga keseimbangan, fokus hingga akhir!', 'Fokus, konsentrasi & keseimbangan.'],
                    ['Single Line', 'Satu langkah kecil, kerja sama yang besar!', 'Konsentrasi, dukungan tim & komunikasi.'],
                    ['Buldozer', 'Langkah serempak, tim kompak!', 'Kekompakan, koordinasi & komunikasi.'],
                ],
            ],
        ];

        $order = 0;
        foreach ($data as $section => $groups) {
            foreach ($groups as $group => $items) {
                foreach ($items as [$name, $summary, $description]) {
                    Program::query()->updateOrCreate(
                        ['slug' => Str::slug("{$section} {$group} {$name}")],
                        ['section' => $section, 'group' => $group, 'name' => $name, 'summary' => $summary, 'description' => $description, 'sort_order' => ++$order],
                    );
                }
            }
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['akses', 'Di mana lokasi Lingkung Gunung?', 'Jalan Akses Lingkung Gunung No.01, Kp. Baru, Cimande, Bogor, di ketinggian sekitar 850 MDPL dan berbatasan langsung dengan Taman Nasional Gunung Gede Pangrango. Cari "Lingkung Gunung Resort Bogor" di Google Maps atau "Lingkung Gunung" di Waze.'],
            ['akses', 'Rute mana yang paling cepat dari Jakarta?', 'Rute 1: Tol Jagorawi-Bocimi, keluar Gerbang Tol Caringin, lalu Pasar Cikereteg dan Pancawati. Rute 2: Tol Jagorawi, keluar Gerbang Tol Ciawi, lalu Pasar Cikereteg dan Pancawati. Keduanya bebas dari buka-tutup jalur Puncak.'],
            ['akses', 'Kendaraan apa saja yang bisa masuk?', 'Mobil pribadi, bus kecil, engkel, bus 3/4, bus pariwisata 3/4, hingga truk/tronton 3/4. Jalan menuju lokasi sebagian besar beraspal atau beton, sebagian kecil sirtu yang sudah dipadatkan.'],
            ['booking', 'Bagaimana cara memesan?', 'Pilih tanggal dan jumlah tamu di tombol "Cek Ketersediaan", pilih akomodasi, isi data pemesan, lalu bayar DP 25% atau lunas secara online. E-ticket dikirim ke email setelah pembayaran terverifikasi.'],
            ['booking', 'Berapa lama unit ditahan setelah saya memesan?', 'Unit ditahan selama 60 menit sambil menunggu pembayaran. Bila belum dibayar, unit otomatis dilepas dan bisa dipesan tamu lain.'],
            ['booking', 'Apakah sarapan sudah termasuk?', 'Ya, untuk akomodasi kamar dan tenda sudah termasuk sarapan untuk 2 orang (nasi goreng atau nasi uduk, telur ceplok atau tempe mendoan, air mineral, sambal, kerupuk).'],
            ['booking', 'Fasilitas apa saja yang tersedia?', 'Wifi dan kolam renang dapat digunakan tamu. Fasilitas setiap akomodasi tercantum di halaman masing-masing unit.'],
            ['pembayaran', 'Bagaimana sistem pembayarannya?', 'Bayar DP 25% saat memesan, lalu lunasi paling lambat 3 hari sebelum kedatangan (H-3). Anda juga bisa langsung membayar lunas.'],
            ['pembayaran', 'Metode pembayaran apa saja yang diterima?', 'Pembayaran online melalui Midtrans (QRIS, virtual account bank, dan e-wallet) atau transfer ke rekening resmi Bank Mandiri a.n. PT. Lingkung Gunung Sejahtera. Kami tidak menerima pembayaran tunai.'],
            ['pembayaran', 'Saya memesan kurang dari 3 hari sebelum kedatangan. Bisa bayar DP?', 'Untuk pemesanan kurang dari 3 hari sebelum kedatangan, pembayaran harus langsung lunas.'],
            ['rombongan', 'Berapa minimal peserta untuk paket rombongan?', 'Tergantung paket: mulai 30 orang (Fullboard LDKS A/B dan Room Glamping), 50 orang (Fullboard Camp A/B), hingga 100 orang (LDKS C dan Camp C). LDKS C hanya tersedia di hari kerja.'],
            ['rombongan', 'Berapa kapasitas ruang meeting?', 'Meeting room kami menampung hingga 200 orang, dilengkapi LCD projector, sound system, wifi, dan whiteboard. Tersedia pilihan aula dengan layout U-shape, classroom, atau lesehan.'],
            ['rombongan', 'Bisakah saya menyusun paket sendiri?', 'Bisa. Gunakan fitur "Rancang Paket Rombongan" untuk memilih paket, menu, aktivitas, dan item tambahan, lalu dapatkan estimasi harga dan proposal PDF. Tim sales kami akan menindaklanjuti via WhatsApp.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            Faq::query()->updateOrCreate(['question' => $question], ['category' => $category, 'answer' => $answer, 'sort_order' => $i + 1]);
        }
    }

    private function pages(): void
    {
        $pages = [
            // Beranda: judul = headline hero, deskripsi = subjudul, foto hero = koleksi "hero"
            'beranda' => ['Bangun di antara kabut, menatap Puncak Salak.', 'Glamping, pondokan, dan camping di 4 hektar alam terbuka. Lewat Tol Jagorawi–Bocimi, tanpa drama buka-tutup Puncak.', ''],
            'mekanisme-pembayaran' => ['Mekanisme Pembayaran', 'Cara pembayaran booking di Lingkung Gunung: DP 25%, pelunasan H-3, non-tunai.', <<<'HTML'
                <ol>
                    <li><strong>DP 25%</strong> dibayar ketika booking tanggal.</li>
                    <li><strong>Pelunasan</strong> paling lambat 3 hari sebelum hari kedatangan (H-3).</li>
                    <li>Kami <strong>tidak menerima pembayaran tunai</strong>.</li>
                    <li>Pembayaran online melalui Midtrans (QRIS, virtual account, e-wallet), atau transfer ke rekening resmi <strong>Bank Mandiri a.n. PT. Lingkung Gunung Sejahtera, No. 133-00-1868-7988</strong>.</li>
                    <li>Konfirmasi pembayaran transfer melalui telepon 0251-829-1453 atau WhatsApp 0811-1516-727.</li>
                </ol>
                <p>Pastikan pembayaran Anda sesuai nominal dan ke nomor rekening yang tertera. Pembayaran online terverifikasi otomatis dan e-ticket langsung dikirim ke email.</p>
                HTML],
            'syarat-dan-ketentuan' => ['Syarat & Ketentuan Pemesanan', 'Syarat dan ketentuan pemesanan langsung di website Lingkung Gunung.', <<<'HTML'
                <ul>
                    <li>Harga dihitung per malam sesuai kategori hari (weekday/weekend) dan dapat berubah sewaktu-waktu sebelum pemesanan dibuat.</li>
                    <li>Unit ditahan selama 60 menit sejak pemesanan dibuat. Pemesanan yang belum dibayar akan dibatalkan otomatis.</li>
                    <li>DP minimal 25% dari total. Sisa pembayaran wajib dilunasi paling lambat H-3. Pemesanan kurang dari H-3 wajib dibayar lunas.</li>
                    <li>Jumlah tamu tidak boleh melebihi kapasitas akomodasi yang dipesan.</li>
                    <li>Kebijakan pembatalan dan pengembalian dana mengikuti ketentuan resort dan akan diinformasikan oleh tim kami.</li>
                </ul>
                HTML],
            'kebijakan-privasi' => ['Kebijakan Privasi', 'Bagaimana Lingkung Gunung mengumpulkan, memakai, dan melindungi data pribadi tamu.', <<<'HTML'
                <p><em>Draf: perlu ditinjau oleh pihak resort sebelum go-live.</em></p>
                <h2>Data yang kami kumpulkan</h2>
                <p>Nama, nomor HP/WhatsApp, email, dan nama instansi (bila ada). Kami tidak meminta foto KTP saat pemesanan online.</p>
                <h2>Tujuan penggunaan</h2>
                <p>Memproses pemesanan dan pembayaran, mengirim e-ticket dan pengingat pelunasan, serta menghubungi Anda terkait kunjungan.</p>
                <h2>Pembayaran</h2>
                <p>Pembayaran online diproses oleh Midtrans. Kami tidak menyimpan data kartu atau rekening Anda.</p>
                <h2>Penyimpanan &amp; keamanan</h2>
                <p>Data disimpan di server dengan koneksi terenkripsi (HTTPS) dan hanya dapat diakses staf yang berwenang. Masa simpan data mengikuti ketentuan resort sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.</p>
                <h2>Hak Anda</h2>
                <p>Anda dapat meminta akses, perbaikan, atau penghapusan data pribadi dengan menghubungi kami melalui WhatsApp atau telepon resmi resort.</p>
                HTML],
            'contoh-itinerary-2-hari' => ['Contoh 2 Days Outbound', 'Contoh rundown 2 hari outbound & team building di Lingkung Gunung.', <<<'HTML'
                <h2>Hari 1: Team Building</h2>
                <table>
                    <thead><tr><th>Waktu</th><th>Agenda</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td>06.00 - 08.00</td><td>Start dari Jakarta</td><td>Dengan kendaraan TNI / bis menuju Lingkung Gunung</td></tr>
                        <tr><td>08.00 - 09.00</td><td>Tiba di Lingkung Gunung</td><td>Welcome drink, opening, penjelasan program kegiatan</td></tr>
                        <tr><td>09.00 - 10.00</td><td>Ice Breaking Big Group</td><td>Halo Apakabar, Angin Badai, Tupa / Pemburu</td></tr>
                        <tr><td>10.00 - 10.30</td><td>Small Group Dynamic</td><td>Yelling, permainan kelompok, hand knot</td></tr>
                        <tr><td>10.30 - 12.00</td><td>Initiative Problem Solving Games Sesi 1</td><td>Small group challenge &amp; personal challenge</td></tr>
                        <tr><td>12.00 - 13.00</td><td>Lunch</td><td>Lunch buffet, Zuhur jama' dengan Ashar</td></tr>
                        <tr><td>13.00 - 16.30</td><td>Initiative Problem Solving Games Sesi 2</td><td>Small group challenge &amp; personal challenge</td></tr>
                        <tr><td>15.30 - 16.00</td><td>Final Project: Save the Candle</td><td>Menjaga dan memindahkan lilin dari satu tempat ke tempat lain</td></tr>
                        <tr><td>16.00 - 16.30</td><td>De-brief, tugas kelompok, snack time</td><td>Penjelasan makna &amp; filosofi permainan</td></tr>
                        <tr><td>16.30 - 19.00</td><td>MCK, makan malam</td><td>Bersih diri &amp; makan malam</td></tr>
                        <tr><td>19.00 - 21.00</td><td>Api unggun / performance</td><td>Penampilan kreasi masing-masing kelompok</td></tr>
                    </tbody>
                </table>
                <h2>Hari 2: Team Building</h2>
                <table>
                    <thead><tr><th>Waktu</th><th>Agenda</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td>06.00 - 07.00</td><td>Breakfast</td><td>Breakfast bersama &amp; persiapan kegiatan</td></tr>
                        <tr><td>07.00 - 10.00</td><td>Rafting &amp; Paintball</td><td>Safety briefing, adventure rafting ± 8-12 km (2-3 jam), coconut drink &amp; snack, paintball</td></tr>
                        <tr><td>10.00 - 11.00</td><td>The Brief</td><td>Evaluasi umum &amp; closing statement</td></tr>
                        <tr><td>11.00 - 12.30</td><td>Cleaning &amp; packing</td><td>Pengemasan barang &amp; persiapan kepulangan</td></tr>
                        <tr><td>12.30 - 13.30</td><td>Lunch, snack pulang, sayonara</td><td>Kembali ke Jakarta dengan pengalaman yang berkesan</td></tr>
                    </tbody>
                </table>
                <p><em>"Datang sebagai Tim, Pulang sebagai Keluarga."</em></p>
                HTML],
        ];

        foreach ($pages as $slug => [$title, $meta, $body]) {
            Page::query()->firstOrCreate(['slug' => $slug], ['title' => $title, 'meta_description' => $meta, 'body' => trim($body)]);
        }
    }
}
