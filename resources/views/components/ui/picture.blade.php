{{--
    Gambar responsif WebP dengan srcset + lazy loading.
    - src: path dasar tanpa ukuran & ekstensi, mis. "images/placeholder/glamping-luar"
           (file yang dicari: {src}-480.webp, {src}-960.webp, {src}-1600.webp).
           Kosong/null = placeholder rapi "Foto menyusul" (siap diganti dari admin panel).
    - alt: WAJIB diisi untuk aksesibilitas (kosongkan "" hanya untuk gambar dekoratif).
    - priority: true untuk gambar hero/LCP (eager + fetchpriority=high).
    - ratio: kelas aspect Tailwind, mis. "aspect-[4/3]".
--}}
@props([
    'src' => null,
    'media' => null, // foto unggahan admin (Spatie Media) - didahulukan daripada src
    'alt',
    'widths' => [480, 960, 1600],
    'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
    'priority' => false,
    'ratio' => 'aspect-[4/3]',
    'imgClass' => '',
])

@php
    $mediaSrcset = null;
    if ($media) {
        $mediaSrcset = collect(\App\Support\ImageSizes::WIDTHS)
            ->filter(fn ($w, $name) => $media->hasGeneratedConversion($name))
            ->map(fn ($w, $name) => $media->getUrl($name)." {$w}w");
    }
@endphp

<div {{ $attributes->class(['relative overflow-hidden bg-hutan-900', $ratio]) }}>
    @if ($media && $mediaSrcset?->isNotEmpty())
        <img
            src="{{ $media->getUrl($media->hasGeneratedConversion('md') ? 'md' : 'sm') }}"
            srcset="{{ $mediaSrcset->implode(', ') }}"
            sizes="{{ $sizes }}"
            alt="{{ $alt }}"
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            decoding="{{ $priority ? 'sync' : 'async' }}"
            @if ($priority) fetchpriority="high" @endif
            class="absolute inset-0 size-full object-cover {{ $imgClass }}"
        >
    @elseif ($src)
        <img
            src="{{ asset($src.'-'.$widths[1].'.webp') }}"
            srcset="{{ collect($widths)->map(fn ($w) => asset("{$src}-{$w}.webp")." {$w}w")->implode(', ') }}"
            sizes="{{ $sizes }}"
            alt="{{ $alt }}"
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            decoding="{{ $priority ? 'sync' : 'async' }}"
            @if ($priority) fetchpriority="high" @endif
            class="absolute inset-0 size-full object-cover {{ $imgClass }}"
        >
    @else
        {{-- Placeholder: siluet pegunungan berlapis + kabut, bukan kotak abu-abu --}}
        <div class="absolute inset-0 bg-gradient-to-b from-hutan-700 via-hutan-800 to-hutan-950" role="img" aria-label="{{ $alt }} (foto menyusul)">
            <svg class="absolute inset-x-0 bottom-0 h-3/4 w-full" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 120 L60 70 L110 105 L170 40 L230 95 L290 55 L350 90 L400 65 V200 H0Z" fill="#265036" />
                <path d="M0 150 L50 115 L120 145 L190 95 L250 135 L320 105 L400 130 V200 H0Z" fill="#1f402d" />
                <path d="M0 175 L80 150 L150 170 L230 140 L300 165 L400 150 V200 H0Z" fill="#0d1e15" />
            </svg>
            <div class="absolute inset-x-0 top-1/3 h-16 bg-kabut-50/10 blur-2xl"></div>
            <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-none bg-black/25 px-3 py-1 text-xs font-medium text-kabut-100 backdrop-blur-sm">
                <x-heroicon-o-camera class="size-3.5" aria-hidden="true" />
                Foto menyusul
            </span>
        </div>
    @endif
    {{ $slot }}
</div>
