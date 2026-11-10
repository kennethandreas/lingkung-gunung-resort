{{-- Label kecil. variant: netral | hutan | emas | tanah | sukses | peringatan | bahaya | info | kaca --}}
@props(['variant' => 'netral', 'icon' => null])

@php
    $variants = [
        'netral' => 'bg-kabut-100 text-kabut-700 ring-kabut-200',
        'hutan' => 'bg-hutan-50 text-hutan-800 ring-hutan-200',
        'emas' => 'bg-emas-50 text-emas-800 ring-emas-200',
        'tanah' => 'bg-tanah-50 text-tanah-700 ring-tanah-200',
        'sukses' => 'bg-sukses-50 text-sukses-800 ring-sukses-600/20',
        'peringatan' => 'bg-peringatan-50 text-peringatan-800 ring-peringatan-600/20',
        'bahaya' => 'bg-bahaya-50 text-bahaya-800 ring-bahaya-600/20',
        'info' => 'bg-info-50 text-info-800 ring-info-600/20',
        'kaca' => 'bg-white/15 text-white ring-white/30 backdrop-blur-md',
    ];
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-none px-2 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.14em] ring-1 ring-inset '.($variants[$variant] ?? $variants['netral'])) }}>
    @if ($icon)
        <x-dynamic-component :component="$icon" class="size-3.5" aria-hidden="true" />
    @endif
    {{ $slot }}
</span>
