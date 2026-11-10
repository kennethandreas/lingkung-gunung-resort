{{-- Label section editorial: nomor + garis emas yang tumbuh saat terlihat + teks. Contoh: 01 ——— Tentang kami --}}
@props(['number' => null, 'text', 'dark' => false])

<p x-data x-intersect.once="$el.classList.add('is-visible')" {{ $attributes->class(['label-section flex items-center gap-3 text-xs font-bold uppercase tracking-[0.22em]', 'text-emas-700' => ! $dark, 'text-emas-300' => $dark]) }}>
    @if ($number)
        <span class="font-display text-sm tracking-normal tabular-nums">{{ $number }}</span>
    @endif
    <span @class(['label-section-garis h-px w-10', 'bg-emas-500/60' => ! $dark, 'bg-emas-300/60' => $dark]) aria-hidden="true"></span>
    <span>{{ $text }}</span>
</p>
