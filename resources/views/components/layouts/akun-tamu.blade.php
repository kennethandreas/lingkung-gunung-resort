{{--
    Layout halaman masuk / daftar / lupa password / verifikasi.
    Bila tamu datang dari wizard pemesanan, panel "Pesanan Anda menunggu" tampil (tetap ada saat pindah Masuk ↔ Daftar).
--}}
@props(['title', 'heading', 'lead' => null, 'eyebrow' => 'Akun Lingkung Gunung'])

@php
    $pending = \App\Support\PendingBooking::fromSession();
@endphp

<x-layouts.public :title="$title" :mobile-bar="false">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="bg-kabut-50 py-12 sm:py-16">
        <div @class(['container-lg grid gap-10', 'lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)] lg:justify-center lg:gap-16' => $pending, 'max-w-md' => ! $pending])>
            <div class="w-full">
                <p class="label-kecil flex items-center gap-3 text-emas-700"><span class="h-px w-8 bg-emas-600" aria-hidden="true"></span>{{ $pending ? 'Langkah 3 dari 4 · Pemesanan' : $eyebrow }}</p>
                <h1 class="mt-4 font-display text-display-md text-balance text-hutan-950">{{ $heading }}</h1>
                @if ($lead)
                    <p class="mt-3 text-kabut-700">{{ $lead }}</p>
                @endif

                <div class="mt-8 border border-kabut-300 bg-white p-6 sm:p-8">
                    @if (session('status'))
                        <x-ui.alert variant="sukses" class="mb-6">{{ session('status') }}</x-ui.alert>
                    @endif
                    @if (session('notice') && ! $pending)
                        <x-ui.alert variant="info" class="mb-6">{{ session('notice') }}</x-ui.alert>
                    @endif

                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="mt-6 text-sm text-kabut-700">{{ $footer }}</div>
                @endisset
            </div>

            @if ($pending)
                <aside class="h-fit border-2 border-hutan-900 bg-white p-6 sm:p-8 lg:sticky lg:top-24" aria-labelledby="judul-pesanan-menunggu">
                    <p class="label-kecil text-emas-700">Pesanan Anda menunggu</p>
                    <h2 id="judul-pesanan-menunggu" class="mt-3 font-display text-2xl text-hutan-950">Masuk atau daftar, lalu kami bawa Anda kembali ke pesanan ini</h2>
                    <dl class="mt-6 divide-y divide-kabut-200 border-y border-kabut-200 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-kabut-600">Tanggal</dt><dd class="text-right font-semibold text-kabut-900">{{ $pending['check_in']->translatedFormat('D, d M') }} – {{ $pending['check_out']->translatedFormat('D, d M Y') }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-kabut-600">Lama</dt><dd class="font-semibold text-kabut-900">{{ $pending['nights'] }} malam · {{ $pending['guests'] }} tamu</dd></div>
                        @if ($pending['units'])
                            <div class="flex justify-between gap-4 py-3"><dt class="text-kabut-600">Akomodasi</dt><dd class="text-right font-semibold text-kabut-900">{{ implode(', ', $pending['units']) }}</dd></div>
                        @endif
                    </dl>
                    <ul class="mt-5 space-y-2 text-sm text-kabut-700">
                        <li class="flex gap-2"><x-heroicon-o-check class="mt-0.5 size-4 shrink-0 text-hutan-700" aria-hidden="true" /> Akun gratis, dipakai untuk melihat status booking dan e-ticket kapan saja.</li>
                        <li class="flex gap-2"><x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-hutan-700" aria-hidden="true" /> Unit belum ditahan. Ketersediaan & harga diperiksa ulang saat Anda kembali.</li>
                    </ul>
                    <a href="{{ \Illuminate\Support\Str::replace('lanjut=1', 'lanjut=0', $pending['url']) }}" class="tautan-panah mt-6 text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Kembali ubah pilihan</a>
                </aside>
            @endif
        </div>
    </section>
</x-layouts.public>
