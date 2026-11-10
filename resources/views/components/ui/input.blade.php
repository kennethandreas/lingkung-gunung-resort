{{-- Input teks/tanggal/email/telepon. Pasangkan dengan <x-ui.field>. --}}
@props(['invalid' => false, 'icon' => null])

<div class="relative">
    @if ($icon)
        <x-dynamic-component :component="$icon" class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-kabut-400" aria-hidden="true" />
    @endif
    <input {{ $attributes->class([
        'block h-12 w-full rounded-sm border bg-white text-[0.9375rem] text-kabut-900 shadow-sm transition placeholder:text-kabut-400',
        'focus:outline-none focus:ring-4',
        'pl-11 pr-4' => $icon,
        'px-4' => ! $icon,
        'border-kabut-300 focus:border-hutan-600 focus:ring-hutan-600/15' => ! $invalid,
        'border-bahaya-600 focus:border-bahaya-600 focus:ring-bahaya-600/15' => $invalid,
    ])->merge(['type' => 'text']) }} @if ($invalid) aria-invalid="true" @endif>
</div>
