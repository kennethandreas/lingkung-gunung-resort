{{-- Konten muncul halus saat di-scroll. Tanpa JS / reduced-motion: langsung tampil. --}}
@props(['delay' => 0, 'as' => 'div'])

<{{ $as }}
    x-data
    x-intersect.once.margin.-10%="$el.classList.add('is-visible')"
    {{ $attributes->class('reveal') }}
    @if ($delay) style="--reveal-delay: {{ (int) $delay }}ms" @endif
>{{ $slot }}</{{ $as }}>
