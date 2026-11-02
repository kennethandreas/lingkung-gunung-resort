{{-- Rating ringkas satu baris ("★★★★☆ 4,6 · 12 ulasan"). Tidak tampil sama sekali bila belum ada ulasan tayang. --}}
@props(['summary' => null, 'href' => null, 'dark' => false])

@if ($summary && ($summary['count'] ?? 0) > 0)
    @php($text = str_replace('.', ',', number_format($summary['average'], 1)).' · '.$summary['count'].' ulasan')
    <span {{ $attributes->class(['inline-flex flex-wrap items-center gap-2 text-sm', 'text-kabut-700' => ! $dark, 'text-kabut-200' => $dark]) }}>
        <x-ulasan.bintang :value="$summary['average']" size="size-3.5" />
        @if ($href)
            <a href="{{ $href }}" @class(['font-semibold underline-offset-4 hover:underline', 'text-hutan-900' => ! $dark, 'text-white' => $dark])>{{ $text }}</a>
        @else
            <span @class(['font-semibold', 'text-hutan-900' => ! $dark, 'text-white' => $dark])>{{ $text }}</span>
        @endif
    </span>
@endif
