{{-- Kartu program/aktivitas. Dengan foto = kartu besar; tanpa foto = kartu ringkas berikon. --}}
@props(['program', 'price' => null, 'priceUnit' => 'pax', 'meta' => null])

@php
    $cover = $program->getFirstMedia('cover');
@endphp

<article {{ $attributes->class('group flex h-full flex-col overflow-hidden rounded-kartu bg-white shadow-lembut ring-1 ring-kabut-200/70 transition duration-500 ease-alam') }}>
    @if ($cover)
        <x-ui.picture :media="$cover" :alt="$program->name" ratio="aspect-[16/10]" sizes="(min-width:1024px) 33vw, (min-width:640px) 50vw, 100vw" img-class="transition duration-700 ease-alam group-hover:scale-105" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <h3 class="font-display text-xl font-semibold text-hutan-900">{{ $program->name }}</h3>
        @if ($program->summary)
            <p class="mt-1 text-sm font-medium text-emas-700">{{ $program->summary }}</p>
        @endif
        @if ($program->description)
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-kabut-600">{{ $program->description }}</p>
        @endif
        @if ($price || $meta)
            <div class="mt-auto flex flex-wrap items-end justify-between gap-2 pt-4">
                @if ($price)
                    <p class="text-sm text-kabut-600"><span class="text-base font-bold text-hutan-900">Rp{{ number_format($price, 0, ',', '.') }}</span> / {{ $priceUnit }}</p>
                @endif
                @if ($meta)
                    <x-ui.badge variant="netral">{{ $meta }}</x-ui.badge>
                @endif
            </div>
        @endif
    </div>
</article>
