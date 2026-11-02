@use('App\Support\WhatsApp')

@php
    $cover = $article->getFirstMedia('cover');
    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => $article->excerpt,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'image' => $cover?->getUrl('lg'),
        'publisher' => ['@type' => 'Organization', 'name' => 'Lingkung Gunung Adventure Camp Bogor'],
        'mainEntityOfPage' => route('artikel.show', $article),
    ]);
@endphp

<x-layouts.public :title="$article->title" :description="\Illuminate\Support\Str::limit($article->excerpt ?? strip_tags((string) $article->body), 155)" :og-image="$cover?->getUrl('lg')">
    <x-slot:head>
        <meta property="og:type" content="article">
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <article>
        <header class="bg-kabut-50 pt-10 sm:pt-16">
            <div class="container-lg max-w-4xl">
                <nav aria-label="Breadcrumb" class="text-sm text-kabut-600">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('home') }}" class="hover:text-hutan-800">Beranda</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('artikel.index') }}" class="hover:text-hutan-800">Artikel</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('artikel.index', ['kategori' => $article->category]) }}" class="hover:text-hutan-800">{{ $article->categoryLabel() }}</a></li>
                    </ol>
                </nav>
                <h1 class="masuk mt-5 font-display text-display-xl font-semibold text-balance text-hutan-900">{{ $article->title }}</h1>
                <p class="masuk mt-4 flex flex-wrap items-center gap-2 text-sm text-kabut-600" style="--masuk-delay: 100ms">
                    <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('l, d F Y') }}</time>
                    <span aria-hidden="true">·</span> {{ $article->readingMinutes() }} menit baca
                </p>
                @if ($article->excerpt)
                    <p class="masuk mt-6 text-xl leading-relaxed text-kabut-700" style="--masuk-delay: 180ms">{{ $article->excerpt }}</p>
                @endif
            </div>
            @if ($cover)
                <div class="container-lg mt-10 max-w-5xl">
                    <x-ui.reveal class="reveal-tirai">
                        <x-ui.picture :media="$cover" :alt="$cover->getCustomProperty('alt', $article->title)" ratio="aspect-[16/9]" priority class="rounded-foto shadow-angkat" sizes="(min-width:1024px) 64rem, 100vw" />
                    </x-ui.reveal>
                </div>
            @endif
        </header>

        <div class="section bg-white">
            <div class="container-lg max-w-3xl">
                <div class="prose-lingkung text-lg">{!! \Illuminate\Support\Str::sanitizeHtml((string) $article->body) !!}</div>

                @if ($article->source_url)
                    <p class="mt-10 border-t border-kabut-200 pt-6 text-sm text-kabut-600">
                        Disusun dari sumber resmi: <a href="{{ $article->source_url }}" target="_blank" rel="noopener" class="font-semibold text-hutan-700 underline decoration-emas-400 underline-offset-4">{{ parse_url($article->source_url, PHP_URL_HOST) }}</a>
                    </p>
                @endif

                <div class="mt-10 flex flex-wrap items-center gap-3 rounded-kartu bg-hutan-50 p-6">
                    <p class="flex-1 font-semibold text-hutan-900">Ingin merencanakan kunjungan?</p>
                    <x-ui.button variant="primary" :href="route('pesan')">Cek Ketersediaan</x-ui.button>
                    <x-ui.button variant="whatsapp" :href="WhatsApp::link('Halo Lingkung Gunung, saya membaca artikel: '.$article->title)" target="_blank" rel="noopener">WhatsApp</x-ui.button>
                </div>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="section bg-kabut-50">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Baca juga" title="Artikel lainnya" />
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <x-ui.card-artikel :article="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
