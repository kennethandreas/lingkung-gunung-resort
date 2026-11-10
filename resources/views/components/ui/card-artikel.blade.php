{{-- Kartu artikel: foto sampul, kategori, judul, ringkasan, tanggal & waktu baca. --}}
@props(['article', 'featured' => false])

<article {{ $attributes->class(['group relative flex flex-col overflow-hidden rounded-kartu bg-white shadow-lembut ring-1 ring-kabut-200/70 transition duration-500 ease-alam']) }}>
    <x-ui.picture :media="$article->getFirstMedia('cover')" :alt="$article->title" :ratio="$featured ? 'aspect-[16/9]' : 'aspect-[3/2]'" :sizes="$featured ? '(min-width:1024px) 60vw, 100vw' : '(min-width:768px) 33vw, 100vw'" img-class="transition duration-[1200ms] ease-alam group-hover:scale-105" />
    <div class="flex flex-1 flex-col p-6">
        <p class="flex items-center gap-2 text-xs font-semibold text-kabut-600">
            <span class="rounded-none bg-hutan-50 px-2.5 py-1 text-hutan-800">{{ $article->categoryLabel() }}</span>
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
            <span aria-hidden="true">·</span> {{ $article->readingMinutes() }} menit baca
        </p>
        <h3 @class(['mt-3 font-display font-semibold text-balance text-hutan-900', 'text-3xl' => $featured, 'text-xl' => ! $featured])>
            <a href="{{ route('artikel.show', $article) }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $article->title }}</a>
        </h3>
        @if ($article->excerpt)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-kabut-600">{{ $article->excerpt }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-hutan-700">Baca <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" aria-hidden="true" /></span>
    </div>
</article>
