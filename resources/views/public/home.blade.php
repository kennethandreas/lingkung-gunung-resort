@use('App\Models\Setting')
@use('App\Support\ResortContact')
@use('App\Support\WhatsApp')

@php
    $fromPrice = $unitTypes->pluck('prices.from')->filter()->min();
    $heroMedia = $hero?->getFirstMedia('hero');
    // Slide hero: unggahan admin (bila ada & bukan gambar sementara) didahulukan, lalu foto resmi resort
    $slides = collect([
        $heroMedia && ! $heroMedia->getCustomProperty('placeholder') && ! $heroMedia->getCustomProperty('resmi') ? ['media' => $heroMedia, 'src' => null, 'alt' => $heroMedia->getCustomProperty('alt', 'Lingkung Gunung')] : null,
        ['media' => null, 'src' => 'images/resort/deck-lembah', 'alt' => 'Deck pandang menghadap lembah dan pegunungan'],
        ['media' => null, 'src' => 'images/resort/glamping-udara', 'alt' => 'Deretan glamping di lereng hijau, dilihat dari udara'],
        ['media' => null, 'src' => 'images/resort/kafe-teras', 'alt' => 'Teras makan di antara pohon pinus'],
    ])->filter()->values();

    // Akomodasi unggulan = yang sudah punya foto asli; sisanya tampil sebagai daftar ringkas
    $featured = $unitTypes->filter(fn ($row) => $row['cover'] && ! $row['cover']->getCustomProperty('placeholder'))->take(3);
    $others = $unitTypes->reject(fn ($row) => $featured->contains(fn ($f) => $f['model']->is($row['model'])));

    $packageFrom = $packages->flatMap->variants->min('price_weekday');
    $packageMinPax = $packages->flatMap->variants->min('min_pax');
    $activityPhotos = ['trekking' => 'trekking-sungai', 'rafting' => 'rafting', 'paintball' => 'paintball', 'offroad' => 'offroad'];
    $highlightActivities = $activities->filter(fn ($a) => isset($activityPhotos[strtolower($a->name)]))->values();
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode(Setting::get('google_maps_query'));
    $wazeUrl = 'https://waze.com/ul?q='.urlencode(Setting::get('waze_query'));

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => 'Lingkung Gunung Adventure Camp Bogor',
        'description' => 'Resort dan adventure camp di ketinggian 850 MDPL, berbatasan dengan Taman Nasional Gunung Gede Pangrango. Glamping, kamar, tenda, outbound, dan paket rombongan.',
        'url' => route('home'),
        'telephone' => Setting::get('contact_phone'),
        'email' => ResortContact::email(),
        'image' => asset('images/resort/glamping-udara-1600.webp'),
        'priceRange' => $fromPrice ? 'Mulai Rp'.number_format($fromPrice, 0, ',', '.').' per malam' : null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Jalan Akses Lingkung Gunung No.01, Kp. Baru, Cimande',
            'addressLocality' => 'Kec. Caringin, Kabupaten Bogor',
            'addressRegion' => 'Jawa Barat',
            'postalCode' => '16730',
            'addressCountry' => 'ID',
        ],
        'amenityFeature' => collect(['Wifi', 'Kolam renang', 'Meeting room 200 orang', 'Area outbound', 'Restoran'])->map(fn ($f) => ['@type' => 'LocationFeatureSpecification', 'name' => $f, 'value' => true])->all(),
        'sameAs' => ResortContact::sameAs() ?: null,
    ];
@endphp

