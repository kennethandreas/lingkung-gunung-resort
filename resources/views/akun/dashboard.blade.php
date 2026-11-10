@use('App\Models\CustomerAccount')
@use('App\Services\PaymentService')

@php
    // Booking yang sudah tampil di "Perlu tindakan" tidak diulang di "Aktivitas terbaru"
    $recentOther = $recent->reject(fn ($b) => $actionNeeded->contains('id', $b->id));
@endphp

<x-layouts.akun title="Akun Saya" heading="Ringkasan" lead="Booking Anda, tagihan yang perlu dibayar, dan perjalanan berikutnya.">
    {{-- 1. Perlu tindakan (paling penting, paling atas) --}}
    @if ($actionNeeded->isNotEmpty())
        <section aria-labelledby="judul-tindakan">
            <h2 id="judul-tindakan" class="label-kecil text-emas-800">Perlu tindakan ({{ $actionNeeded->count() }})</h2>
            <div class="mt-3 space-y-3">
                @foreach ($actionNeeded as $booking)
                    <x-akun.booking-row :booking="$booking" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- 2. Perjalanan berikutnya --}}
    <section @class(['mt-10' => $actionNeeded->isNotEmpty()]) aria-labelledby="judul-berikutnya">
        <h2 id="judul-berikutnya" class="label-kecil text-kabut-700">Perjalanan berikutnya</h2>
        @if ($next)
            <div class="mt-3 border border-kabut-300 bg-white">
                <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <div>
                        <p class="font-display text-3xl text-hutan-950">{{ $next->accommodationSummary() }}</p>
                        <p class="mt-2 text-kabut-700">
                            {{ $next->check_in->translatedFormat('l, d F Y') }} – {{ $next->check_out->translatedFormat('l, d F Y') }} · {{ $next->guests }} tamu
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-akun.status-badge :status="$next->status" />
                            <x-ui.badge variant="netral">{{ $next->paymentStatusLabel() }}</x-ui.badge>
                            @if ($next->check_in->isFuture())
                                <span class="text-sm text-kabut-600">{{ (int) today()->diffInDays($next->check_in) }} hari lagi</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @if ($nextCharge)
                            <x-ui.button variant="primary" :href="route('booking.show', $next)">Bayar {{ $nextCharge['type']->getLabel() }} {{ PaymentService::rupiah($nextCharge['amount']) }}</x-ui.button>
                        @elseif ($next->status->isConfirmed())
                            <x-ui.button variant="primary" :href="route('booking.show', $next)" icon="heroicon-o-qr-code">Lihat e-ticket</x-ui.button>
                        @endif
                        <x-ui.button variant="outline" :href="route('akun.booking.show', $next->code)">Detail</x-ui.button>
                    </div>
                </div>
                <div class="border-t border-kabut-200 p-4 sm:p-6">
                    <x-booking.langkah :booking="$next" class="!border-0" />
                </div>
            </div>
        @else
            <x-akun.empty-state class="mt-3" icon="heroicon-o-map" title="Belum ada perjalanan yang akan datang" text="Booking yang Anda buat di website otomatis muncul di sini, lengkap dengan status pembayaran dan e-ticket.">
                <x-ui.button variant="primary" :href="route('pesan')">Cek ketersediaan</x-ui.button>
                <x-ui.button variant="outline" :href="route('akomodasi.index')">Lihat akomodasi</x-ui.button>
            </x-akun.empty-state>
        @endif
    </section>

    {{-- 3. Pintasan --}}
    <section class="mt-10 grid gap-px border border-kabut-300 bg-kabut-300 sm:grid-cols-3" aria-label="Pintasan akun">
        <a href="{{ route('akun.booking.index') }}" class="group bg-white p-5 transition-colors hover:bg-kabut-50">
            <p class="label-kecil text-kabut-600">Booking akan datang</p>
            <p class="mt-2 font-display text-3xl text-hutan-950">{{ $upcomingCount }}</p>
            <p class="mt-1 text-sm text-hutan-800 group-hover:underline">Semua booking →</p>
        </a>
        <a href="{{ route('akun.tersimpan') }}" class="group bg-white p-5 transition-colors hover:bg-kabut-50">
            <p class="label-kecil text-kabut-600">Akomodasi tersimpan</p>
            <p class="mt-2 font-display text-3xl text-hutan-950">{{ $savedCount }}</p>
            <p class="mt-1 text-sm text-hutan-800 group-hover:underline">Lihat daftar →</p>
        </a>
        <a href="{{ route('akun.profil') }}" class="group bg-white p-5 transition-colors hover:bg-kabut-50">
            <p class="label-kecil text-kabut-600">Email akun</p>
            <p class="mt-2 flex items-center gap-1.5 truncate font-semibold text-hutan-950">
                @if (CustomerAccount::verificationRequired())<x-heroicon-s-check-badge class="size-5 shrink-0 text-sukses-600" aria-hidden="true" /><span class="sr-only">Terverifikasi:</span>@endif
                {{ $account->email }}
            </p>
            <p class="mt-1 text-sm text-hutan-800 group-hover:underline">Ubah profil →</p>
        </a>
    </section>

    {{-- 4. Aktivitas terbaru --}}
    @if ($recent->isEmpty() || $recentOther->isNotEmpty())
    <section class="mt-10" aria-labelledby="judul-terbaru">
        <div class="flex items-end justify-between gap-4">
            <h2 id="judul-terbaru" class="label-kecil text-kabut-700">Aktivitas terbaru</h2>
            @if ($recent->isNotEmpty())
                <a href="{{ route('akun.booking.index') }}" class="tautan-panah text-hutan-900">Semua booking</a>
            @endif
        </div>

        @if ($recentOther->isNotEmpty())
            <div class="mt-3 space-y-3">
                @foreach ($recentOther as $booking)
                    <x-akun.booking-row :booking="$booking" />
                @endforeach
            </div>
        @else
            <p class="mt-3 border border-kabut-300 bg-white p-5 text-sm text-kabut-700">
                Belum ada aktivitas. Pernah memesan lewat telepon/WhatsApp atau sebelum punya akun? Tambahkan di halaman
                <a href="{{ route('akun.booking.index') }}#tautkan" class="font-semibold text-hutan-800 underline underline-offset-4">Booking Saya</a>.
            </p>
        @endif
    </section>
    @endif
</x-layouts.akun>
