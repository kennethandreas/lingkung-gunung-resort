{{-- Layout area akun pelanggan: sapaan, menu akun (samping di desktop, geser di HP), isi halaman. --}}
@props(['title', 'heading' => null, 'lead' => null])

@php
    $account = auth('customer')->user();
    $menu = [
        ['label' => 'Ringkasan', 'route' => 'akun.dashboard', 'active' => 'akun.dashboard', 'icon' => 'heroicon-o-home'],
        ['label' => 'Booking Saya', 'route' => 'akun.booking.index', 'active' => 'akun.booking.*', 'icon' => 'heroicon-o-calendar-days'],
        ['label' => 'Tersimpan', 'route' => 'akun.tersimpan', 'active' => 'akun.tersimpan', 'icon' => 'heroicon-o-heart'],
        ['label' => 'Profil Saya', 'route' => 'akun.profil', 'active' => 'akun.profil', 'icon' => 'heroicon-o-user-circle'],
    ];
    $menu = array_map(fn ($item) => $item + ['current' => request()->routeIs($item['active'])], $menu);
    $firstName = \Illuminate\Support\Str::of($account->name)->explode(' ')->first();
@endphp

<x-layouts.public :title="$title" :mobile-bar="false">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="min-h-[70vh] bg-kabut-50 pb-16 pt-8 sm:pt-12">
        <div class="container-lg">
            <p class="label-kecil flex items-center gap-3 text-emas-700"><span class="h-px w-8 bg-emas-600" aria-hidden="true"></span>Akun · Halo, {{ $firstName }}</p>
            <h1 class="mt-3 font-display text-display-md text-hutan-950">{{ $heading ?? $title }}</h1>
            @if ($lead)
                <p class="mt-2 max-w-2xl text-kabut-700">{{ $lead }}</p>
            @endif

            <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_1fr]">
                <nav aria-label="Menu akun" class="min-w-0 lg:sticky lg:top-24 lg:self-start">
                    <ul class="-mx-4 flex overflow-x-auto border-b border-kabut-300/70 px-4 lg:mx-0 lg:flex-col lg:overflow-visible lg:border-b-0 lg:border-l lg:px-0">
                        @foreach ($menu as $item)
                            <li class="shrink-0">
                                <a href="{{ route($item['route']) }}" @if ($item['current']) aria-current="page" @endif
                                   class="{{ $item['current'] ? 'border-hutan-900 text-hutan-950' : 'border-transparent text-kabut-700 hover:text-hutan-900' }} -mb-px flex items-center gap-2.5 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition-colors lg:-ml-px lg:mb-0 lg:border-b-0 lg:border-l-2">
                                    <x-dynamic-component :component="$item['icon']" class="size-5 shrink-0" aria-hidden="true" />
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                        <li class="shrink-0 lg:mt-4 lg:border-t lg:border-kabut-200 lg:pt-2">
                            <form method="POST" action="{{ route('akun.keluar') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap px-4 py-3 text-sm font-semibold text-kabut-700 transition-colors hover:text-bahaya-700">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-5 shrink-0" aria-hidden="true" />
                                    Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </nav>

                <div class="min-w-0">
                    @if (session('status'))
                        <x-ui.alert variant="sukses" class="mb-6">{{ session('status') }}</x-ui.alert>
                    @endif
                    @if (session('error'))
                        <x-ui.alert variant="peringatan" class="mb-6">{{ session('error') }}</x-ui.alert>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
