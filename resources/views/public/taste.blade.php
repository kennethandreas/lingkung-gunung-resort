@use('App\Models\MenuOption')

<x-layouts.public title="Taste: Swaka Cafe & Resto" description="Swaka Cafe & Resto di Lingkung Gunung: kopi berkualitas, menu Nusantara, dan panorama pegunungan. Lihat pilihan Menu Group A–E untuk rombongan." transparent-header>
    <x-ui.page-hero eyebrow="Kuliner" title="Swaka Cafe & Resto" lead="Kopi dan menu Nusantara menghadap lembah, serta set menu A–E untuk rombongan." :media="$photo?->getFirstMedia('photo')" :alt="$photo?->title ?? 'Swaka Cafe & Resto'" />

    <section class="section bg-kabut-50">
        <div class="container-lg grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['heroicon-o-fire', 'Kopi berkualitas', 'Diseduh dari biji pilihan, dinikmati dengan udara sejuk 850 MDPL.'], ['heroicon-o-cake', 'Menu Nusantara', 'Hidangan rumahan khas Indonesia untuk tamu menginap maupun rombongan.'], ['heroicon-o-sun', 'Panorama pegunungan', 'Teras terbuka menghadap lembah dan kabut pagi.'], ['heroicon-o-user-group', 'Untuk keluarga & komunitas', 'Meja panjang untuk berkumpul dan berbagi cerita.']] as $i => [$icon, $title, $text])
                <x-ui.reveal :delay="$i * 80" class="rounded-kartu bg-white p-6 shadow-lembut ring-1 ring-kabut-200/70">
                    <x-dynamic-component :component="$icon" class="size-8 text-emas-600" aria-hidden="true" />
                    <h2 class="mt-4 font-display text-xl font-semibold text-hutan-900">{{ $title }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-kabut-600">{{ $text }}</p>
                </x-ui.reveal>
            @endforeach
        </div>
    </section>

    @if ($menuSets->isNotEmpty())
        <section class="section bg-white" id="menu-group">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Pilihan menu group" :script="true" title="Menu Makan A–E untuk rombongan" lead="Setiap paket fullboard memakai satu set menu. Di setiap set, tersedia opsi A–E untuk sarapan, makan siang, dan makan malam." />

                <div class="mt-10" x-data="{ set: '{{ $menuSets->first()->code }}', meal: 'breakfast' }">
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Set menu">
                        @foreach ($menuSets as $set)
                            <button type="button" role="tab" class="rounded-none px-5 py-2.5 text-sm font-semibold transition"
                                    x-bind:class="set === '{{ $set->code }}' ? 'bg-hutan-800 text-white shadow-lembut' : 'bg-kabut-100 text-kabut-700 hover:bg-hutan-50'"
                                    x-bind:aria-selected="set === '{{ $set->code }}'" x-on:click="set = '{{ $set->code }}'">{{ $set->name }}</button>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Waktu makan">
                        @foreach (MenuOption::MEALS as $meal => $label)
                            <button type="button" role="tab" class="rounded-none border px-4 py-2 text-sm font-semibold transition"
                                    x-bind:class="meal === '{{ $meal }}' ? 'border-emas-500 bg-emas-50 text-emas-800' : 'border-kabut-200 text-kabut-600 hover:border-emas-400'"
                                    x-bind:aria-selected="meal === '{{ $meal }}'" x-on:click="meal = '{{ $meal }}'">{{ $label }}</button>
                        @endforeach
                    </div>

                    @foreach ($menuSets as $set)
                        @foreach (MenuOption::MEALS as $meal => $label)
                            @php
                                $options = $set->options->where('meal', $meal);
                            @endphp
                            <div x-show="set === '{{ $set->code }}' && meal === '{{ $meal }}'" x-cloak role="tabpanel" class="mt-6">
                                @if ($options->isEmpty())
                                    <p class="rounded-kartu bg-kabut-50 p-6 text-kabut-600">Pilihan {{ strtolower($label) }} untuk {{ $set->name }} akan diinformasikan oleh tim kami.</p>
                                @else
                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                        @foreach ($options as $option)
                                            <div class="rounded-kartu bg-kabut-50 p-5 ring-1 ring-kabut-200">
                                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emas-700">{{ $option->label }}</p>
                                                <ul class="mt-3 space-y-1 text-sm capitalize text-kabut-800">
                                                    @foreach ($option->items as $dish)
                                                        <li>{{ $dish }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($meal === 'coffee_break')
                                        <p class="mt-3 text-sm text-kabut-600">Untuk coffee break pilih {{ $set->coffee_break_choices }} macam menu.</p>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
