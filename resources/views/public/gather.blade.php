@use('App\Support\WhatsApp')

@php
    $segments = $groups->except('Team Building Games');
    $games = $groups->get('Team Building Games', collect());
@endphp

<x-layouts.public title="Gather: Rombongan & MICE" description="Paket rombongan fullboard (LDKS, Camp, Room Glamping), meeting room 200 orang, team building, family & community gathering di Lingkung Gunung, Bogor." transparent-header>
    <x-ui.page-hero eyebrow="Acara & Rombongan" title="Paket rombongan, outbound, dan meeting" lead="Paket fullboard untuk LDKS sekolah, outing kantor, gathering, hingga jambore komunitas. Akomodasi, makan 3x, aula, dan lapangan dalam satu harga per orang." image="images/resort/senam-rombongan" alt="Rombongan berkegiatan di lapangan berlatar pohon pinus">
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button variant="accent" size="lg" :href="route('rancang-paket')" icon-right="heroicon-m-arrow-right">Rancang paket rombongan</x-ui.button>
            <x-ui.button variant="light" size="lg" :href="WhatsApp::link('Halo, saya ingin meminta penawaran paket rombongan.')" target="_blank" rel="noopener">Minta penawaran</x-ui.button>
        </div>
    </x-ui.page-hero>

    {{-- SEGMEN --}}
    <section class="section bg-kabut-50">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="Untuk siapa" title="Perusahaan, keluarga, dan komunitas" align="center" />
            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                @foreach ($segments as $segment => $items)
                    <x-ui.reveal :delay="$loop->index * 120" class="rounded-kartu bg-white p-7 shadow-lembut ring-1 ring-kabut-200/70">
                        <h3 class="font-display text-display-md font-semibold text-emas-700">{{ ['Corporate' => 'Perusahaan', 'Family' => 'Keluarga', 'Community' => 'Komunitas'][$segment] ?? $segment }}</h3>
                        <ul class="mt-5 divide-y divide-kabut-200">
                            @foreach ($items as $item)
                                <li class="py-3">
                                    <p class="font-semibold text-hutan-900">{{ $item->name }}</p>
                                    @if ($item->description)
                                        <p class="mt-1 text-sm text-kabut-600">{{ $item->description }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.reveal>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PAKET FULLBOARD --}}
    <section class="section bg-white" id="paket">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="Pilihan paket group" :script="true" title="Paket fullboard per orang" lead="Harga per pax sudah termasuk akomodasi, makan 3x sesuai set menu, wifi, kolam renang, aula, dan lapangan. Pilihan menu bisa dilihat di halaman Taste." />
            <div class="mt-10 space-y-8">
                @foreach ($packages as $package)
                    <div class="overflow-hidden rounded-kartu ring-1 ring-kabut-200">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 bg-hutan-900 px-5 py-4 text-white sm:px-6">
                            <div>
                                <h3 class="font-display text-2xl font-semibold">{{ $package->name }}</h3>
                                <x-ulasan.singkat :summary="$packageRatings[$package->id] ?? null" :href="route('ulasan.target', ['paket', $package->slug])" dark class="mt-1" />
                            </div>
                            <p class="text-sm text-hutan-100">{{ $package->accommodation }}</p>
                        </div>
                        <div class="grid divide-y divide-kabut-200 md:grid-cols-3 md:divide-x md:divide-y-0">
                            @foreach ($package->variants as $variant)
                                <div class="flex flex-col p-5 sm:p-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-display text-xl font-semibold text-hutan-900">Varian {{ $variant->code }}</p>
                                        @if ($variant->weekday_only)
                                            <x-ui.badge variant="peringatan">Hanya weekday</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-kabut-600">Min. {{ $variant->min_pax }} peserta @if ($variant->menuSet)· {{ $variant->menuSet->name }}@endif</p>
                                    <dl class="mt-4 grid grid-cols-2 gap-3">
                                        <div class="rounded-sm bg-kabut-50 p-3">
                                            <dt class="text-xs text-kabut-600">Weekday</dt>
                                            <dd class="font-bold text-hutan-900">Rp{{ number_format($variant->price_weekday, 0, ',', '.') }}</dd>
                                        </div>
                                        <div class="rounded-sm bg-kabut-50 p-3">
                                            <dt class="text-xs text-kabut-600">Weekend</dt>
                                            <dd class="font-bold text-hutan-900">{{ $variant->weekday_only ? '-' : 'Rp'.number_format($variant->price_weekend, 0, ',', '.') }}</dd>
                                        </div>
                                    </dl>
                                    <ul class="mt-4 flex flex-wrap gap-1.5">
                                        @foreach ($variant->includes ?? [] as $include)
                                            <li class="rounded-none bg-hutan-50 px-2.5 py-1 text-xs font-medium text-hutan-800">{{ $include }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('rancang-paket', ['paket' => $variant->id]) }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-hutan-700 hover:text-hutan-900">Rancang dengan paket ini <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-col gap-4 rounded-kartu bg-emas-50 p-6 ring-1 ring-emas-200 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-display text-xl font-semibold text-hutan-900">Susun paket sendiri dalam 3 menit</p>
                    <p class="mt-1 text-sm text-kabut-700">Pilih paket, menu, aktivitas, dan item tambahan. Estimasi langsung muncul dan proposal PDF dikirim ke email Anda.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button variant="primary" size="lg" :href="route('rancang-paket')" icon="heroicon-m-squares-plus">Rancang paket</x-ui.button>
                    <x-ui.button variant="outline" size="lg" :href="route('rancang-paket', ['template' => '2-days-outbound'])">Mulai dari contoh 2 Days Outbound</x-ui.button>
                </div>
            </div>
        </div>
    </section>

    {{-- MEETING ROOM --}}
    @if ($halls->isNotEmpty())
        <section class="section bg-kabut-50" id="meeting">
            <div class="container-lg grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:items-start">
                <div>
                    <x-ui.section-heading eyebrow="MICE" title="Meeting room hingga 200 orang" lead="Ruang pertemuan nyaman di tengah alam dengan LCD projector, sound system, wifi, dan whiteboard. Catering & coffee break tersedia sesuai kebutuhan." />
                    <x-ui.picture src="images/resort/aula" alt="Aula berdinding kaca untuk meeting dan acara" ratio="aspect-[4/3]" class="mt-8 rounded-foto shadow-angkat" sizes="(min-width:1024px) 40vw, 100vw" />
                </div>
                <div class="overflow-x-auto rounded-kartu bg-white shadow-lembut ring-1 ring-kabut-200">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Daftar aula dan harga sewa</caption>
                        <thead class="bg-kabut-100 text-kabut-700">
                            <tr><th class="px-4 py-3" scope="col">Ruang</th><th class="px-4 py-3" scope="col">Layout & kapasitas</th><th class="px-4 py-3 text-right" scope="col">Harga / sett</th></tr>
                        </thead>
                        <tbody class="divide-y divide-kabut-200">
                            @foreach ($halls as $hall)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-hutan-900">{{ $hall->name }}</td>
                                    <td class="px-4 py-3 text-kabut-700">{{ $hall->layout }} · {{ $hall->capacity ? 'maks. '.$hall->capacity.' orang' : '-' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">Rp{{ number_format($hall->price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- TEAM BUILDING GAMES --}}
    @if ($games->isNotEmpty())
        <section class="section bg-white">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Contoh" :script="true" title="Team building games" lead="Kerja sama, komunikasi, dan keseruan dalam setiap tantangan." />
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($games as $i => $game)
                        <x-ui.reveal :delay="($i % 4) * 70" class="rounded-kartu bg-kabut-50 p-5 ring-1 ring-kabut-200">
                            <p class="font-display text-lg font-semibold text-hutan-900">{{ $game->name }}</p>
                            <p class="mt-1 text-sm font-medium text-emas-700">{{ $game->summary }}</p>
                            <p class="mt-2 text-sm text-kabut-600">{{ $game->description }}</p>
                        </x-ui.reveal>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ITINERARY --}}
    @if ($itinerary)
        <section class="section bg-tanah-50">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Inspirasi" title="{{ $itinerary->title }}" />
                <div class="prose-lingkung mt-8 overflow-x-auto rounded-kartu bg-white p-5 shadow-lembut ring-1 ring-tanah-200 sm:p-8">
                    {!! \Illuminate\Support\Str::sanitizeHtml($itinerary->body) !!}
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
