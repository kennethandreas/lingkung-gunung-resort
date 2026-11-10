@php
    // Kelompok yang mudah dipahami pengunjung baru (berdasarkan satuan harga di admin)
    $groups = [
        'kamar' => ['Kamar & glamping', 'Kamar ber-AC dengan balkon, pondokan keluarga, dan glamping A-frame. Sarapan 2 orang termasuk.', $unitTypes->filter(fn ($r) => $r['model']->pricing_unit->value === 'room')],
        'tenda' => ['Tenda', 'Tenda dome dan tenda keluarga yang sudah disiapkan lengkap dengan kasur, selimut, dan sarapan.', $unitTypes->filter(fn ($r) => $r['model']->pricing_unit->value === 'tent')],
        'camping' => ['Area camping & campervan', 'Bawa tenda atau kendaraan sendiri. Tersedia parkir, wifi, listrik, dan akses kolam renang.', $unitTypes->filter(fn ($r) => in_array($r['model']->pricing_unit->value, ['person', 'plot'], true))],
    ];
    $groups = array_filter($groups, fn ($g) => $g[2]->isNotEmpty());
@endphp

<x-layouts.public title="Menginap" description="Pilihan menginap di Lingkung Gunung Bogor: kamar Pangrango berbalkon, Pondokan Bale Gunung, Glamping Imah Lembah, Kemping Ceria, Family Suite Camp, camp site, dan kavling campervan. Lengkap dengan kapasitas dan harga." transparent-header>
    <x-ui.page-hero eyebrow="Menginap" title="Kamar, glamping, dan tenda di ketinggian 850 MDPL" lead="Semua pilihan sudah termasuk wifi dan akses kolam renang. Bandingkan kapasitas dan harga di bawah, lalu pesan langsung." image="images/resort/glamping-lembah" alt="Glamping A-frame menghadap hutan dan lembah" />

    {{-- Lompat cepat ke kelompok --}}
    <nav class="sticky top-18 z-30 border-b border-kabut-300/70 bg-kabut-50/95 backdrop-blur" aria-label="Kelompok akomodasi">
        <ul class="container-lg scrollbar-tipis flex gap-8 overflow-x-auto">
            @foreach ($groups as $key => [$label])
                <li><a href="#{{ $key }}" class="label-kecil block whitespace-nowrap border-b-2 border-transparent py-4 text-kabut-700 transition-colors hover:border-hutan-800 hover:text-hutan-900">{{ $label }}</a></li>
            @endforeach
            <li><a href="#bandingkan" class="label-kecil block whitespace-nowrap border-b-2 border-transparent py-4 text-kabut-700 transition-colors hover:border-hutan-800 hover:text-hutan-900">Bandingkan harga</a></li>
        </ul>
    </nav>

    @foreach ($groups as $key => [$label, $intro, $rows])
        <section @class(['scroll-mt-32 pb-10 pt-16 sm:pt-20', 'bg-white' => $loop->even, 'bg-kabut-50' => $loop->odd]) id="{{ $key }}">
            <div class="container-lg">
                <div class="grid gap-4 pb-6 lg:grid-cols-12 lg:items-end">
                    <h2 class="font-display text-display-lg text-hutan-950 lg:col-span-6">{{ $label }}</h2>
                    <p class="text-lg leading-relaxed text-kabut-700 lg:col-span-5 lg:col-start-8">{{ $intro }}</p>
                </div>
                @foreach ($rows as $row)
                    <x-ui.reveal>
                        <x-ui.unit-row :row="$row" :flip="$loop->even" />
                    </x-ui.reveal>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Perbandingan: data sama persis dengan harga di wizard (tabel unit_types + rates) --}}
    <section class="section scroll-mt-32 border-t border-kabut-300/70 bg-white" id="bandingkan">
        <div class="container-lg">
            <x-ui.section-heading eyebrow="Bandingkan" title="Semua pilihan dalam satu tabel" :lead="'Harga publish per malam. Tarif weekend berlaku untuk '.$weekendLabel.' serta hari libur yang ditandai resort. Harga akhir dihitung otomatis saat Anda memilih tanggal.'" />
            <div class="mt-10 overflow-x-auto border-y border-kabut-300">
                <table class="w-full min-w-[46rem] border-collapse text-left text-sm">
                    <caption class="sr-only">Perbandingan kapasitas, tempat tidur, sarapan, dan harga akomodasi</caption>
                    <thead class="text-kabut-700">
                        <tr class="border-b border-kabut-300">
                            <th scope="col" class="label-kecil px-4 py-4">Akomodasi</th>
                            <th scope="col" class="label-kecil px-4 py-4">Kapasitas</th>
                            <th scope="col" class="label-kecil px-4 py-4">Tempat tidur</th>
                            <th scope="col" class="label-kecil px-4 py-4">Sarapan</th>
                            <th scope="col" class="label-kecil px-4 py-4 text-right">Weekday</th>
                            <th scope="col" class="label-kecil px-4 py-4 text-right">Weekend</th>
                            <th scope="col" class="px-4 py-4"><span class="sr-only">Pesan</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-kabut-200">
                        @foreach ($unitTypes as $row)
                            @php
                                $type = $row['model'];
                            @endphp
                            <tr class="transition-colors hover:bg-kabut-50">
                                <th scope="row" class="px-4 py-4 font-normal">
                                    <a href="{{ route('akomodasi.show', $type) }}" class="font-display text-lg text-hutan-950 hover:text-hutan-700">{{ $type->marketing_name }}</a>
                                    <span class="block text-xs text-kabut-600">{{ $row['unit'] }}</span>
                                </th>
                                <td class="px-4 py-4">{{ $type->isPricedPerPerson() ? 'Per orang' : 'Maks. '.$type->capacity_pax.' orang' }}</td>
                                <td class="px-4 py-4">{{ $type->bed_configuration ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $type->breakfast_pax ? $type->breakfast_pax.' orang' : 'Tidak termasuk' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ $row['prices']['weekday'] ? 'Rp'.number_format($row['prices']['weekday'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ $row['prices']['weekend'] ? 'Rp'.number_format($row['prices']['weekend'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4 text-right">
                                    @if ($type->is_bookable_online)
                                        <a href="{{ route('pesan', ['unit' => $type->slug]) }}" class="tautan-panah text-hutan-900">Pesan</a>
                                    @else
                                        <span class="label-kecil text-emas-700">Via WhatsApp</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.public>
