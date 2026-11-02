<x-layouts.public title="Artikel" description="Kabar, tips berkemah, dan cerita acara dari Lingkung Gunung Adventure Camp Bogor." transparent-header>
    <x-ui.page-hero eyebrow="Artikel" title="Kabar, tips, dan cerita dari Lingkung Gunung" lead="Panduan berkemah, info akomodasi, dan cerita rombongan yang pernah berkegiatan di sini." image="images/resort/campervan-atap" alt="Mobil dengan tenda atap di area campervan" />

    <section class="section bg-kabut-50">
        <div class="container-lg">
            @if ($categories->count() > 1)
                <nav class="flex flex-wrap gap-2" aria-label="Kategori artikel">
                    <a href="{{ route('artikel.index') }}" @class(['rounded-none px-4 py-2 text-sm font-semibold transition', 'bg-hutan-800 text-white' => ! $category, 'bg-white text-kabut-700 ring-1 ring-kabut-200 hover:ring-hutan-300' => $category]) @if (! $category) aria-current="page" @endif>Semua</a>
                    @foreach ($categories as $key => $label)
                        <a href="{{ route('artikel.index', ['kategori' => $key]) }}" @class(['rounded-none px-4 py-2 text-sm font-semibold transition', 'bg-hutan-800 text-white' => $category === $key, 'bg-white text-kabut-700 ring-1 ring-kabut-200 hover:ring-hutan-300' => $category !== $key]) @if ($category === $key) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($articles->isEmpty())
                <p class="mt-10 text-kabut-600">Belum ada artikel di kategori ini.</p>
            @else
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <x-ui.reveal :delay="($loop->index % 3) * 100" class="h-full">
                            <x-ui.card-artikel :article="$article" class="h-full" />
                        </x-ui.reveal>
                    @endforeach
                </div>
                <div class="mt-10">{{ $articles->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.public>
