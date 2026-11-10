{{--
    Layout halaman publik (tahap ux-redesign, lihat docs/AUDIT-UX.md).
    - Menu berlabel jelas (Menginap, Acara & Rombongan, Aktivitas, Kuliner, Galeri, Lokasi & Kontak) + satu tombol "Pesan".
    - Dropdown teks sederhana (hover/klik/keyboard, Esc menutup). Menu HP: akordeon.
    - title / description unik per halaman. transparentHeader: header transparan di atas hero foto.
--}}
@props([
    'title' => null,
    'description' => 'Resort dan adventure camp 850 MDPL di Caringin, Bogor, berbatasan dengan Taman Nasional Gunung Gede Pangrango. Glamping, kamar, tenda, outbound, dan paket rombongan. Pesan langsung tanpa komisi OTA.',
    'transparentHeader' => false,
    'mobileBar' => true,
    'ogImage' => null,
])

@use('App\Models\Setting')
@use('App\Models\UnitType')
@use('App\Support\ResortContact')
@use('App\Support\WhatsApp')

@php
    $stayLinks = UnitType::query()->active()->ordered()->get(['slug', 'marketing_name'])
        ->map(fn ($type) => [$type->marketing_name, route('akomodasi.show', $type->slug)])->all();
    $nav = [
        ['key' => 'menginap', 'label' => 'Menginap', 'href' => route('akomodasi.index'), 'active' => ['akomodasi.*'],
            'links' => [...$stayLinks, ['Bandingkan semua harga', route('akomodasi.index').'#bandingkan']]],
        ['key' => 'acara', 'label' => 'Acara & Rombongan', 'href' => route('gather'), 'active' => ['gather', 'rancang-paket', 'proposal.*'],
            'links' => [['Paket fullboard rombongan', route('gather').'#paket'], ['Meeting & aula', route('gather').'#meeting'], ['Hitung estimasi biaya', route('rancang-paket')], ['Program sekolah & anak', route('learn')]]],
        ['key' => 'aktivitas', 'label' => 'Aktivitas', 'href' => route('play'), 'active' => ['play', 'learn'],
            'links' => [['Petualangan', route('play').'#adventure'], ['Budaya & alam', route('play').'#eco-culture'], ['Daftar harga aktivitas', route('play').'#aktivitas'], ['Sanggar Alam untuk anak', route('learn').'#sanggar-alam']]],
        ['key' => 'kuliner', 'label' => 'Kuliner', 'href' => route('taste'), 'active' => ['taste'], 'links' => []],
        ['key' => 'galeri', 'label' => 'Galeri', 'href' => route('galeri'), 'active' => ['galeri'], 'links' => []],
        ['key' => 'kontak', 'label' => 'Lokasi & Kontak', 'href' => route('kontak'), 'active' => ['kontak', 'rute'],
            'links' => [['Kontak & FAQ', route('kontak')], ['Rute & akses', route('rute')], ['Kirim pertanyaan', route('kontak').'#kirim-pertanyaan']]],
    ];
    $pageTitle = $title ? "{$title} · Lingkung Gunung" : 'Lingkung Gunung Adventure Camp Bogor · Resort & Camping di Kaki Gunung Gede Pangrango';
    $account = auth('customer')->user();
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-pt-20">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#1a3526">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/ikon-512.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Lingkung Gunung Adventure Camp Bogor">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/resort/glamping-udara-1600.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    {{ $head ?? '' }}
    <script>
        document.documentElement.classList.add('js');
        // Transisi antarhalaman yang dibatalkan browser (mis. redirect setelah kirim form) jangan jadi error di console
        ['pageswap', 'pagereveal'].forEach((type) => addEventListener(type, (e) => {
            const vt = e.viewTransition;
            if (vt) [vt.ready, vt.finished, vt.updateCallbackDone].forEach((p) => p && p.catch(() => {}));
        }));
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <a href="#konten" class="sr-only z-[60] bg-hutan-900 px-4 py-2 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Lompat ke konten</a>

    <header
        x-data="{
            scrolled: false, menu: false, open: null, closeTimer: null, sub: null,
            show(key) { clearTimeout(this.closeTimer); this.open = key },
            hideSoon() { clearTimeout(this.closeTimer); this.closeTimer = setTimeout(() => this.open = null, 160) },
            get solid() { return this.scrolled || this.menu || this.open !== null },
        }"
        x-init="scrolled = window.scrollY > 24"
        x-on:scroll.window.passive="scrolled = window.scrollY > 24"
        x-on:keydown.escape.window="open = null"
        @class(['vt-header fixed inset-x-0 top-0 z-50 border-b transition-colors duration-300', 'border-kabut-300/70 bg-kabut-50' => ! $transparentHeader])
        @if ($transparentHeader) x-bind:class="solid ? 'border-kabut-300/70 bg-kabut-50' : 'border-white/15 bg-transparent'" @endif
    >
        <div class="container-lg flex h-18 items-center justify-between gap-6">
            <a href="{{ url('/') }}" aria-label="Lingkung Gunung Adventure Camp Bogor, kembali ke beranda" class="shrink-0">
                @if ($transparentHeader)
                    <x-ui.logo light x-show="! solid" />
                    <x-ui.logo x-show="solid" x-cloak />
                @else
                    <x-ui.logo />
                @endif
            </a>

            <nav class="hidden xl:block" aria-label="Menu utama" x-on:mouseleave="hideSoon()">
                <ul class="flex items-center">
                    @foreach ($nav as $item)
                        @php
                            $isActive = request()->routeIs(...$item['active']);
                        @endphp
                        <li class="relative" x-on:mouseenter="{{ $item['links'] ? "show('{$item['key']}')" : 'open = null' }}">
                            <div class="flex items-center">
                                <a href="{{ $item['href'] }}" @if ($isActive) aria-current="page" @endif
                                   @class(['nav-tautan block whitespace-nowrap px-3 py-6 text-[0.7rem] font-semibold tracking-[0.12em] uppercase transition-colors', 'text-kabut-900 hover:text-hutan-700' => ! $transparentHeader])
                                   @if ($transparentHeader) x-bind:class="solid ? 'text-kabut-900 hover:text-hutan-700' : 'text-white hover:text-kabut-200'" @endif>{{ $item['label'] }}</a>
                                @if ($item['links'])
                                    <button type="button" class="-ml-2 mr-1 flex size-6 items-center justify-center" aria-controls="menu-{{ $item['key'] }}" aria-label="Buka submenu {{ $item['label'] }}"
                                            x-bind:aria-expanded="open === '{{ $item['key'] }}'"
                                            x-on:click="open = open === '{{ $item['key'] }}' ? null : '{{ $item['key'] }}'; if (open) $nextTick(() => document.querySelector('#menu-{{ $item['key'] }} a')?.focus())"
                                            @if ($transparentHeader) x-bind:class="solid ? 'text-kabut-600' : 'text-white/80'" @else class="text-kabut-600" @endif>
                                        <x-heroicon-m-chevron-down class="size-3.5 transition duration-300" x-bind:class="open === '{{ $item['key'] }}' && 'rotate-180'" aria-hidden="true" />
                                    </button>
                                @endif
                            </div>
                            @if ($item['links'])
                                <div id="menu-{{ $item['key'] }}" x-show="open === '{{ $item['key'] }}'" x-cloak
                                     x-transition:enter="transition duration-200 ease-alam" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     x-on:focusout="if (! $el.contains($event.relatedTarget)) hideSoon()"
                                     class="absolute left-0 top-full w-72 border border-kabut-300 bg-white py-2 shadow-angkat">
                                    <ul>
                                        @foreach ($item['links'] as [$text, $url])
                                            <li><a href="{{ $url }}" class="group flex items-center justify-between px-5 py-2.5 text-sm text-kabut-800 transition-colors hover:bg-kabut-50 hover:text-hutan-800 focus-visible:bg-kabut-50">{{ $text }} <x-heroicon-m-arrow-right class="size-3.5 opacity-0 transition group-hover:opacity-100" aria-hidden="true" /></a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-1 sm:gap-3">
                <a href="{{ $account ? route('akun.dashboard') : route('akun.masuk') }}"
                   @class(['label-kecil hidden items-center gap-2 whitespace-nowrap px-2 py-2 transition-colors lg:inline-flex','text-kabut-900 hover:text-hutan-700' => ! $transparentHeader])
                   @if ($transparentHeader) x-bind:class="solid ? 'text-kabut-900 hover:text-hutan-700' : 'text-white hover:text-kabut-200'" @endif>
                    <x-heroicon-o-user class="size-4" aria-hidden="true" />
                    {{ $account ? 'Akun Saya' : 'Masuk' }}
                </a>
                <x-ui.button variant="primary" size="md" :href="route('pesan')" class="max-sm:hidden">Pesan</x-ui.button>
                <button type="button" @class(['flex size-11 items-center justify-center xl:hidden', 'text-hutan-950' => ! $transparentHeader])
                        @if ($transparentHeader) x-bind:class="solid ? 'text-hutan-950' : 'text-white'" @endif
                        x-on:click="menu = ! menu" x-bind:aria-expanded="menu" aria-controls="menu-hp" aria-label="Buka menu">
                    <x-heroicon-o-bars-2 class="size-7" x-show="! menu" aria-hidden="true" />
                    <x-heroicon-o-x-mark class="size-7" x-show="menu" x-cloak aria-hidden="true" />
                </button>
            </div>
        </div>

        {{-- Menu HP/tablet (akordeon). Tinggi eksplisit agar tidak bergantung pada tinggi header. --}}
        <div id="menu-hp" x-show="menu" x-cloak x-transition.opacity.duration.200ms x-trap.noscroll="menu"
             class="absolute inset-x-0 top-full h-[calc(100dvh-4.5rem)] overflow-y-auto overscroll-contain bg-kabut-50 xl:hidden">
            <nav class="container-lg py-4" aria-label="Menu utama (HP)">
                <ul class="menu-giliran divide-y divide-kabut-300/70 border-b border-kabut-300/70">
                    @foreach ($nav as $item)
                        <li style="--i: {{ $loop->index }}">
                            @if ($item['links'])
                                <button type="button" class="flex w-full items-center justify-between py-4 text-left" x-on:click="sub = sub === '{{ $item['key'] }}' ? null : '{{ $item['key'] }}'" x-bind:aria-expanded="sub === '{{ $item['key'] }}'">
                                    <span class="font-display text-2xl text-hutan-950">{{ $item['label'] }}</span>
                                    <x-heroicon-m-plus class="size-5 text-kabut-600 transition duration-300" x-bind:class="sub === '{{ $item['key'] }}' && 'rotate-45'" aria-hidden="true" />
                                </button>
                                <div x-show="sub === '{{ $item['key'] }}'" x-collapse x-cloak>
                                    <ul class="pb-4">
                                        <li><a href="{{ $item['href'] }}" class="block py-2 pl-4 font-semibold text-hutan-800" x-on:click="menu = false">Lihat halaman {{ $item['label'] }}</a></li>
                                        @foreach ($item['links'] as [$text, $url])
                                            <li><a href="{{ $url }}" class="block py-2 pl-4 text-kabut-800" x-on:click="menu = false">{{ $text }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <a href="{{ $item['href'] }}" class="flex items-center justify-between py-4" x-on:click="menu = false">
                                    <span class="font-display text-2xl text-hutan-950">{{ $item['label'] }}</span>
                                    <x-heroicon-m-arrow-right class="size-5 text-kabut-600" aria-hidden="true" />
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6 grid gap-3">
                    <x-ui.button variant="primary" size="lg" :href="route('pesan')" x-on:click="menu = false">Pesan Sekarang</x-ui.button>
                    <x-ui.button variant="outline" size="lg" icon="heroicon-o-user" :href="$account ? route('akun.dashboard') : route('akun.masuk')">{{ $account ? 'Akun Saya' : 'Masuk / Daftar' }}</x-ui.button>
                </div>
                <p class="mt-6 text-sm text-kabut-700">Butuh bantuan? <a href="{{ WhatsApp::link() }}" target="_blank" rel="noopener" class="font-semibold text-hutan-800 underline underline-offset-4">WhatsApp {{ WhatsApp::display() }}</a></p>
            </nav>
        </div>
    </header>

    <main id="konten" @class(['pt-18' => ! $transparentHeader])>
        {{ $slot }}
    </main>

    <footer class="bg-hutan-950 text-kabut-300 {{ $mobileBar ? 'pb-24 md:pb-0' : '' }}">
        <div class="container-lg grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
            <div>
                <x-ui.logo light />
                <p class="mt-6 max-w-xs text-sm leading-relaxed">Resort & adventure camp 850 MDPL, berbatasan langsung dengan Taman Nasional Gunung Gede Pangrango.</p>
                @if ($socials = ResortContact::socials())
                    <ul class="mt-6 flex flex-wrap gap-2" aria-label="Media sosial resmi">
                        @foreach ($socials as $social)
                            <li>
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="flex size-10 items-center justify-center border border-white/20 transition-colors hover:border-white hover:text-white" aria-label="{{ $social['label'] }} {{ $social['display'] }}" title="{{ $social['label'] }}">
                                    <x-dynamic-component :component="$social['icon']" class="size-[1.125rem]" aria-hidden="true" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <nav aria-label="Jelajahi">
                <h2 class="label-kecil text-white">Jelajahi</h2>
                <ul class="mt-5 space-y-2.5 text-sm">
                    @foreach ($nav as $item)
                        <li><a class="transition-colors hover:text-white" href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a class="transition-colors hover:text-white" href="{{ route('artikel.index') }}">Artikel</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ route('ulasan.index') }}">Ulasan tamu</a></li>
                </ul>
            </nav>
            <nav aria-label="Informasi tamu">
                <h2 class="label-kecil text-white">Informasi Tamu</h2>
                <ul class="mt-5 space-y-2.5 text-sm">
                    <li><a class="transition-colors hover:text-white" href="{{ route('pesan') }}">Cara memesan</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ route('halaman', 'mekanisme-pembayaran') }}">Mekanisme pembayaran</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ route('kontak') }}#faq">Pertanyaan umum (FAQ)</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ route('halaman', 'syarat-dan-ketentuan') }}">Syarat & ketentuan</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ route('halaman', 'kebijakan-privasi') }}">Kebijakan privasi</a></li>
                    <li><a class="transition-colors hover:text-white" href="{{ $account ? route('akun.booking.index') : route('akun.masuk') }}">Cek pesanan saya</a></li>
                </ul>
            </nav>
            <div>
                <h2 class="label-kecil text-white">Hubungi</h2>
                <ul class="mt-5 space-y-2.5 text-sm">
                    <li><a class="inline-flex items-center gap-2 transition-colors hover:text-white" href="{{ WhatsApp::link() }}" target="_blank" rel="noopener"><x-ui.icon-whatsapp class="size-4" /> {{ WhatsApp::display() }}</a></li>
                    @if ($phone = Setting::get('contact_phone'))
                        <li><a class="inline-flex items-center gap-2 transition-colors hover:text-white" href="tel:{{ preg_replace('/\D/', '', $phone) }}"><x-heroicon-o-phone class="size-4" aria-hidden="true" /> {{ $phone }}</a></li>
                    @endif
                    @if ($email = ResortContact::email())
                        <li><a class="inline-flex items-center gap-2 transition-colors hover:text-white" href="mailto:{{ $email }}"><x-heroicon-o-envelope class="size-4" aria-hidden="true" /> {{ $email }}</a></li>
                    @endif
                </ul>
                <address class="mt-5 text-sm not-italic leading-relaxed">{{ Setting::get('address') }}</address>
                <p class="mt-4 flex gap-5 text-sm">
                    <a class="tautan-panah text-white" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(Setting::get('google_maps_query')) }}" target="_blank" rel="noopener">Google Maps</a>
                    <a class="tautan-panah text-white" href="https://waze.com/ul?q={{ urlencode(Setting::get('waze_query')) }}" target="_blank" rel="noopener">Waze</a>
                </p>
            </div>
        </div>
        <div class="container-lg flex flex-col gap-2 border-t border-white/10 py-6 text-xs text-kabut-400 sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} PT Lingkung Gunung Sejahtera · Pembayaran non-tunai</p>
            <p>Foto: dokumentasi resmi resort</p>
        </div>
    </footer>

    @if ($mobileBar)
        <x-ui.mobile-bar :booking-url="route('pesan')" />
    @endif

    {{-- Notifikasi singkat (mis. "disimpan ke akun", "sudah keluar"). Halaman akun menampilkannya sendiri. --}}
    @if (session('status') && ! request()->routeIs('akun.*'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition.opacity
             class="fixed inset-x-4 bottom-24 z-50 mx-auto max-w-md md:bottom-6">
            <x-ui.alert variant="sukses" class="pr-12 shadow-angkat">{{ session('status') }}</x-ui.alert>
            <button type="button" class="absolute right-3 top-3 flex size-8 items-center justify-center text-sukses-800 hover:bg-sukses-100" x-on:click="show = false" aria-label="Tutup pesan">
                <x-heroicon-m-x-mark class="size-5" aria-hidden="true" />
            </button>
        </div>
    @endif

    @livewireScripts
</body>
</html>
