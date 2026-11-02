{{-- Hero halaman dalam: foto penuh dengan satu lapisan gelap, label kecil, judul serif, paragraf. Tanpa dekorasi. --}}
@props(['title', 'eyebrow' => null, 'lead' => null, 'media' => null, 'image' => null, 'alt' => ''])

<section class="relative isolate flex min-h-[26rem] items-end overflow-hidden bg-hutan-950 pb-12 pt-32 sm:min-h-[32rem] sm:pb-16">
    @if ($media || $image)
        <x-ui.picture :media="$media" :src="$image" :alt="$alt" priority sizes="100vw" ratio="" class="!absolute inset-0 -z-20" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-hutan-950/90 via-hutan-950/45 to-hutan-950/20" aria-hidden="true"></div>
    @endif
    <div class="container-lg">
        @if ($eyebrow)
            <p class="masuk label-kecil flex items-center gap-3 text-kabut-200"><span class="h-px w-8 bg-emas-300" aria-hidden="true"></span>{{ $eyebrow }}</p>
        @endif
        <h1 class="masuk mt-4 max-w-3xl font-display text-display-xl text-balance text-white" style="--masuk-delay: 80ms">{{ $title }}</h1>
        @if ($lead)
            <p class="masuk mt-5 max-w-2xl text-lg leading-relaxed text-kabut-100" style="--masuk-delay: 160ms">{{ $lead }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="masuk" style="--masuk-delay: 240ms">{{ $slot }}</div>
        @endif
    </div>
</section>
