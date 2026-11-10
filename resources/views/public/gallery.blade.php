@use('App\Models\GalleryPhoto')

@php
    $items = $photos->map(fn ($photo) => [
        'src' => $photo->getFirstMediaUrl('photo', 'lg'),
        'alt' => $photo->title,
        'category' => $photo->category,
    ])->values();
@endphp

<x-layouts.public title="Galeri" description="Galeri foto Lingkung Gunung Adventure Camp Bogor: akomodasi, aktivitas, kuliner, fasilitas, dan alam 850 MDPL." transparent-header>
    <x-ui.page-hero eyebrow="Galeri" title="Foto Lingkung Gunung" lead="Dokumentasi asli akomodasi, aktivitas, kuliner, dan alam sekitar resort." image="images/resort/deck-pandang" alt="Papan nama Lingkung Gunung di tepi lembah" />

    <section class="section bg-kabut-50"
             x-data="{
                filter: 'semua', open: false, index: 0, items: @js($items), startX: null,
                get visible() { return this.items.map((item, i) => ({ ...item, i })).filter(item => this.filter === 'semua' || item.category === this.filter) },
                get position() { return this.visible.findIndex(item => item.i === this.index) },
                get current() { return this.items[this.index] ?? null },
                show(i) { this.index = i; this.open = true },
                step(delta) {
                    const list = this.visible;
                    if (! list.length) return;
                    this.index = list[(this.position + delta + list.length) % list.length].i;
                },
                swipeEnd(event) {
                    if (this.startX === null) return;
                    const dx = event.changedTouches[0].clientX - this.startX;
                    if (Math.abs(dx) > 50) this.step(dx < 0 ? 1 : -1);
                    this.startX = null;
                },
             }">
        <div class="container-lg">
            <div class="flex flex-wrap gap-2" role="group" aria-label="Saring kategori foto">
                <button type="button" class="rounded-none px-4 py-2 text-sm font-semibold transition duration-300 ease-alam" x-bind:class="filter === 'semua' ? 'bg-hutan-800 text-white shadow-lembut' : 'bg-white text-kabut-700 ring-1 ring-kabut-200 hover:ring-hutan-300'" x-bind:aria-pressed="filter === 'semua'" x-on:click="filter = 'semua'">Semua</button>
                @foreach (GalleryPhoto::CATEGORIES as $key => $label)
                    @if ($photos->contains('category', $key))
                        <button type="button" class="rounded-none px-4 py-2 text-sm font-semibold transition duration-300 ease-alam" x-bind:class="filter === '{{ $key }}' ? 'bg-hutan-800 text-white shadow-lembut' : 'bg-white text-kabut-700 ring-1 ring-kabut-200 hover:ring-hutan-300'" x-bind:aria-pressed="filter === '{{ $key }}'" x-on:click="filter = '{{ $key }}'">{{ $label }}</button>
                    @endif
                @endforeach
            </div>

            <ul class="mt-8 columns-1 gap-4 sm:columns-2 lg:columns-3">
                @forelse ($photos as $i => $photo)
                    <li class="mb-4 break-inside-avoid" x-show="filter === 'semua' || filter === '{{ $photo->category }}'" x-transition.opacity.duration.400ms>
                        <button type="button" class="group relative block w-full overflow-hidden rounded-foto text-left focus-visible:outline-2 focus-visible:outline-offset-2" x-on:click="show({{ $i }})" aria-label="Perbesar foto: {{ $photo->title }}">
                            <x-ui.picture :media="$photo->getFirstMedia('photo')" :alt="$photo->title" ratio="aspect-[4/3]" sizes="(min-width:1024px) 33vw, (min-width:640px) 50vw, 100vw" img-class="transition duration-700 ease-alam group-hover:scale-105">
                                <span class="pointer-events-none absolute inset-0 flex items-end bg-gradient-to-t from-hutan-950/70 via-transparent to-transparent p-4 opacity-0 transition duration-500 ease-alam group-hover:opacity-100 group-focus-visible:opacity-100">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-white"><x-heroicon-m-magnifying-glass-plus class="size-5" aria-hidden="true" /> Perbesar</span>
                                </span>
                            </x-ui.picture>
                            <span class="mt-2 block text-sm text-kabut-600">{{ $photo->title }}</span>
                        </button>
                    </li>
                @empty
                    <li class="text-kabut-600">Foto akan segera ditambahkan.</li>
                @endforelse
            </ul>
        </div>

        {{-- Lightbox: Esc tutup, ←/→ pindah foto, geser di HP --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition duration-300 ease-alam" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-hutan-950/95 p-4 backdrop-blur-sm"
             role="dialog" aria-modal="true" aria-label="Foto diperbesar"
             x-on:keydown.escape.window="open = false" x-on:keydown.arrow-right.window="open && step(1)" x-on:keydown.arrow-left.window="open && step(-1)"
             x-on:click.self="open = false" x-on:touchstart.passive="startX = $event.touches[0].clientX" x-on:touchend="swipeEnd($event)"
             x-trap.noscroll="open">
            {{-- x-for dengan key = elemen dibuat ulang tiap ganti foto, sehingga animasi .foto-masuk diputar lagi --}}
            <template x-for="item in (current ? [current] : [])" x-bind:key="index">
                <img x-bind:src="item.src" x-bind:alt="item.alt" class="foto-masuk max-h-[78vh] w-auto max-w-full rounded-foto shadow-angkat">
            </template>
            <div class="flex items-center gap-4 text-sm text-kabut-200">
                <span x-text="current?.alt"></span>
                <span class="tabular-nums text-kabut-400" x-text="`${position + 1} / ${visible.length}`"></span>
            </div>
            <button type="button" class="absolute left-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-none bg-white/10 text-white transition hover:bg-white/20 sm:left-6" x-on:click="step(-1)" x-show="visible.length > 1" aria-label="Foto sebelumnya"><x-heroicon-o-chevron-left class="size-6" /></button>
            <button type="button" class="absolute right-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-none bg-white/10 text-white transition hover:bg-white/20 sm:right-6" x-on:click="step(1)" x-show="visible.length > 1" aria-label="Foto berikutnya"><x-heroicon-o-chevron-right class="size-6" /></button>
            <button type="button" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-none bg-white/10 text-white transition hover:bg-white/20" x-on:click="open = false" aria-label="Tutup"><x-heroicon-o-x-mark class="size-6" /></button>
        </div>
    </section>
</x-layouts.public>
