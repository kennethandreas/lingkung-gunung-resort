{{-- Keadaan kosong yang ramah (belum ada booking / belum ada akomodasi tersimpan). --}}
@props(['icon' => 'heroicon-o-sparkles', 'title', 'text' => null])

<div {{ $attributes->class('flex flex-col items-center border border-kabut-300 bg-white px-6 py-12 text-center') }}>
    <span class="flex size-14 items-center justify-center border border-kabut-300 text-hutan-800">
        <x-dynamic-component :component="$icon" class="size-7" aria-hidden="true" />
    </span>
    <p class="mt-4 font-display text-2xl text-hutan-950">{{ $title }}</p>
    @if ($text)
        <p class="mt-2 max-w-md text-sm leading-relaxed text-kabut-700">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-3">{{ $slot }}</div>
    @endif
</div>
