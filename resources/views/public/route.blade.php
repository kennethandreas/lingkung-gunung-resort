@use('App\Models\Setting')

@php
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode(Setting::get('google_maps_query'));
    $wazeUrl = 'https://waze.com/ul?q='.urlencode(Setting::get('waze_query'));
@endphp

<x-layouts.public title="Rute & Akses" description="Rute ke Lingkung Gunung Bogor via Tol Jagorawi–Bocimi (Gerbang Tol Caringin) atau Tol Jagorawi (Gerbang Tol Ciawi). Bisa dilalui mobil pribadi hingga bus 3/4." transparent-header>
    <x-ui.page-hero eyebrow="Lokasi & Akses" title="Rute ke Lingkung Gunung" lead="Jalan aspal, beton, dan sirtu padat. Aman dilalui siang dan malam di segala kondisi cuaca." image="images/resort/campsite-mobil" alt="Kendaraan tamu di area camp site yang luas">
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button variant="accent" size="lg" :href="$mapsUrl" target="_blank" rel="noopener" icon="heroicon-o-map-pin">Buka Google Maps</x-ui.button>
            <x-ui.button variant="light" size="lg" :href="$wazeUrl" target="_blank" rel="noopener">Buka Waze</x-ui.button>
        </div>
    </x-ui.page-hero>

    <section class="section bg-kabut-50">
        <div class="container-lg grid gap-6 lg:grid-cols-2">
            @foreach ([
                ['Rute 1 · Jagorawi–Bocimi', 'Alternatif tercepat dari Jakarta.', ['Tol Jagorawi', 'Tol Bocimi', 'Gerbang Tol Caringin', 'Pasar Cikereteg', 'Pancawati', 'Lingkung Gunung']],
                ['Rute 2 · Jagorawi', 'Lewat Gerbang Tol Ciawi.', ['Tol Jagorawi', 'Gerbang Tol Ciawi', 'Pasar Cikereteg', 'Pancawati', 'Lingkung Gunung']],
            ] as $i => [$title, $note, $stops])
                <x-ui.reveal :delay="$i * 120" class="rounded-kartu bg-white p-7 shadow-lembut ring-1 ring-kabut-200/70">
                    <h2 class="font-display text-2xl font-semibold text-hutan-900">{{ $title }}</h2>
                    <p class="mt-1 text-sm text-kabut-600">{{ $note }}</p>
                    <ol class="relative mt-6 space-y-4 border-l-2 border-dashed border-emas-300 pl-6">
                        @foreach ($stops as $stop)
                            <li class="relative">
                                <span @class(['absolute -left-[2.05rem] top-0.5 flex size-5 items-center justify-center rounded-none ring-4 ring-white', 'bg-hutan-800' => $loop->last, 'bg-emas-400' => ! $loop->last])></span>
                                <span @class(['font-semibold text-hutan-900' => $loop->last, 'text-kabut-800' => ! $loop->last])>{{ $stop }}</span>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.reveal>
            @endforeach
        </div>
    </section>

    <section class="section bg-white">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="Easy access" title="Dapat dilalui berbagai kendaraan" lead="Cocok untuk rombongan sekolah, perusahaan, hingga kendaraan dinas." />
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['Mobil pribadi', 'Jalur nyaman & aman untuk keluarga.'], ['Bus 3/4', 'Cocok untuk rombongan sekolah/perusahaan.'], ['Bus pariwisata 3/4', 'Akses mudah untuk midibus & rombongan menengah.'], ['Truk / tronton 3/4', 'Untuk logistik, perlengkapan kegiatan & kendaraan dinas.']] as [$vehicle, $text])
                    <div class="rounded-kartu bg-kabut-50 p-5 ring-1 ring-kabut-200">
                        <x-heroicon-o-truck class="size-7 text-emas-600" aria-hidden="true" />
                        <p class="mt-3 font-semibold text-hutan-900">{{ $vehicle }}</p>
                        <p class="mt-1 text-sm text-kabut-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <x-ui.alert variant="peringatan" class="mt-8">Akses jalan relatif baik: sebagian besar beraspal atau beton, sebagian kecil jalan sirtu yang sudah dipadatkan.</x-ui.alert>
        </div>
    </section>

    <section class="bg-kabut-100">
        <div class="container-lg py-12">
            <h2 class="sr-only">Peta lokasi</h2>
            <div class="overflow-hidden rounded-kartu shadow-lembut ring-1 ring-kabut-200">
                <iframe title="Peta lokasi Lingkung Gunung" src="https://maps.google.com/maps?q={{ urlencode(Setting::get('google_maps_query')) }}&z=13&output=embed" class="h-[24rem] w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <address class="mt-4 text-sm not-italic text-kabut-700">{{ Setting::get('address') }}</address>
        </div>
    </section>
</x-layouts.public>
