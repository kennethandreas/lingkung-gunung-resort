<x-layouts.public :title="$page->title" :description="$page->meta_description">
    <section class="section bg-kabut-50">
        <div class="container-lg max-w-3xl">
            <h1 class="font-display text-display-lg font-semibold text-hutan-900">{{ $page->title }}</h1>
            <p class="mt-2 text-sm text-kabut-600">Diperbarui {{ $page->updated_at->translatedFormat('d F Y') }}</p>
            <div class="prose-lingkung mt-8 overflow-x-auto rounded-kartu bg-white p-6 shadow-lembut ring-1 ring-kabut-200 sm:p-10">
                {!! \Illuminate\Support\Str::sanitizeHtml((string) $page->body) !!}
            </div>
        </div>
    </section>
</x-layouts.public>
