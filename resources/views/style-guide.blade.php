<x-layouts.public title="Style Guide" description="Design tokens dan komponen UI website Lingkung Gunung." transparent-header>
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    {{-- ============================================================
         1. CONTOH SUASANA: HERO
         ============================================================ --}}
    <section class="relative isolate flex min-h-[min(92svh,60rem)] items-end overflow-hidden bg-hutan-950 pb-10 pt-32 sm:pb-16">
        <x-ui.picture src="images/placeholder/glamping-luar" alt="Glamping Imah Lembah di antara kabut dan pegunungan" priority sizes="100vw" ratio="" class="!absolute inset-0 -z-20" />
        {{-- Gradien agar teks selalu terbaca + lapisan kabut yang bergerak pelan --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-hutan-950 via-hutan-950/45 to-hutan-950/30" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-hutan-950/55 via-hutan-950/15 to-transparent" aria-hidden="true"></div>
        <div class="absolute inset-x-[-10%] top-1/3 -z-10 h-40 animate-kabut rounded-none bg-kabut-50/20 blur-3xl" aria-hidden="true"></div>

        <div class="container-lg w-full">
            <x-ui.badge variant="kaca" icon="heroicon-m-map-pin">850 MDPL · Caringin, Bogor</x-ui.badge>
            <p class="mt-6 font-script text-3xl text-emas-300 sm:text-4xl">Tempat Alam Bercerita</p>
            <h1 class="mt-2 max-w-4xl font-display text-display-2xl font-semibold text-balance text-white">
                Bangun di antara kabut, menatap Puncak Salak.
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-kabut-100">
                Glamping, pondokan, dan camping di 4 hektar alam terbuka. Lewat Tol Jagorawi–Bocimi, tanpa drama buka-tutup Puncak.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-ui.button variant="accent" size="lg" href="#cek-ketersediaan" icon-right="heroicon-m-arrow-right">Cek Ketersediaan</x-ui.button>
                <x-ui.button variant="light" size="lg" :href="route('rancang-paket')">Rancang Paket Rombongan</x-ui.button>
            </div>

            {{-- Kotak cek ketersediaan (tampilan saja; versi aktif dibuat setelah disetujui) --}}
            <form id="cek-ketersediaan" class="mt-12 grid gap-3 rounded-kartu bg-white/95 p-4 shadow-angkat backdrop-blur-md sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end lg:p-5" onsubmit="return false">
                <x-ui.field label="Check-in" for="sg-in">
                    <x-ui.input id="sg-in" type="date" icon="heroicon-o-calendar-days" />
                </x-ui.field>
                <x-ui.field label="Check-out" for="sg-out">
                    <x-ui.input id="sg-out" type="date" icon="heroicon-o-calendar-days" />
                </x-ui.field>
                <x-ui.field label="Tamu">
                    <x-ui.stepper :value="2" :max="40" label="Jumlah tamu" class="w-full justify-between sm:w-auto" />
                </x-ui.field>
                <x-ui.button variant="primary" size="lg" type="submit" class="w-full lg:w-auto" icon="heroicon-m-magnifying-glass">Cari</x-ui.button>
            </form>
        </div>
    </section>

    {{-- ============================================================
         2. PENGANTAR ARAH DESAIN
         ============================================================ --}}
    <section class="section bg-kabut-50">
        <div class="container-lg grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-center">
            <x-ui.reveal>
                <x-ui.section-heading eyebrow="Usulan arah desain" title="Hangat, alami, dan premium, tanpa terasa seperti template." />
                <div class="mt-6 space-y-4 text-lg leading-relaxed text-kabut-700">
                    <p><strong class="text-hutan-900">Suasana:</strong> kabut pagi di atas hutan pinus, kayu hangat, dan sentuhan emas seperti kompas di brosur. Foto jadi bintang utama; teks dan warna mendukung, bukan bersaing.</p>
                    <p><strong class="text-hutan-900">Warna:</strong> hijau hutan sebagai warna utama, emas sebagai aksen ajakan bertindak, coklat tanah untuk kehangatan, dan netral "kabut" yang lebih hangat dari abu-abu biasa.</p>
                    <p><strong class="text-hutan-900">Huruf:</strong> <span class="font-display font-semibold">Fraunces</span> (serif lembut berkarakter) untuk judul, <span class="font-semibold">Plus Jakarta Sans</span> (dirancang di Indonesia, sangat terbaca di HP) untuk isi, dan <span class="font-script text-xl text-emas-700">Kaushan Script</span> hanya untuk tagline, seperti tulisan tangan di brosur.</p>
                </div>
            </x-ui.reveal>
            <x-ui.reveal :delay="150" class="grid grid-cols-2 gap-4">
                <x-ui.picture src="images/placeholder/swaka-teras" alt="Teras Swaka Cafe dengan pemandangan lembah" ratio="aspect-[3/4]" class="rounded-foto shadow-angkat" sizes="(min-width:1024px) 25vw, 50vw" />
                <div class="grid gap-4 pt-10">
                    <x-ui.picture src="images/placeholder/glamping-dalam" alt="Interior glamping A-frame dengan jendela ke pegunungan" ratio="aspect-square" class="rounded-foto shadow-lembut" sizes="(min-width:1024px) 25vw, 50vw" />
                    <div class="rounded-foto bg-hutan-800 p-5 text-kabut-100">
                        <p class="font-script text-2xl text-emas-300">Good food, good people</p>
                        <p class="mt-1 text-sm">Great story. Foto di halaman ini sementara diambil dari brosur.</p>
                    </div>
                </div>
            </x-ui.reveal>
        </div>
    </section>

    {{-- ============================================================
         3. PALET WARNA
         ============================================================ --}}
    <section class="section border-t border-kabut-200 bg-white">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="01 · Warna" title="Palet" lead="Setiap warna punya skala 50–950. Nama tokennya berbahasa Indonesia, mis. bg-hutan-800, text-emas-700." />

            <div class="mt-12 space-y-10">
                @foreach ($palette as $key => $family)
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="font-display text-2xl font-semibold text-hutan-900">{{ $family['label'] }}</h3>
                            <p class="text-sm text-kabut-600">{{ $family['note'] }}</p>
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11">
                            @foreach ($family['shades'] as $shade => $hex)
                                <div>
                                    <div class="h-16 rounded-xl ring-1 ring-black/5 sm:h-20" style="background: {{ $hex }}"></div>
                                    <p class="mt-1.5 text-xs font-semibold text-kabut-800">{{ $key }}-{{ $shade }}</p>
                                    <p class="text-[0.6875rem] uppercase text-kabut-600">{{ $hex }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div>
                    <h3 class="font-display text-2xl font-semibold text-hutan-900">Status</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                        @foreach ([['Sukses', 'sukses', '#1f8a5b'], ['Peringatan', 'peringatan', '#b45309'], ['Bahaya', 'bahaya', '#b42318'], ['Info', 'info', '#1d6fa5']] as [$label, $token, $hex])
                            <div class="flex items-center gap-3 rounded-xl p-3 ring-1 ring-kabut-200">
                                <span class="size-10 rounded-lg" style="background: {{ $hex }}"></span>
                                <span><span class="block text-sm font-semibold">{{ $label }}</span><span class="text-xs text-kabut-600">{{ $token }}-600 · {{ $hex }}</span></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="font-display text-2xl font-semibold text-hutan-900">Kontras (WCAG, dihitung otomatis)</h3>
                    <div class="mt-4 overflow-x-auto rounded-xl ring-1 ring-kabut-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-kabut-100 text-kabut-700">
                                <tr><th class="px-4 py-3">Pasangan</th><th class="px-4 py-3">Contoh</th><th class="px-4 py-3">Rasio</th><th class="px-4 py-3">Hasil</th></tr>
                            </thead>
                            <tbody class="divide-y divide-kabut-200">
                                @foreach ($contrasts as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-kabut-800">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3"><span class="rounded-md px-3 py-1 font-semibold" style="color: {{ $row['fg'] }}; background: {{ $row['bg'] }}">Aa Lingkung</span></td>
                                        <td class="px-4 py-3 font-semibold tabular-nums">{{ number_format($row['ratio'], 2, ',', '.') }} : 1</td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge :variant="$row['level'] === 'Gagal' ? 'bahaya' : (str_starts_with($row['level'], 'AA (') ? 'peringatan' : 'sukses')">{{ $row['level'] }}</x-ui.badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         4. TIPOGRAFI
         ============================================================ --}}
    <section class="section bg-kabut-100/60 bg-kontur">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="02 · Huruf" title="Tipografi" lead="Ukuran judul menyesuaikan lebar layar (fluid), jadi tetap proporsional dari HP sampai desktop." />
            <div class="mt-12 space-y-8 rounded-kartu bg-white p-6 shadow-lembut sm:p-10">
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-baseline">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Display 2XL · Fraunces 600</p>
                    <p class="font-display text-display-2xl font-semibold text-hutan-900">Tempat Alam Bercerita</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-baseline">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Display XL</p>
                    <p class="font-display text-display-xl font-semibold text-hutan-900">Glamping Imah Lembah</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-baseline">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Display LG</p>
                    <p class="font-display text-display-lg font-semibold text-hutan-900">Paket rombongan fullboard</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-baseline">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Display MD</p>
                    <p class="font-display text-display-md font-semibold text-hutan-900">Swaka Cafe &amp; Resto</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-baseline">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Aksen · Kaushan Script</p>
                    <p class="font-script text-4xl text-emas-700">Lebih dekat dengan alam</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-[10rem_1fr]">
                    <p class="text-xs font-semibold uppercase tracking-widest text-kabut-600">Isi · Plus Jakarta Sans</p>
                    <div class="max-w-prose space-y-3 text-lg leading-relaxed text-kabut-700">
                        <p>Berbatasan langsung dengan Taman Nasional Gunung Gede Pangrango, Lingkung Gunung menawarkan panorama Puncak Salak dan vegetasi alami yang autentik. Akomodasi, ruang makan, dan area outbound ada dalam satu ekosistem.</p>
                        <p class="text-base text-kabut-600">Teks kecil (16px) tetap nyaman dibaca di HP. <a href="#" class="font-semibold text-hutan-700 underline decoration-emas-400 decoration-2 underline-offset-4 hover:text-hutan-900">Contoh tautan</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         5. TOMBOL & BADGE
         ============================================================ --}}
    <section class="section bg-white">
        <div class="container-lg grid gap-16 lg:grid-cols-2">
            <div>
                <x-ui.section-heading eyebrow="03 · Aksi" title="Tombol" />
                <div class="mt-8 space-y-6">
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button variant="primary">Utama</x-ui.button>
                        <x-ui.button variant="accent">Cek Ketersediaan</x-ui.button>
                        <x-ui.button variant="outline">Garis</x-ui.button>
                        <x-ui.button variant="ghost">Tanpa latar</x-ui.button>
                        <x-ui.button variant="whatsapp" :href="\App\Support\WhatsApp::link('Halo, saya ingin bertanya')" target="_blank" rel="noopener">WhatsApp</x-ui.button>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button size="sm">Kecil</x-ui.button>
                        <x-ui.button size="md" icon-right="heroicon-m-arrow-right">Sedang</x-ui.button>
                        <x-ui.button size="lg" icon="heroicon-m-calendar-days">Besar</x-ui.button>
                        <x-ui.button disabled>Nonaktif</x-ui.button>
                    </div>
                    <div class="flex flex-wrap gap-3 rounded-kartu bg-hutan-900 p-6">
                        <x-ui.button variant="light">Di atas foto/gelap</x-ui.button>
                        <x-ui.button variant="accent">Aksen di gelap</x-ui.button>
                    </div>
                </div>
            </div>
            <div>
                <x-ui.section-heading eyebrow="04 · Label" title="Badge" />
                <div class="mt-8 flex flex-wrap gap-2">
                    <x-ui.badge>Netral</x-ui.badge>
                    <x-ui.badge variant="hutan" icon="heroicon-m-sparkles">Favorit keluarga</x-ui.badge>
                    <x-ui.badge variant="emas">Promo</x-ui.badge>
                    <x-ui.badge variant="tanah">Eco-culture</x-ui.badge>
                    <x-ui.badge variant="sukses">Lunas</x-ui.badge>
                    <x-ui.badge variant="peringatan">Menunggu pembayaran</x-ui.badge>
                    <x-ui.badge variant="bahaya">Penuh</x-ui.badge>
                    <x-ui.badge variant="info">DP dibayar</x-ui.badge>
                </div>
                <div class="mt-6 rounded-kartu bg-hutan-900 p-6"><x-ui.badge variant="kaca" icon="heroicon-m-map-pin">Badge kaca di atas foto</x-ui.badge></div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         6. FORM
         ============================================================ --}}
    <section class="section bg-kabut-50">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="05 · Isian" title="Form" lead="Tinggi 48px agar mudah disentuh, label selalu terlihat, dan pesan error ramah dalam Bahasa Indonesia." />
            <div class="mt-10 grid gap-6 rounded-kartu bg-white p-6 shadow-lembut sm:grid-cols-2 sm:p-8">
                <x-ui.field label="Nama lengkap" for="sg-nama" hint="Sesuai kartu identitas, untuk check-in." required>
                    <x-ui.input id="sg-nama" placeholder="mis. Siti Aminah" autocomplete="name" />
                </x-ui.field>
                <x-ui.field label="No. WhatsApp" for="sg-hp" error="Nomor WhatsApp sepertinya belum lengkap. Contoh: 0812 3456 7890." required>
                    <x-ui.input id="sg-hp" type="tel" value="0812" invalid icon="heroicon-o-phone" aria-describedby="sg-hp-error" />
                </x-ui.field>
                <x-ui.field label="Email" for="sg-email" hint="E-ticket dikirim ke email ini.">
                    <x-ui.input id="sg-email" type="email" placeholder="nama@email.com" icon="heroicon-o-envelope" />
                </x-ui.field>
                <x-ui.field label="Akomodasi" for="sg-tipe">
                    <x-ui.select id="sg-tipe">
                        @foreach ($unitTypes as $type)
                            <option>{{ $type['name'] }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Jumlah unit">
                    <x-ui.stepper :value="1" :max="10" label="Jumlah unit" />
                </x-ui.field>
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-kabut-800">Pembayaran</legend>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-hutan-600 bg-hutan-50/60 p-4 ring-2 ring-hutan-600/15">
                        <input type="radio" name="sg-bayar" checked class="mt-0.5 size-5 accent-hutan-700">
                        <span><span class="block font-semibold text-hutan-900">DP 25% sekarang</span><span class="text-sm text-kabut-600">Sisanya dilunasi paling lambat H-3.</span></span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-kabut-300 p-4 transition hover:border-hutan-600">
                        <input type="radio" name="sg-bayar" class="mt-0.5 size-5 accent-hutan-700">
                        <span><span class="block font-semibold text-hutan-900">Bayar lunas</span><span class="text-sm text-kabut-600">Langsung dapat e-ticket.</span></span>
                    </label>
                </fieldset>
                <label class="flex items-start gap-3 text-sm text-kabut-700 sm:col-span-2">
                    <input type="checkbox" class="mt-0.5 size-5 rounded accent-hutan-700">
                    <span>Saya menyetujui <a href="#" class="font-semibold text-hutan-700 underline underline-offset-2">syarat &amp; ketentuan</a> serta kebijakan privasi.</span>
                </label>
            </div>
        </div>
    </section>

    {{-- ============================================================
         7. KARTU AKOMODASI (data asli dari database)
         ============================================================ --}}
    <section class="section bg-white">
        <div class="container-lg">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <x-ui.section-heading eyebrow="Stay" :script="true" title="Pilih tempat menginap" lead="Kartu memakai data dari admin panel (nama & harga brosur). Unit tanpa foto otomatis menampilkan placeholder." />
                <x-ui.button variant="outline" href="#" icon-right="heroicon-m-arrow-right">Semua akomodasi</x-ui.button>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($unitTypes as $i => $type)
                    <x-ui.reveal :delay="($i % 3) * 120">
                        <x-ui.card-akomodasi
                            :name="$type['name']"
                            :tagline="$type['tagline']"
                            :image="$type['image']"
                            :price="$type['price']"
                            :price-unit="$type['unit']"
                            :capacity="$type['capacity']"
                            :features="$type['features']"
                            :badge="$i === 1 ? 'Paling diminati' : null"
                            class="h-full"
                        />
                    </x-ui.reveal>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================
         8. GALERI
         ============================================================ --}}
    <section class="section bg-hutan-950 text-white">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="Galeri" :script="true" :dark="true" title="Cerita dari ketinggian 850 MDPL" />
            <div class="mt-10 grid auto-rows-[10rem] grid-cols-2 gap-3 sm:auto-rows-[14rem] lg:grid-cols-4">
                <x-ui.picture src="images/placeholder/glamping-luar" alt="Deretan glamping A-frame" ratio="" class="col-span-2 row-span-2 rounded-foto" sizes="(min-width:1024px) 50vw, 100vw" img-class="transition duration-700 ease-alam hover:scale-105" />
                <x-ui.picture src="images/placeholder/kemping-ceria" alt="Tenda Kemping Ceria di bawah pinus" ratio="" class="rounded-foto" sizes="(min-width:1024px) 25vw, 50vw" img-class="transition duration-700 ease-alam hover:scale-105" />
                <x-ui.picture src="images/placeholder/swaka-teras" alt="Teras Swaka Cafe" ratio="" class="row-span-2 rounded-foto" sizes="(min-width:1024px) 25vw, 50vw" img-class="transition duration-700 ease-alam hover:scale-105" />
                <x-ui.picture src="images/placeholder/meeting-luar" alt="Gedung meeting room dikelilingi pohon cemara" ratio="" class="rounded-foto" sizes="(min-width:1024px) 25vw, 50vw" img-class="transition duration-700 ease-alam hover:scale-105" />
            </div>
        </div>
    </section>

    {{-- ============================================================
         9. ALUR BOOKING: INDIKATOR LANGKAH + RINGKASAN HARGA
         ============================================================ --}}
    <section class="section bg-kabut-50">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="06 · Pemesanan" title="Langkah booking & ringkasan harga" lead="Progres selalu terlihat. Di desktop ringkasan harga menempel di samping; di HP ringkasan ada di bawah tombol lanjut." />
            <div class="mt-10 rounded-kartu bg-white p-5 shadow-lembut sm:p-8">
                <x-ui.steps :steps="['Tanggal & tamu', 'Akomodasi', 'Data pemesan', 'Pembayaran']" :current="3" />
                <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_22rem]">
                    <div class="space-y-6">
                        <h3 class="font-display text-2xl font-semibold text-hutan-900">Data pemesan</h3>
                        <x-ui.alert variant="info" title="Stok ditahan untuk Anda">Kami menahan unit selama 60 menit sambil Anda menyelesaikan pembayaran.</x-ui.alert>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.field label="Nama lengkap" for="sg-b-nama" required><x-ui.input id="sg-b-nama" value="Siti Aminah" /></x-ui.field>
                            <x-ui.field label="No. WhatsApp" for="sg-b-hp" required><x-ui.input id="sg-b-hp" type="tel" value="0812 1111 2222" /></x-ui.field>
                        </div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                            <x-ui.button variant="ghost" icon="heroicon-m-arrow-left">Kembali</x-ui.button>
                            <x-ui.button variant="primary" size="lg" icon-right="heroicon-m-arrow-right">Lanjut ke pembayaran</x-ui.button>
                        </div>
                    </div>
                    <x-ui.price-summary
                        class="lg:sticky lg:top-24 lg:self-start"
                        meta="Kam, 15 Okt – Min, 18 Okt 2026 · 3 malam · 4 tamu"
                        :lines="[
                            ['label' => 'Glamping Imah Lembah × 1', 'detail' => 'Kam (WD) Rp670.000 · Jum (WE) Rp815.000 · Sab (WE) Rp815.000', 'amount' => 2300000],
                        ]"
                        :discount="0"
                        :total="2300000"
                        :dp="575000"
                    >
                        <p class="mt-4 flex items-center gap-2 text-xs text-kabut-600"><x-heroicon-o-lock-closed class="size-4" aria-hidden="true" /> Pembayaran aman via Midtrans (QRIS, VA, e-wallet)</p>
                    </x-ui.price-summary>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         10. ALERT & MODAL
         ============================================================ --}}
    <section class="section bg-white">
        <div class="container-lg grid gap-12 lg:grid-cols-2">
            <div>
                <x-ui.section-heading eyebrow="07 · Pesan" title="Alert" />
                <div class="mt-8 space-y-3">
                    <x-ui.alert variant="sukses" title="Pembayaran diterima">E-ticket sudah dikirim ke email Anda.</x-ui.alert>
                    <x-ui.alert variant="peringatan" title="Segera lunasi">Sisa pembayaran Rp1.725.000 paling lambat Senin, 12 Oktober 2026.</x-ui.alert>
                    <x-ui.alert variant="bahaya" title="Maaf, unit sudah penuh">Glamping Imah Lembah tidak tersedia untuk 16 Oktober 2026. Silakan pilih tanggal atau akomodasi lain.</x-ui.alert>
                    <x-ui.alert variant="info">Pemesanan kurang dari 3 hari sebelum kedatangan harus dibayar lunas.</x-ui.alert>
                </div>
            </div>
            <div>
                <x-ui.section-heading eyebrow="08 · Dialog" title="Modal" lead="Di HP muncul dari bawah layar. Bisa ditutup dengan Esc, tombol ×, atau ketuk latar." />
                <div class="mt-8">
                    <x-ui.button variant="outline" x-data x-on:click="$dispatch('buka-modal', 'contoh')" icon="heroicon-m-window">Buka contoh modal</x-ui.button>
                </div>
                <x-ui.modal name="contoh" title="Kebijakan pembayaran">
                    <ul class="space-y-3 text-kabut-700">
                        <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> DP 25% saat booking, pelunasan paling lambat H-3.</li>
                        <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> Seluruh pembayaran non-tunai.</li>
                        <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> Slot yang tidak dibayar dalam 60 menit otomatis dilepas.</li>
                    </ul>
                    <x-slot:footer>
                        <x-ui.button variant="ghost" x-on:click="$dispatch('tutup-modal', 'contoh')">Tutup</x-ui.button>
                        <x-ui.button variant="primary" x-on:click="$dispatch('tutup-modal', 'contoh')">Saya mengerti</x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>
    </section>

    {{-- ============================================================
         11. TOKEN LAIN: RADIUS, BAYANGAN, GERAK
         ============================================================ --}}
    <section class="section bg-kabut-100/60">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="09 · Token" title="Radius, bayangan, jarak & gerak" />
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-kartu bg-white p-6 shadow-lembut"><p class="font-semibold">rounded-kartu</p><p class="text-sm text-kabut-600">1.25rem · kartu & panel</p></div>
                <div class="rounded-foto bg-white p-6 shadow-lembut"><p class="font-semibold">rounded-foto</p><p class="text-sm text-kabut-600">1rem · foto & galeri</p></div>
                <div class="rounded-kartu bg-white p-6 shadow-angkat"><p class="font-semibold">shadow-angkat</p><p class="text-sm text-kabut-600">saat kartu di-hover</p></div>
                <div class="rounded-none bg-white p-6 text-center shadow-lembut"><p class="font-semibold">rounded-none</p><p class="text-sm text-kabut-600">tombol & badge</p></div>
            </div>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-kartu bg-white p-6 shadow-lembut">
                    <p class="font-semibold">Jarak</p>
                    <p class="mt-1 text-sm text-kabut-600">Section: <code>.section</code> (64 → 80 → 112px). Lebar konten: <code>.container-lg</code> (maks. 1280px, gutter 16px di HP).</p>
                </div>
                <div class="rounded-kartu bg-white p-6 shadow-lembut">
                    <p class="font-semibold">Gerak</p>
                    <p class="mt-1 text-sm text-kabut-600">Easing <code>ease-alam</code>, durasi 300–800ms. Konten muncul halus saat di-scroll (<code>&lt;x-ui.reveal&gt;</code>), kartu terangkat saat di-hover, kabut hero bergerak pelan. Semua animasi otomatis mati bila pengguna mengaktifkan "kurangi animasi" di perangkatnya.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         12. CTA PENUTUP (contoh section rombongan)
         ============================================================ --}}
    <section class="relative isolate overflow-hidden bg-tanah-900 py-20 text-white sm:py-28">
        <x-ui.picture src="images/placeholder/meeting-luar" alt="" ratio="" class="!absolute inset-0 -z-20 opacity-40" sizes="100vw" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-tanah-950 via-tanah-950/85 to-tanah-950/40" aria-hidden="true"></div>
        <div class="container-lg">
            <x-ui.reveal class="max-w-2xl">
                <p class="font-script text-3xl text-emas-300">Datang sebagai tim, pulang sebagai keluarga</p>
                <h2 class="mt-3 font-display text-display-xl font-semibold text-balance">Rancang paket rombongan dalam 3 menit.</h2>
                <p class="mt-5 text-lg text-tanah-100">Pilih paket fullboard, menu, dan aktivitas. Estimasi harga langsung muncul, proposal PDF siap dikirim ke tim Anda.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button variant="accent" size="lg" href="#" icon-right="heroicon-m-arrow-right">Mulai rancang paket</x-ui.button>
                    <x-ui.button variant="light" size="lg" :href="\App\Support\WhatsApp::link('Halo, saya ingin konsultasi paket rombongan')" target="_blank" rel="noopener">Tanya tim sales</x-ui.button>
                </div>
            </x-ui.reveal>
        </div>
    </section>
</x-layouts.public>
