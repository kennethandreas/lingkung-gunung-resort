{{--
    Daftar ulasan tayang + urutan/saring bintang + halaman. Dipakai di /ulasan dan halaman ulasan per target.
    $reviews = paginator dari ReviewService::publishedReviews (query string ikut terbawa).
--}}
@props(['reviews', 'summary', 'sort' => 'terbaru', 'stars' => null, 'showTarget' => false])

@use('App\Http\Controllers\ReviewController')

<div {{ $attributes }} id="daftar-ulasan">
    @if ($summary['count'] > 0)
        <form method="GET" action="{{ url()->current() }}#daftar-ulasan" class="flex flex-wrap items-end gap-4 border-b border-kabut-300 pb-5">
            <div>
                <label for="urut" class="label-kecil block text-kabut-700">Urutkan</label>
                <select id="urut" name="urut" class="mt-1.5 h-10 rounded-none border border-kabut-300 bg-white px-3 pr-8 text-sm text-kabut-900 focus:border-hutan-700 focus:outline-none focus:ring-1 focus:ring-hutan-700" onchange="this.form.requestSubmit()">
                    @foreach (ReviewController::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="bintang" class="label-kecil block text-kabut-700">Nilai</label>
                <select id="bintang" name="bintang" class="mt-1.5 h-10 rounded-none border border-kabut-300 bg-white px-3 pr-8 text-sm text-kabut-900 focus:border-hutan-700 focus:outline-none focus:ring-1 focus:ring-hutan-700" onchange="this.form.requestSubmit()">
                    <option value="">Semua nilai</option>
                    @foreach ([5, 4, 3, 2, 1] as $star)
                        <option value="{{ $star }}" @selected($stars === $star)>{{ $star }} bintang ({{ $summary['distribution'][$star] }})</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="h-10 border border-hutan-900 px-4 text-xs font-semibold uppercase tracking-[0.14em] text-hutan-900">Terapkan</button></noscript>
        </form>
    @endif

    @forelse ($reviews as $review)
        <x-ulasan.kartu :review="$review" :show-target="$showTarget" :class="$loop->first ? 'border-t-0' : ''" />
    @empty
        {{-- Tanpa ulasan sama sekali: kotak ringkasan sudah menjelaskannya --}}
        @if ($stars)
            <p class="py-8 text-kabut-700">Belum ada ulasan dengan nilai {{ $stars }} bintang. <a href="{{ url()->current() }}#daftar-ulasan" class="font-semibold text-hutan-800 underline underline-offset-4">Tampilkan semua ulasan</a></p>
        @endif
    @endforelse

    @if ($reviews->hasPages())
        <nav class="flex items-center justify-between gap-4 border-t border-kabut-300 pt-5 text-sm" aria-label="Halaman ulasan">
            @if ($reviews->onFirstPage())
                <span class="text-kabut-400">Sebelumnya</span>
            @else
                <a href="{{ $reviews->previousPageUrl() }}#daftar-ulasan" class="tautan-panah text-hutan-900" rel="prev"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Sebelumnya</a>
            @endif
            <span class="text-kabut-600">Halaman {{ $reviews->currentPage() }} dari {{ $reviews->lastPage() }}</span>
            @if ($reviews->hasMorePages())
                <a href="{{ $reviews->nextPageUrl() }}#daftar-ulasan" class="tautan-panah text-hutan-900" rel="next">Berikutnya <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
            @else
                <span class="text-kabut-400">Berikutnya</span>
            @endif
        </nav>
    @endif
</div>
