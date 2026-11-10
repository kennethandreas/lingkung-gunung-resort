{{--
    Baris akomodasi editorial (pengganti grid kartu): foto besar + fakta yang bisa dibandingkan + dua aksi.
    $row = ['model' => UnitType, 'prices' => [...], 'cover' => Media|null, 'unit' => 'kamar/malam']
    flip = foto di kanan (selang-seling).
--}}
@props(['row', 'flip' => false, 'headingLevel' => 'h3'])

@php
    $type = $row['model'];
    $features = collect($type->includes ?? [])->reject(fn ($i) => str_starts_with($i, 'Breakfast:') || in_array($i, ['Room', 'Tenda'], true))->take(6);
    $illustration = (bool) $row['cover']?->getCustomProperty('placeholder');
    $tag = in_array($headingLevel, ['h2', 'h3'], true) ? $headingLevel : 'h3';
@endphp

<article {{ $attributes->class(['grid gap-6 border-t border-kabut-300/70 py-10 md:grid-cols-12 md:gap-10 lg:py-14']) }}>
    <a href="{{ route('akomodasi.show', $type) }}" @class(['group relative block overflow-hidden md:col-span-7', 'md:order-2' => $flip]) tabindex="-1" aria-hidden="true">
        <x-ui.picture :media="$row['cover']" :alt="$type->marketing_name" ratio="aspect-[3/2]" sizes="(min-width:768px) 58vw, 100vw" img-class="transition duration-[900ms] ease-alam group-hover:scale-[1.03]" />
        @if ($illustration)
            <span class="absolute bottom-0 left-0 bg-kabut-50/90 px-2 py-1 text-[0.65rem] text-kabut-700">Ilustrasi brosur</span>
        @endif
    </a>
    <div @class(['flex flex-col md:col-span-5', 'md:order-1' => $flip])>
        <p class="label-kecil text-emas-700">{{ $type->isPricedPerPerson() ? 'Area per orang' : ($type->pricing_unit->value === 'tent' ? 'Tenda' : 'Kamar') }}</p>
        <{{ $tag }} class="mt-3 font-display text-3xl text-hutan-950 lg:text-4xl">
            <a href="{{ route('akomodasi.show', $type) }}" class="hover:text-hutan-700">{{ $type->marketing_name }}</a>
        </{{ $tag }}>
        @if ($type->description)
            <p class="mt-4 leading-relaxed text-kabut-700">{{ \Illuminate\Support\Str::limit($type->description, 170) }}</p>
        @endif

        <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 border-y border-kabut-300/70 py-5 text-sm">
            <div>
                <dt class="label-kecil text-kabut-600">Kapasitas</dt>
                <dd class="mt-1 text-kabut-900">{{ $type->isPricedPerPerson() ? 'Per orang' : 'Maks. '.$type->capacity_pax.' orang' }}</dd>
            </div>
            <div>
                <dt class="label-kecil text-kabut-600">Tempat tidur</dt>
                <dd class="mt-1 text-kabut-900">{{ $type->bed_configuration ?: '-' }}</dd>
            </div>
            <div>
                <dt class="label-kecil text-kabut-600">Sarapan</dt>
                <dd class="mt-1 text-kabut-900">{{ $type->breakfast_pax ? 'Termasuk '.$type->breakfast_pax.' orang' : 'Tidak termasuk' }}</dd>
            </div>
            <div>
                <dt class="label-kecil text-kabut-600">Harga mulai</dt>
                <dd class="mt-1 font-semibold text-kabut-900">{{ $row['prices']['from'] ? 'Rp'.number_format($row['prices']['from'], 0, ',', '.') : '-' }} <span class="font-normal text-kabut-600">/ {{ $row['unit'] }}</span></dd>
            </div>
        </dl>

        @if ($features->isNotEmpty())
            <p class="mt-4 text-sm text-kabut-600">{{ $features->implode(' · ') }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-6 gap-y-3 pt-6">
            @if ($type->is_bookable_online)
                <x-ui.button variant="primary" :href="route('pesan', ['unit' => $type->slug])">Pesan</x-ui.button>
            @else
                <x-ui.button variant="whatsapp" :href="\App\Support\WhatsApp::link('Halo, saya ingin memesan '.$type->marketing_name.'.')" target="_blank" rel="noopener">Pesan via WhatsApp</x-ui.button>
            @endif
            <a href="{{ route('akomodasi.show', $type) }}" class="tautan-panah text-hutan-900">Lihat detail <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
        </div>
    </div>
</article>
