@use('App\Support\WhatsApp')

@php
    // Cocokkan program dengan harga di tabel aktivitas (dikelola di admin › Aktivitas)
    $activityByName = $activities->keyBy(fn ($a) => strtolower($a->name));
    $groupList = $groups->keys()->values();
    $adventure = $groups->get($groupList->first(), collect());
    $others = $groups->except($groupList->first());
    $extras = $activities->filter(fn ($a) => $a->category === 'konsumsi');
    $priced = $activities->reject(fn ($a) => $a->category === 'konsumsi');
@endphp

<x-layouts.public title="Play: Petualangan & Budaya" description="Trekking, rafting, paintball, offroad, team building, dan pengalaman eco-culture Sunda di Lingkung Gunung, Bogor. Lihat harga aktivitas per orang." transparent-header>
    <x-ui.page-hero eyebrow="Aktivitas" title="Aktivitas alam & petualangan" lead="Trekking, rafting, paintball, offroad, dan pengalaman budaya Sunda. Lengkap dengan harga per orang dan jumlah peserta minimal." image="images/resort/trekking-sungai" alt="Rombongan trekking menyusuri sungai berbatu di kaki Gunung Pangrango">
        <div class="mt-8 flex flex-wrap gap-2">
            <x-ui.button variant="light" size="sm" href="#adventure">Adventure</x-ui.button>
            <x-ui.button variant="light" size="sm" href="#eco-culture">Eco-culture</x-ui.button>
            <x-ui.button variant="light" size="sm" href="#aktivitas">Harga aktivitas</x-ui.button>
        </div>
    </x-ui.page-hero>

    {{-- Adventure: baris editorial berselang-seling, foto besar --}}
    <section class="section bg-kabut-50" id="adventure">
        <div class="container-lg">
            <x-ui.section-label number="01" :text="$groupList->first() ?? 'Adventure & Adrenaline'" />
            <h2 class="mt-5 max-w-2xl font-display text-display-lg font-semibold text-balance text-hutan-900">Adrenalin di jalur sungai, hutan, dan tanah merah.</h2>
            <div class="mt-14 space-y-16 lg:space-y-24">
                @foreach ($adventure as $program)
                    @php
                        $activity = $activityByName->get(strtolower($program->name)) ?? $activityByName->get(str_replace('tracking', 'trekking', strtolower($program->name)));
                        $cover = $program->getFirstMedia('cover');
                    @endphp
                    <article class="grid items-center gap-8 lg:grid-cols-12 lg:gap-14">
                        <x-ui.reveal :class="\Illuminate\Support\Arr::toCssClasses(['reveal-tirai lg:col-span-7', 'lg:order-2' => $loop->odd])">
                            <div class="group overflow-hidden rounded-foto">
                                <x-ui.picture :media="$cover" :alt="$cover?->getCustomProperty('alt', $program->name) ?? $program->name" ratio="aspect-[3/2]" sizes="(min-width:1024px) 58vw, 100vw" img-class="transition duration-[1200ms] ease-alam group-hover:scale-105" />
                            </div>
                        </x-ui.reveal>
                        <x-ui.reveal :delay="120" :class="\Illuminate\Support\Arr::toCssClasses(['lg:col-span-5', 'lg:order-1' => $loop->odd])">
                            <p class="font-display text-6xl font-semibold text-emas-400/60 tabular-nums">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                            <h3 class="mt-2 font-display text-display-md font-semibold text-hutan-900">{{ $program->name }}</h3>
                            @if ($program->summary)
                                <p class="mt-1 font-script text-2xl text-emas-700">{{ $program->summary }}</p>
                            @endif
                            @if ($program->description)
                                <p class="mt-4 leading-relaxed text-kabut-700">{{ $program->description }}</p>
                            @endif
                            @if ($activity)
                                <dl class="mt-6 grid grid-cols-2 gap-4 rounded-kartu bg-white p-5 ring-1 ring-kabut-200">
                                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-kabut-600">Harga</dt><dd class="mt-1 text-lg font-bold text-hutan-900">Rp{{ number_format($activity->price, 0, ',', '.') }} <span class="text-sm font-normal text-kabut-600">/ {{ $activity->price_unit }}</span></dd></div>
                                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-kabut-600">Minimal</dt><dd class="mt-1 text-lg font-bold text-hutan-900">{{ $activity->min_pax }} orang</dd></div>
                                    @if ($activity->includes)
                                        <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-kabut-600">Termasuk</dt><dd class="mt-1 text-sm text-kabut-700">{{ $activity->includes }}</dd></div>
                                    @endif
                                </dl>
                            @endif
                        </x-ui.reveal>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kelompok lain (eco-culture): grid kartu --}}
    @foreach ($others as $group => $programs)
        <section @class(['section', 'bg-white' => $loop->odd, 'bg-kabut-50' => $loop->even]) @if ($loop->first) id="eco-culture" @endif>
            <div class="container-lg">
                <x-ui.section-label :number="str_pad($loop->iteration + 1, 2, '0', STR_PAD_LEFT)" :text="$group" />
                <h2 class="mt-5 max-w-2xl font-display text-display-lg font-semibold text-balance text-hutan-900">Mengenal budaya Sunda dan alam lewat tangan sendiri.</h2>
                <x-ui.reveal class="reveal-giliran mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($programs as $program)
                        @php
                            $activity = $activityByName->get(strtolower($program->name));
                        @endphp
                        <x-ui.program-card :program="$program" :price="$activity?->price" :price-unit="$activity?->price_unit ?? 'pax'" :meta="$activity ? 'Min. '.$activity->min_pax.' orang' : null" />
                    @endforeach
                </x-ui.reveal>
            </div>
        </section>
    @endforeach

    {{-- Tabel harga aktivitas (admin › Aktivitas) --}}
    @if ($priced->isNotEmpty())
        <section class="section bg-tanah-50" id="aktivitas">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Harga publish 2026" title="Daftar aktivitas & harga" lead="Harga per orang (kecuali disebutkan lain). Bisa ditambahkan ke paket rombongan lewat Rancang Paket." />
                <x-ui.reveal class="mt-10 overflow-x-auto rounded-kartu bg-white ring-1 ring-tanah-200">
                    <table class="w-full min-w-[40rem] border-collapse text-left text-sm">
                        <caption class="sr-only">Daftar harga aktivitas</caption>
                        <thead class="bg-tanah-100 text-kabut-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">Aktivitas</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Termasuk</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Min.</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-tanah-100">
                            @foreach ($priced as $activity)
                                <tr class="transition-colors hover:bg-tanah-50/70">
                                    <th scope="row" class="px-4 py-3 font-semibold text-hutan-900">
                                        {{ $activity->name }}
                                        <x-ulasan.singkat :summary="$activityRatings[$activity->id] ?? null" :href="route('ulasan.target', ['aktivitas', $activity->slug])" class="mt-1 !text-xs font-normal" />
                                    </th>
                                    <td class="px-4 py-3 text-kabut-700">{{ $activity->includes }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $activity->min_pax }} org</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">Rp{{ number_format($activity->price, 0, ',', '.') }} <span class="font-normal text-kabut-600">/ {{ $activity->price_unit }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.reveal>
            </div>
        </section>
    @endif

    @if ($extras->isNotEmpty())
        <section class="section bg-white">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Tambahan untuk rombongan" title="Momen kebersamaan" />
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    @foreach ($extras as $extra)
                        <div class="rounded-kartu bg-kabut-50 p-6 ring-1 ring-kabut-200">
                            <h3 class="font-display text-xl font-semibold text-hutan-900">{{ $extra->name }}</h3>
                            <p class="mt-1 text-sm text-kabut-600">{{ $extra->includes }}</p>
                            <p class="mt-3 text-sm text-kabut-600"><span class="text-lg font-bold text-hutan-900">Rp{{ number_format($extra->price, 0, ',', '.') }}</span> / {{ $extra->price_unit }} · min. {{ $extra->min_pax }} orang</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="relative isolate overflow-hidden bg-hutan-950 py-20 text-white sm:py-28">
        <x-ui.picture src="images/resort/offroad" alt="" ratio="" class="!absolute inset-0 -z-20 opacity-35" sizes="100vw" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-hutan-950 via-hutan-950/80 to-transparent" aria-hidden="true"></div>
        <div class="container-lg flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
            <x-ui.section-heading :dark="true" eyebrow="Siap bertualang?" title="Gabungkan aktivitas dengan paket menginap atau rombongan." />
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="accent" size="lg" :href="route('rancang-paket')">Rancang paket</x-ui.button>
                <x-ui.button variant="light" size="lg" :href="WhatsApp::link('Halo, saya ingin memesan aktivitas di Lingkung Gunung.')" target="_blank" rel="noopener">Tanya via WhatsApp</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
