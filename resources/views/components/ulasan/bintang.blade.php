{{-- Deret 5 bintang untuk nilai 0–5 (boleh desimal). Pembaca layar mendengar "4,5 dari 5 bintang". --}}
@props(['value' => 0, 'size' => 'size-4'])

@php
    $value = max(0, min(5, (float) $value));
    $label = str_replace('.', ',', rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.')).' dari 5 bintang';
@endphp

<span {{ $attributes->class('relative inline-flex shrink-0 leading-none') }} role="img" aria-label="{{ $label }}">
    <span class="flex text-kabut-300" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)
            <x-heroicon-s-star class="{{ $size }}" />
        @endfor
    </span>
    <span class="absolute inset-y-0 left-0 flex overflow-hidden text-emas-500" style="width: {{ $value / 5 * 100 }}%" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)
            <x-heroicon-s-star class="{{ $size }} shrink-0" />
        @endfor
    </span>
</span>
