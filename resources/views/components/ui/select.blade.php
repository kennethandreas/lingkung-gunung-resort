{{-- Pilihan (select) dengan gaya yang sama seperti input. --}}
@props(['invalid' => false])

<div class="relative">
    <select {{ $attributes->class([
        'block h-12 w-full appearance-none rounded-sm border bg-white pl-4 pr-10 text-[0.9375rem] text-kabut-900 shadow-sm transition',
        'focus:outline-none focus:ring-4',
        'border-kabut-300 focus:border-hutan-600 focus:ring-hutan-600/15' => ! $invalid,
        'border-bahaya-600 focus:ring-bahaya-600/15' => $invalid,
    ]) }}>
        {{ $slot }}
    </select>
    <x-heroicon-m-chevron-down class="pointer-events-none absolute right-3.5 top-1/2 size-5 -translate-y-1/2 text-kabut-600" aria-hidden="true" />
</div>
