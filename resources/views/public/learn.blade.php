@use('App\Support\WhatsApp')

<x-layouts.public title="Learn: Outdoor Learning" description="Outing, LDKS, Pramuka, Supercamp, Leadership Camp, Diksar Mapala, Sanggar Alam, dan Bivak Survival di Lingkung Gunung, Bogor." transparent-header>
    <x-ui.page-hero eyebrow="Program Sekolah & Anak" title="Belajar di alam terbuka" lead="Outing, LDKS, Pramuka, Supercamp, Diksar Mapala, hingga Sanggar Alam untuk anak-anak mengenal budaya Sunda." image="images/resort/pramuka-upacara" alt="Upacara pramuka di lapangan Lingkung Gunung" />

    @foreach ($groups as $group => $programs)
        <section @class(['section', 'bg-kabut-50' => $loop->odd, 'bg-white' => $loop->even])>
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Outdoor learning destination" :title="$group" />
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($programs as $i => $program)
                        <x-ui.reveal :delay="($i % 4) * 80" :class="$group === 'Tailor-Made Program' ? 'sm:col-span-2 lg:col-span-4' : ''">
                            <x-ui.program-card :program="$program" />
                        </x-ui.reveal>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @if ($sanggar->isNotEmpty())
        <section class="section bg-hutan-50" id="sanggar-alam">
            <div class="container-lg">
                <x-ui.section-heading eyebrow="Paket kegiatan" :script="true" title="Sanggar Alam untuk anak-anak" lead="Ruang belajar imersif di kaki Gunung Gede Pangrango: mengenal budaya Sunda dan alam lewat pengalaman langsung, dari permainan tradisional, berkebun, hingga makan siang botram." />
                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    @foreach ($sanggar as $paket)
                        <div class="rounded-kartu bg-white p-7 shadow-lembut ring-1 ring-hutan-200">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="font-display text-2xl font-semibold text-hutan-900">{{ $paket->name }}</h3>
                                @if ($paket->schedule)
                                    <x-ui.badge variant="hutan" icon="heroicon-m-clock">{{ $paket->schedule }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-4 text-sm text-kabut-600"><span class="text-2xl font-bold text-hutan-900">Rp{{ number_format($paket->price, 0, ',', '.') }}</span> / {{ $paket->price_unit }}</p>
                            <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                                @foreach (array_map('trim', explode(',', (string) $paket->includes)) as $item)
                                    <li class="flex items-center gap-2 text-sm text-kabut-800"><x-heroicon-o-check-circle class="size-5 shrink-0 text-hutan-600" aria-hidden="true" /> {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section bg-hutan-950 text-white">
        <div class="container-lg flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
            <x-ui.section-heading :dark="true" eyebrow="Untuk sekolah, kampus & komunitas" title="Susun program sesuai tujuan pembelajaran Anda." />
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="accent" size="lg" :href="route('gather').'#paket'">Lihat paket fullboard</x-ui.button>
                <x-ui.button variant="light" size="lg" :href="WhatsApp::link('Halo, saya ingin konsultasi program edukasi/LDKS.')" target="_blank" rel="noopener">Konsultasi program</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
