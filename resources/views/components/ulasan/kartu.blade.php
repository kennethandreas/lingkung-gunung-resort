{{-- Satu ulasan tayang: nilai, nama tampil, tanggal, isi (teks polos, di-escape), foto dari tamu. --}}
@props(['review', 'showTarget' => false])

@php
    $photos = $review->getMedia('photos');
@endphp

<article {{ $attributes->class('border-t border-kabut-300 py-6') }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <x-ulasan.bintang :value="$review->rating" />
            <p class="font-semibold text-hutan-950">{{ $review->reviewer_name }}</p>
        </div>
        <p class="text-sm text-kabut-600"><time datetime="{{ $review->published_at->toDateString() }}">{{ $review->published_at->translatedFormat('d F Y') }}</time></p>
    </div>
    @if ($showTarget)
        <p class="label-kecil mt-2 text-kabut-600">{{ $review->targetName() }}</p>
    @endif

    @if ($review->comment)
        <p class="mt-3 max-w-3xl whitespace-pre-line break-words leading-relaxed text-kabut-800">{{ $review->comment }}</p>
    @endif

    @if ($photos->isNotEmpty())
        <div x-data="{ open: null }" class="mt-4">
            <p class="label-kecil mb-2 text-kabut-600">Foto dari tamu</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($photos as $photo)
                    <li>
                        <button type="button" x-on:click="open = '{{ route('ulasan.foto', [$photo->uuid, 'md']) }}'" class="block size-20 overflow-hidden border border-kabut-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hutan-700 sm:size-24" aria-label="Perbesar foto dari {{ $review->reviewer_name }}">
                            <img src="{{ route('ulasan.foto', [$photo->uuid, 'sm']) }}" alt="Foto dari {{ $review->reviewer_name }}" loading="lazy" decoding="async" class="size-full object-cover">
                        </button>
                    </li>
                @endforeach
            </ul>
            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-950/95 p-4" role="dialog" aria-modal="true" aria-label="Foto dari {{ $review->reviewer_name }}"
                 x-on:keydown.escape.window="open = null" x-on:click.self="open = null" x-trap.noscroll="open">
                <img x-bind:src="open" alt="Foto dari {{ $review->reviewer_name }}" class="max-h-[85vh] w-auto max-w-full">
                <button type="button" class="absolute right-4 top-4 flex size-11 items-center justify-center bg-white/10 text-white hover:bg-white/20" x-on:click="open = null" aria-label="Tutup"><x-heroicon-o-x-mark class="size-6" /></button>
            </div>
        </div>
    @endif
</article>
