{{-- Kartu akomodasi: foto dominan, nama, info singkat, harga mulai, CTA. save = UnitType untuk tombol simpan (opsional). --}}
@props([
    'name',
    'tagline' => null,
    'image' => null,
    'media' => null,
    'href' => '#',
    'price' => null,
    'priceUnit' => 'malam',
    'capacity' => null,
    'features' => [],
    'badge' => null,
    'save' => null,
])

<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-kartu bg-white shadow-lembut ring-1 ring-kabut-200/70 transition duration-500 ease-alam') }}>
    <x-ui.picture :src="$image" :media="$media" :alt="$name" ratio="aspect-[4/3]" img-class="transition duration-700 ease-alam group-hover:scale-105">
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-hutan-950/60 to-transparent"></div>
        @if ($badge)
            <x-ui.badge variant="emas" class="absolute right-4 top-4 shadow-lembut">{{ $badge }}</x-ui.badge>
        @endif
        @if ($save)
            <x-ui.save-button :unit-type="$save" class="!absolute left-4 top-4" />
        @endif
        @if ($capacity)
            <span class="absolute bottom-4 left-4 inline-flex items-center gap-1.5 text-sm font-medium text-white">
                <x-heroicon-o-user-group class="size-4" aria-hidden="true" />
                Maks. {{ $capacity }} orang
            </span>
        @endif
    </x-ui.picture>

    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <h3 class="font-display text-2xl font-semibold text-hutan-900">
            <a href="{{ $href }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $name }}</a>
        </h3>
        @if ($tagline)
            <p class="mt-1 text-sm text-kabut-600">{{ $tagline }}</p>
        @endif

        @if ($features)
            <ul class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($features as $feature)
                    <li class="rounded-none bg-kabut-100 px-2.5 py-1 text-xs font-medium text-kabut-700">{{ $feature }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
            @if ($price)
                <p class="leading-tight">
                    <span class="block text-xs text-kabut-600">Mulai</span>
                    <span class="text-lg font-bold text-hutan-900">Rp{{ number_format($price, 0, ',', '.') }}</span>
                    <span class="text-sm text-kabut-600">/ {{ $priceUnit }}</span>
                </p>
            @endif
            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-none bg-hutan-50 text-hutan-800 transition duration-300 group-hover:bg-hutan-800 group-hover:text-white" aria-hidden="true">
                <x-heroicon-m-arrow-up-right class="size-5" />
            </span>
        </div>
    </div>
</article>
