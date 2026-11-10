{{-- Kotak pesan. variant: info | sukses | peringatan | bahaya --}}
@props(['variant' => 'info', 'title' => null])

@php
    $styles = [
        'info' => ['bg-info-50 text-info-800 ring-info-600/20', 'heroicon-o-information-circle'],
        'sukses' => ['bg-sukses-50 text-sukses-800 ring-sukses-600/20', 'heroicon-o-check-circle'],
        'peringatan' => ['bg-peringatan-50 text-peringatan-800 ring-peringatan-600/20', 'heroicon-o-exclamation-triangle'],
        'bahaya' => ['bg-bahaya-50 text-bahaya-800 ring-bahaya-600/20', 'heroicon-o-x-circle'],
    ];
    [$classes, $icon] = $styles[$variant] ?? $styles['info'];
@endphp

<div {{ $attributes->class('flex gap-3 rounded-sm p-4 ring-1 ring-inset '.$classes) }} role="{{ $variant === 'bahaya' ? 'alert' : 'status' }}">
    <x-dynamic-component :component="$icon" class="size-5 shrink-0" aria-hidden="true" />
    <div class="text-sm leading-relaxed">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
</div>
