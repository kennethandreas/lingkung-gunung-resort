@use('App\Support\WhatsApp')

@php
    $includes = collect($unitType->includes ?? []);
    $breakfast = $includes->first(fn ($i) => str_starts_with($i, 'Breakfast:'));
    $features = $includes->reject(fn ($i) => str_starts_with($i, 'Breakfast:'))->values();
    $unitLabel = match ($unitType->pricing_unit->value) { 'person' => 'orang', 'tent' => 'tenda', 'plot' => 'kavling', default => 'kamar' };
    $cover = $photos->first();
    $weekendLabel = app(\App\Services\PricingService::class)->weekendLabel();
    $weekdayLabel = 'malam lainnya';
@endphp

<x-layouts.public :title="$unitType->marketing_name" :description="\Illuminate\Support\Str::limit($unitType->description ?? $unitType->tagline, 155)" transparent-header>
    <x-ui.page-hero :eyebrow="$unitType->tagline" :title="$unitType->marketing_name" :media="$cover" :alt="$unitType->marketing_name">
        <div class="mt-6 flex flex-wrap gap-2">
            @unless ($unitType->isPricedPerPerson())
                <x-ui.badge variant="kaca" icon="heroicon-m-user-group">Maks. {{ $unitType->capacity_pax }} orang</x-ui.badge>
            @endunless
            @if ($unitType->bed_configuration)
                <x-ui.badge variant="kaca" icon="heroicon-m-moon">{{ $unitType->bed_configuration }}</x-ui.badge>
            @endif
            @if ($unitType->breakfast_pax)
                <x-ui.badge variant="kaca" icon="heroicon-m-sun">Sarapan {{ $unitType->breakfast_pax }} orang</x-ui.badge>
            @endif
        </div>
    </x-ui.page-hero>

    <section class="section bg-kabut-50">
        <div class="container-lg grid gap-10 lg:grid-cols-[1fr_24rem]">
            <div class="min-w-0 space-y-10">
                {{-- Galeri unit: mosaik 1 besar + 4 kecil, lightbox dengan keterangan, panah keyboard & geser --}}
                @if ($photos->isNotEmpty())
                    <div x-data="{
                            open: false, index: 0, startX: null,
                            photos: @js($photos->map(fn ($m) => ['src' => $m->getUrl('lg'), 'alt' => $m->getCustomProperty('alt', $unitType->marketing_name)])->values()),
                            step(d) { this.index = (this.index + d + this.photos.length) % this.photos.length },
                         }">
                        <div class="grid grid-cols-4 grid-rows-2 gap-3" style="aspect-ratio: 16 / 9">
                            @foreach ($photos->take(5) as $i => $photo)
                                <button type="button" x-on:click="index = {{ $i }}; open = true" aria-label="Perbesar: {{ $photo->getCustomProperty('alt', $unitType->marketing_name) }}"
                                        @class(['group relative overflow-hidden rounded-foto focus-visible:outline-offset-4', 'col-span-4 row-span-2' => $i === 0, 'sm:col-span-2' => $i === 0 && $photos->count() >= 5, 'max-sm:hidden' => $i > 0, 'hidden' => $i > 0 && $photos->count() < 5])>
                                    <x-ui.picture :media="$photo" :alt="$photo->getCustomProperty('alt', $unitType->marketing_name)" ratio="" class="!absolute inset-0" :sizes="$i === 0 ? '(min-width:1024px) 40vw, 100vw' : '(min-width:1024px) 20vw, 50vw'" :priority="$i === 0" img-class="transition duration-[1200ms] ease-alam group-hover:scale-105" />
                                    @if ($i === 4 && $photos->count() > 5)
                                        <span class="absolute inset-0 flex items-center justify-center bg-hutan-950/55 text-sm font-semibold text-white">+{{ $photos->count() - 5 }} foto</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm text-kabut-600">
                            <p>
                                @if ($photos->first()->getCustomProperty('placeholder'))
                                    <x-heroicon-m-information-circle class="inline size-4 align-text-bottom" aria-hidden="true" /> Gambar ilustrasi dari brosur resort; foto asli menyusul.
                                @else
                                    <x-heroicon-m-camera class="inline size-4 align-text-bottom" aria-hidden="true" /> Foto asli resort
                                @endif
                            </p>
                            <button type="button" class="font-semibold text-hutan-700 hover:text-hutan-900" x-on:click="index = 0; open = true">Lihat semua {{ $photos->count() }} foto</button>
                        </div>

                        <div x-show="open" x-cloak x-transition.opacity.duration.300ms class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-hutan-950/95 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Galeri foto {{ $unitType->marketing_name }}"
                             x-on:keydown.escape.window="open = false" x-on:keydown.arrow-right.window="open && step(1)" x-on:keydown.arrow-left.window="open && step(-1)"
                             x-on:click.self="open = false" x-on:touchstart.passive="startX = $event.touches[0].clientX" x-on:touchend="if (startX !== null && Math.abs($event.changedTouches[0].clientX - startX) > 50) step($event.changedTouches[0].clientX < startX ? 1 : -1); startX = null" x-trap.noscroll="open">
                            <template x-for="item in [photos[index]]" x-bind:key="index">
                                <img x-bind:src="item.src" x-bind:alt="item.alt" class="foto-masuk max-h-[78vh] w-auto max-w-full rounded-foto shadow-angkat">
                            </template>
                            <div class="flex items-center gap-4 text-sm text-kabut-200"><span x-text="photos[index].alt"></span><span class="tabular-nums text-kabut-400" x-text="`${index + 1} / ${photos.length}`"></span></div>
                            <button type="button" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-none bg-white/10 text-white hover:bg-white/20" x-on:click="open = false" aria-label="Tutup"><x-heroicon-o-x-mark class="size-6" /></button>
                            <button type="button" class="absolute left-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-none bg-white/10 text-white hover:bg-white/20 sm:left-6" x-show="photos.length > 1" x-on:click="step(-1)" aria-label="Foto sebelumnya"><x-heroicon-m-chevron-left class="size-6" /></button>
                            <button type="button" class="absolute right-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-none bg-white/10 text-white hover:bg-white/20 sm:right-6" x-show="photos.length > 1" x-on:click="step(1)" aria-label="Foto berikutnya"><x-heroicon-m-chevron-right class="size-6" /></button>
                        </div>
                    </div>
                @endif

                <div>
                    <h2 class="font-display text-display-md font-semibold text-hutan-900">Tentang {{ $unitType->marketing_name }}</h2>
                    <p class="mt-4 text-lg leading-relaxed text-kabut-700">{{ $unitType->description }}</p>
                </div>

                @if ($features->isNotEmpty())
                    <div>
                        <h2 class="font-display text-2xl font-semibold text-hutan-900">Fasilitas</h2>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($features as $feature)
                                <li class="flex items-center gap-3 text-kabut-800"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        @if ($breakfast)
                            <p class="mt-5 rounded-sm bg-emas-50 p-4 text-sm text-emas-800"><strong>Sarapan:</strong> {{ \Illuminate\Support\Str::after($breakfast, 'Breakfast: ') }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Panel harga & pemesanan --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-kartu bg-white p-6 shadow-lembut ring-1 ring-kabut-200">
                    <h2 class="font-display text-xl font-semibold text-hutan-900">Harga per malam</h2>
                    <x-ulasan.singkat :summary="$reviewSummary" href="#ulasan" class="mt-2" />
                    <dl class="mt-4 space-y-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-kabut-600">Weekday <span class="text-xs">({{ $weekdayLabel }})</span></dt>
                            <dd class="text-lg font-bold text-hutan-900">{{ $prices['weekday'] ? 'Rp'.number_format($prices['weekday'], 0, ',', '.') : '-' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-kabut-600">Weekend <span class="text-xs">({{ $weekendLabel }})</span></dt>
                            <dd class="text-lg font-bold text-hutan-900">{{ $prices['weekend'] ? 'Rp'.number_format($prices['weekend'], 0, ',', '.') : '-' }}</dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-xs text-kabut-600">Per {{ $unitLabel }} per malam. Hari libur nasional dapat dikenakan tarif weekend.</p>

                    @if ($unitType->is_bookable_online)
                        <x-ui.search-box :unit="$unitType->slug" compact class="mt-6 !bg-kabut-50 !p-4 !shadow-none" />
                    @else
                        <x-ui.alert variant="info" class="mt-6">Akomodasi ini dipesan melalui tim kami.</x-ui.alert>
                        <x-ui.button variant="whatsapp" size="lg" class="mt-4 w-full" :href="WhatsApp::link('Halo, saya ingin memesan '.$unitType->marketing_name.'.')" target="_blank" rel="noopener">Pesan via WhatsApp</x-ui.button>
                    @endif

                    <x-ui.save-button :unit-type="$unitType" look="button" class="mt-3" />
                </div>
            </aside>
        </div>
    </section>

    {{-- Ulasan khusus akomodasi ini --}}
    <section class="section border-t border-kabut-300/70 bg-kabut-50" id="ulasan">
        <div class="container-lg grid gap-10 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start">
            <div>
                <x-ui.section-heading eyebrow="Ulasan tamu" :title="'Ulasan '.$unitType->marketing_name" />
                <x-ulasan.ringkasan :summary="$reviewSummary" class="mt-8" />
            </div>
            <div class="min-w-0">
                @foreach ($latestReviews as $review)
                    <x-ulasan.kartu :review="$review" :class="$loop->first ? 'border-t-0 pt-0 lg:pt-6' : ''" />
                @endforeach
                @if ($reviewSummary['count'] > $latestReviews->count())
                    <a href="{{ route('ulasan.target', ['akomodasi', $unitType->slug]) }}" class="tautan-panah mt-2 text-hutan-900">Lihat semua {{ $reviewSummary['count'] }} ulasan <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                @endif
            </div>
        </div>
    </section>

    @if ($others->isNotEmpty())
        <section class="section bg-white">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Pilihan lain" :script="true" title="Akomodasi lainnya" />
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($others as $row)
                        <x-ui.card-akomodasi class="h-full" :name="$row['model']->marketing_name" :tagline="$row['model']->tagline" :media="$row['cover']" :href="route('akomodasi.show', $row['model'])" :save="$row['model']" :price="$row['prices']['from']" :price-unit="$row['unit']" :capacity="$row['model']->isPricedPerPerson() ? null : $row['model']->capacity_pax" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