<x-layouts.public transparent-header :og-image="asset('images/resort/glamping-udara-1600.webp')">
    <x-slot:head>
        <link rel="preload" as="image" href="{{ asset(($slides[0]['src'] ?? 'images/resort/deck-lembah').'-960.webp') }}" imagesrcset="{{ asset(($slides[0]['src'] ?? 'images/resort/deck-lembah').'-480.webp') }} 480w, {{ asset(($slides[0]['src'] ?? 'images/resort/deck-lembah').'-960.webp') }} 960w, {{ asset(($slides[0]['src'] ?? 'images/resort/deck-lembah').'-1600.webp') }} 1600w" imagesizes="100vw" fetchpriority="high">
        <script type="application/ld+json">{!! json_encode(array_filter($jsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    {{-- ============================== HERO ============================== --}}
    <section class="relative isolate flex min-h-[92svh] items-end overflow-hidden bg-hutan-950 pb-28 pt-36 sm:pb-36"
             x-data="{
                active: 0, count: {{ $slides->count() }}, playing: ! window.matchMedia('(prefers-reduced-motion: reduce)').matches, timer: null,
                start() { clearInterval(this.timer); if (this.playing && this.count > 1) this.timer = setInterval(() => this.go(this.active + 1), 7000) },
                go(i) { this.active = (i + this.count) % this.count },
                toggle() { this.playing = ! this.playing; this.start() },
             }"
             x-init="start()" aria-roledescription="carousel" aria-label="Foto Lingkung Gunung">
        @foreach ($slides as $i => $slide)
            <div class="absolute inset-0 -z-20 transition-opacity duration-[1400ms] ease-alam" x-bind:class="active === {{ $i }} ? 'opacity-100' : 'opacity-0'" @if ($i > 0) aria-hidden="true" @endif>
                <x-ui.picture :media="$slide['media']" :src="$slide['src']" :alt="$slide['alt']" :priority="$i === 0" sizes="100vw" ratio="" class="!absolute inset-0" />
            </div>
        @endforeach
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-hutan-950/85 via-hutan-950/30 to-hutan-950/35" aria-hidden="true"></div>

        <div class="container-lg w-full">
            <p class="masuk label-kecil flex items-center gap-3 text-kabut-100"><span class="h-px w-8 bg-emas-300" aria-hidden="true"></span>Pancawati · Caringin · Bogor · 850 MDPL</p>
            <h1 class="masuk mt-5 max-w-4xl font-display text-display-2xl text-balance text-white" style="--masuk-delay: 80ms">Resort &amp; adventure camp di kaki Gunung Gede Pangrango</h1>
            <p class="masuk mt-6 max-w-2xl text-lg leading-relaxed text-kabut-100" style="--masuk-delay: 160ms">Menginap di kamar berbalkon, glamping, atau tenda di bawah pinus. Outbound, aktivitas alam, dan paket rombongan dalam satu kawasan 4 hektar.</p>
            <div class="masuk mt-9 flex flex-wrap items-center gap-x-8 gap-y-4" style="--masuk-delay: 240ms">
                <x-ui.button variant="accent" size="lg" href="#cek-ketersediaan" x-on:click="$nextTick(() => document.querySelector('#cek-ketersediaan input')?.focus())">Cek ketersediaan</x-ui.button>
                <a href="{{ route('gather') }}" class="tautan-panah text-white">Paket rombongan <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
            </div>
        </div>

        @if ($slides->count() > 1)
            <div class="absolute bottom-28 right-4 hidden items-center gap-1 text-white sm:bottom-36 sm:right-8 md:flex lg:right-[max(2rem,calc((100vw-80rem)/2+2rem))]">
                <span class="label-kecil mr-3 tabular-nums" aria-live="polite"><span x-text="String(active + 1).padStart(2, '0')">01</span> / {{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</span>
                <button type="button" class="flex size-10 items-center justify-center border border-white/40 transition-colors hover:bg-white hover:text-hutan-950" x-on:click="go(active - 1); start()" aria-label="Foto sebelumnya"><x-heroicon-m-arrow-left class="size-4" aria-hidden="true" /></button>
                <button type="button" class="flex size-10 items-center justify-center border border-white/40 transition-colors hover:bg-white hover:text-hutan-950" x-on:click="go(active + 1); start()" aria-label="Foto berikutnya"><x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></button>
                <button type="button" class="flex size-10 items-center justify-center border border-white/40 transition-colors hover:bg-white hover:text-hutan-950" x-on:click="toggle()" x-bind:aria-label="playing ? 'Jeda slideshow' : 'Putar slideshow'">
                    <x-heroicon-m-pause class="size-4" x-show="playing" aria-hidden="true" />
                    <x-heroicon-m-play class="size-4" x-show="! playing" x-cloak aria-hidden="true" />
                </button>
            </div>
        @endif
    </section>

    {{-- Kotak ketersediaan menempel di batas bawah hero --}}
    <div class="container-lg relative z-10 -mt-16 sm:-mt-20">
        <x-ui.search-box />
        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-kabut-600">Pembayaran DP 25%, pelunasan paling lambat 3 hari sebelum kedatangan. <a href="{{ route('halaman', 'mekanisme-pembayaran') }}" class="underline underline-offset-4 hover:text-hutan-800">Cara pembayaran</a></p>
            {{-- Rating resort hanya bila sudah ada ulasan tayang (tidak ada angka bawaan) --}}
            <x-ulasan.singkat :summary="$resortRating" :href="route('ulasan.index')" />
        </div>
    </div>

    {{-- BANNER PROMO (hanya bila admin mengaktifkan dengan tanggal berlaku) --}}
    @if ($banner)
        <div class="container-lg mt-10">
            <div class="flex flex-col gap-3 border border-emas-400 bg-emas-50 px-6 py-4 text-hutan-950 sm:flex-row sm:items-center sm:justify-between">
                <p><span class="label-kecil text-emas-800">Penawaran</span> <span class="ml-2 font-semibold">{{ $banner->title }}</span> @if ($banner->body)<span class="text-kabut-700">· {{ $banner->body }}</span>@endif</p>
                @if ($banner->cta_url)
                    <a href="{{ $banner->cta_url }}" class="tautan-panah text-hutan-900">{{ $banner->cta_label ?: 'Lihat penawaran' }} <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                @endif
            </div>
        </div>
    @endif

    {{-- ============================== RENCANA KUNJUNGAN ============================== --}}
    <section class="section bg-kabut-50">
        <div class="container-lg">
            <div class="grid gap-6 lg:grid-cols-12 lg:items-end">
                <h2 class="font-display text-display-lg text-balance text-hutan-950 lg:col-span-6">Apa rencana kunjungan Anda?</h2>
                <p class="text-lg leading-relaxed text-kabut-700 lg:col-span-5 lg:col-start-8">Lingkung Gunung melayani keluarga yang ingin menginap, rombongan sekolah dan kantor, hingga komunitas yang mencari kegiatan alam. Pilih yang paling sesuai.</p>
            </div>
            <div class="mt-12 grid gap-px bg-kabut-300/70 md:grid-cols-3">
                @foreach ([
                    ['Menginap bersama keluarga', 'Kamar ber-AC dengan balkon, glamping A-frame, atau tenda lengkap dengan sarapan.', route('akomodasi.index'), 'Pilih akomodasi', 'images/resort/campervan-keluarga', 'Keluarga berkemah di samping mobil'],
                    ['Rombongan, sekolah & kantor', 'Paket fullboard untuk LDKS, outing, gathering, dan meeting hingga 200 orang.', route('gather'), 'Lihat paket rombongan', 'images/resort/rombongan-hkbp', 'Foto bersama rombongan di lapangan Lingkung Gunung'],
                    ['Aktivitas & petualangan', 'Trekking, rafting, paintball, offroad, flying fox, dan pengalaman budaya Sunda.', route('play'), 'Lihat aktivitas', 'images/resort/flying-fox', 'Flying fox di antara pohon pinus'],
                ] as $i => [$title, $text, $href, $cta, $img, $alt])
                    <x-ui.reveal :delay="$i * 90" class="group bg-kabut-50 pb-8 md:px-6 md:pb-0 md:first:pl-0 md:last:pr-0 lg:px-8">
                        <a href="{{ $href }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                            <x-ui.picture :src="$img" :alt="$alt" ratio="aspect-[4/5]" sizes="(min-width:768px) 30vw, 100vw" img-class="transition duration-[900ms] ease-alam group-hover:scale-[1.03]" />
                        </a>
                        <h3 class="mt-6 font-display text-2xl text-hutan-950"><a href="{{ $href }}" class="hover:text-hutan-700">{{ $title }}</a></h3>
                        <p class="mt-2 leading-relaxed text-kabut-700">{{ $text }}</p>
                        <a href="{{ $href }}" class="tautan-panah mt-5 mb-2 text-hutan-900">{{ $cta }} <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                    </x-ui.reveal>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== MENGINAP ============================== --}}
    <section class="section bg-white" id="menginap">
        <div class="container-lg">
            <div class="flex flex-wrap items-end justify-between gap-6 pb-10">
                <div class="max-w-2xl">
                    <p class="label-kecil text-emas-700">Menginap</p>
                    <h2 class="mt-4 font-display text-display-lg text-balance text-hutan-950">{{ $unitTypes->count() }} pilihan, dari kamar berbalkon hingga tenda di bawah pinus</h2>
                </div>
                <a href="{{ route('akomodasi.index') }}#bandingkan" class="tautan-panah text-hutan-900">Bandingkan semua harga <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
            </div>

            @foreach ($featured as $row)
                <x-ui.reveal>
                    <x-ui.unit-row :row="$row" :flip="$loop->odd" />
                </x-ui.reveal>
            @endforeach

            @if ($others->isNotEmpty())
                <div class="border-t border-kabut-300/70 pt-10">
                    <h3 class="label-kecil text-kabut-600">Pilihan lainnya</h3>
                    <ul class="mt-4 grid gap-x-10 sm:grid-cols-2">
                        @foreach ($others as $row)
                            <li class="border-b border-kabut-300/70">
                                <a href="{{ route('akomodasi.show', $row['model']) }}" class="group flex items-baseline justify-between gap-4 py-4">
                                    <span>
                                        <span class="font-display text-xl text-hutan-950 group-hover:text-hutan-700">{{ $row['model']->marketing_name }}</span>
                                        <span class="mt-0.5 block text-sm text-kabut-600">{{ $row['model']->isPricedPerPerson() ? 'Per orang' : 'Maks. '.$row['model']->capacity_pax.' orang' }}{{ $row['model']->is_bookable_online ? '' : ' · pesan via WhatsApp' }}</span>
                                    </span>
                                    <span class="shrink-0 text-right text-sm text-kabut-700">
                                        @if ($row['prices']['from'])<span class="font-semibold text-kabut-900">Rp{{ number_format($row['prices']['from'], 0, ',', '.') }}</span> / {{ $row['unit'] }}@endif
                                        <x-heroicon-m-arrow-right class="ml-2 inline size-4 transition group-hover:translate-x-1" aria-hidden="true" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    {{-- ============================== ACARA & ROMBONGAN ============================== --}}
    <section class="bg-hutan-950 text-kabut-100">
        <div class="grid lg:grid-cols-2">
            <div class="relative min-h-[22rem] lg:min-h-full">
                <x-ui.picture src="images/resort/aula" alt="Aula berdinding kaca untuk meeting dan acara" ratio="" class="!absolute inset-0" sizes="(min-width:1024px) 50vw, 100vw" />
            </div>
            <x-ui.reveal class="px-4 py-16 sm:px-10 lg:px-16 lg:py-24 xl:pr-[max(2rem,calc((100vw-80rem)/2+2rem))]">
                <p class="label-kecil text-emas-300">Acara &amp; Rombongan</p>
                <h2 class="mt-4 font-display text-display-lg text-balance text-white">LDKS, outing kantor, gathering, dan meeting dalam satu tempat</h2>
                <p class="mt-5 text-lg leading-relaxed text-kabut-300">Paket fullboard sudah termasuk akomodasi, makan 3x dari set menu A–E, aula, lapangan, dan kolam renang. Hitung sendiri estimasinya, lalu dapatkan proposal PDF.</p>
                <dl class="mt-8 grid grid-cols-2 border-t border-white/15">
                    @foreach (array_filter([
                        $packageFrom ? ['Paket mulai', 'Rp'.number_format($packageFrom, 0, ',', '.').' / orang / malam'] : null,
                        $packageMinPax ? ['Minimal peserta', $packageMinPax.' orang'] : null,
                        ['Aula & meeting room', 'Hingga 200 orang'],
                        ['Pilihan paket', $packages->flatMap->variants->count().' varian fullboard'],
                    ]) as [$dt, $dd])
                        <div class="border-b border-white/15 py-4 pr-4">
                            <dt class="label-kecil text-kabut-400">{{ $dt }}</dt>
                            <dd class="mt-1 text-white">{{ $dd }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-9 flex flex-wrap items-center gap-x-8 gap-y-4">
                    <x-ui.button variant="accent" :href="route('gather').'#paket'">Lihat paket rombongan</x-ui.button>
                    <a href="{{ route('rancang-paket') }}" class="tautan-panah text-white">Hitung estimasi biaya <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                </div>
            </x-ui.reveal>
        </div>
    </section>

    {{-- ============================== AKTIVITAS ============================== --}}
    @if ($highlightActivities->isNotEmpty())
        <section class="section bg-kabut-50">
            <div class="container-lg">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div class="max-w-2xl">
                        <p class="label-kecil text-emas-700">Aktivitas</p>
                        <h2 class="mt-4 font-display text-display-lg text-balance text-hutan-950">Petualangan di sungai, hutan, dan jalur tanah</h2>
                    </div>
                    <a href="{{ route('play') }}#aktivitas" class="tautan-panah text-hutan-900">Semua aktivitas &amp; harga <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                </div>
                <ul class="scrollbar-tipis -mx-4 mt-12 flex snap-x snap-mandatory gap-6 overflow-x-auto px-4 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-4">
                    @foreach ($highlightActivities as $activity)
                        <li class="group w-[75vw] shrink-0 snap-start sm:w-auto">
                            <a href="{{ route('play') }}#adventure" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                                <x-ui.picture :src="'images/resort/'.$activityPhotos[strtolower($activity->name)]" :alt="$activity->name" ratio="aspect-[3/4]" sizes="(min-width:1024px) 22vw, (min-width:640px) 45vw, 75vw" img-class="transition duration-[900ms] ease-alam group-hover:scale-[1.03]" />
                            </a>
                            <h3 class="mt-4 font-display text-xl text-hutan-950">{{ $activity->name }}</h3>
                            <p class="mt-1 text-sm text-kabut-700"><span class="font-semibold text-kabut-900">Rp{{ number_format($activity->price, 0, ',', '.') }}</span> / {{ $activity->price_unit }} · min. {{ $activity->min_pax }} orang</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ============================== KULINER ============================== --}}
    <section class="section bg-white">
        <div class="container-lg grid gap-10 lg:grid-cols-12 lg:items-center">
            <x-ui.reveal class="lg:col-span-5">
                <p class="label-kecil text-emas-700">Kuliner</p>
                <h2 class="mt-4 font-display text-display-lg text-balance text-hutan-950">Swaka Cafe &amp; Resto, menghadap lembah</h2>
                <p class="mt-5 text-lg leading-relaxed text-kabut-700">Kopi dan menu Nusantara untuk tamu menginap maupun pengunjung. Rombongan memilih dari set menu A–E untuk sarapan, makan siang, makan malam, dan coffee break.</p>
                <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4">
                    <a href="{{ route('taste') }}" class="tautan-panah text-hutan-900">Tentang Swaka <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                    <a href="{{ route('taste') }}#menu-group" class="tautan-panah text-hutan-900">Set menu rombongan <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                </div>
            </x-ui.reveal>
            <div class="grid grid-cols-5 gap-4 lg:col-span-7">
                <x-ui.reveal class="col-span-3">
                    <x-ui.picture src="images/resort/kafe-deck" alt="Menikmati kopi di deck pandang" ratio="aspect-[3/4]" sizes="(min-width:1024px) 34vw, 60vw" />
                </x-ui.reveal>
                <x-ui.reveal :delay="120" class="col-span-2 self-end">
                    <x-ui.picture src="images/resort/makan-bersama" alt="Tamu bersantap bersama di area resort" ratio="aspect-[3/4]" sizes="(min-width:1024px) 22vw, 40vw" />
                </x-ui.reveal>
            </div>
        </div>
    </section>

    {{-- ============================== LOKASI & KONTAK ============================== --}}
    <section class="section border-t border-kabut-300/70 bg-kabut-50" id="lokasi">
        <div class="container-lg grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p class="label-kecil text-emas-700">Lokasi &amp; Kontak</p>
                <h2 class="mt-4 font-display text-display-lg text-balance text-hutan-950">Lewat Tol Bocimi, tanpa macet jalur Puncak</h2>
                <address class="mt-6 text-lg not-italic leading-relaxed text-kabut-800">{{ Setting::get('address') }}</address>
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button variant="primary" :href="$mapsUrl" target="_blank" rel="noopener" icon="heroicon-o-map-pin">Google Maps</x-ui.button>
                    <x-ui.button variant="outline" :href="$wazeUrl" target="_blank" rel="noopener">Waze</x-ui.button>
                </div>
            </div>
            <div class="lg:col-span-6 lg:col-start-7">
                <dl class="divide-y divide-kabut-300/70 border-y border-kabut-300/70">
                    <div class="grid gap-1 py-5 sm:grid-cols-[10rem_1fr]">
                        <dt class="label-kecil pt-1 text-kabut-600">Rute 1</dt>
                        <dd class="text-kabut-900">Tol Jagorawi–Bocimi, keluar Gerbang Tol Caringin → Pasar Cikereteg → Pancawati → Lingkung Gunung</dd>
                    </div>
                    <div class="grid gap-1 py-5 sm:grid-cols-[10rem_1fr]">
                        <dt class="label-kecil pt-1 text-kabut-600">Rute 2</dt>
                        <dd class="text-kabut-900">Tol Jagorawi, keluar Gerbang Tol Ciawi → Pasar Cikereteg → Pancawati → Lingkung Gunung</dd>
                    </div>
                    <div class="grid gap-1 py-5 sm:grid-cols-[10rem_1fr]">
                        <dt class="label-kecil pt-1 text-kabut-600">Kendaraan</dt>
                        <dd class="text-kabut-900">Mobil pribadi, bus kecil, bus 3/4, hingga truk 3/4</dd>
                    </div>
                    <div class="grid gap-1 py-5 sm:grid-cols-[10rem_1fr]">
                        <dt class="label-kecil pt-1 text-kabut-600">Hubungi</dt>
                        <dd class="flex flex-wrap gap-x-6 gap-y-2 text-kabut-900">
                            <a href="{{ WhatsApp::link('Halo Lingkung Gunung, saya ingin bertanya.') }}" target="_blank" rel="noopener" class="underline underline-offset-4 hover:text-hutan-700">WhatsApp {{ WhatsApp::display() }}</a>
                            @if ($phone = Setting::get('contact_phone'))<a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="underline underline-offset-4 hover:text-hutan-700">Telepon {{ $phone }}</a>@endif
                            @if ($email = ResortContact::email())<a href="mailto:{{ $email }}" class="underline underline-offset-4 hover:text-hutan-700">{{ $email }}</a>@endif
                        </dd>
                    </div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                    <a href="{{ route('rute') }}" class="tautan-panah text-hutan-900">Panduan rute lengkap <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                    <a href="{{ route('kontak') }}#faq" class="tautan-panah text-hutan-900">Pertanyaan umum <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
