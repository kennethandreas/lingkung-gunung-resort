{{--
    Tombol. Pakai href untuk tautan (<a>), tanpa href menjadi <button>.
    variant: primary | accent | outline | ghost | light | whatsapp
    size: sm | md | lg
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'type' => 'button',
])

@php
    // Tombol arsitektural: persegi, label kapital berjarak, tanpa bayangan/efek mengecil (docs/AUDIT-UX.md)
    $base = 'group/btn inline-flex items-center justify-center gap-2.5 rounded-none font-semibold uppercase tracking-[0.14em] whitespace-nowrap select-none '
        .'transition-colors duration-300 ease-(--ease-alam) disabled:pointer-events-none disabled:opacity-50 '
        .'focus-visible:outline-2 focus-visible:outline-offset-2';

    $variants = [
        'primary' => 'bg-hutan-900 text-kabut-50 hover:bg-hutan-700 focus-visible:outline-hutan-700',
        'accent' => 'bg-kabut-50 text-hutan-950 hover:bg-white focus-visible:outline-white',
        'outline' => 'border border-hutan-900 bg-transparent text-hutan-900 hover:bg-hutan-900 hover:text-kabut-50 focus-visible:outline-hutan-700',
        'ghost' => 'bg-transparent text-hutan-900 hover:bg-kabut-100 focus-visible:outline-hutan-700',
        'light' => 'border border-white/70 bg-transparent text-white hover:bg-white hover:text-hutan-950 focus-visible:outline-white',
        'whatsapp' => 'bg-[#1f7a4a] text-white hover:bg-[#186a3f] focus-visible:outline-[#1f7a4a]',
    ];

    $sizes = [
        'sm' => 'h-9 px-4 text-[0.7rem]',
        'md' => 'h-11 px-6 text-[0.72rem]',
        'lg' => 'h-13 px-8 text-[0.75rem]',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
    $iconClass = $size === 'lg' ? 'size-5' : 'size-[1.125rem]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($variant === 'whatsapp' && ! $icon)
            <x-ui.icon-whatsapp class="{{ $iconClass }}" />
        @elseif ($icon)
            <x-dynamic-component :component="$icon" class="{{ $iconClass }}" aria-hidden="true" />
        @endif
        {{ $slot }}
        @if ($iconRight)
            <x-dynamic-component :component="$iconRight" class="{{ $iconClass }} transition-transform duration-300 group-hover/btn:translate-x-0.5" aria-hidden="true" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($variant === 'whatsapp' && ! $icon)
            <x-ui.icon-whatsapp class="{{ $iconClass }}" />
        @elseif ($icon)
            <x-dynamic-component :component="$icon" class="{{ $iconClass }}" aria-hidden="true" />
        @endif
        {{ $slot }}
        @if ($iconRight)
            <x-dynamic-component :component="$iconRight" class="{{ $iconClass }} transition-transform duration-300 group-hover/btn:translate-x-0.5" aria-hidden="true" />
        @endif
    </button>
@endif
