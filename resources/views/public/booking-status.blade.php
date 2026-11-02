@use('App\Enums\BookingStatus')
@use('App\Services\PaymentService')
@use('App\Support\WhatsApp')

@php
    $status = $booking->status;
    $clientKey = config('services.midtrans.client_key');
    $canPayOnline = $charge && filled($clientKey) && filled(config('services.midtrans.server_key'));
    $autoPay = $canPayOnline && request()->boolean('bayar');
    $holdActive = $status === BookingStatus::Pending && $booking->hold_expires_at?->isFuture();
    $waMessage = "Halo Lingkung Gunung, saya ingin bertanya tentang booking {$booking->code}.";
    $steps = ['Tanggal & tamu', 'Akomodasi', 'Data pemesan', 'Pembayaran'];
    $badgeVariant = match ($status) {
        BookingStatus::Pending => 'peringatan',
        BookingStatus::DpPaid => 'info',
        BookingStatus::FullyPaid, BookingStatus::CheckedIn => 'sukses',
        default => 'bahaya',
    };
    $ownedByViewer = $booking->customer_account_id && auth('customer')->id() === $booking->customer_account_id;
@endphp

<x-layouts.public :title="'Booking '.$booking->code" description="Status pemesanan Lingkung Gunung." :mobile-bar="false">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if ($canPayOnline)
            <script src="{{ config('services.midtrans.snap_js') }}" data-client-key="{{ $clientKey }}" defer></script>
        @endif
    </x-slot:head>

    <section class="bg-kabut-50 pb-20 pt-8 sm:pt-12"
        x-data="{
            paying: false,
            message: null,
            closed: false,
            async pay() {
                this.paying = true; this.message = null; this.closed = false;
                try {
                    const res = await fetch('{{ route('booking.pay', $booking) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (! res.ok) { this.message = data.message || 'Pembayaran belum bisa dimulai.'; this.paying = false; return; }
                    const open = () => window.snap.pay(data.token, {
                        onSuccess: () => { window.location.href = '{{ route('booking.show', $booking) }}?status=berhasil' },
                        onPending: () => { window.location.href = '{{ route('booking.show', $booking) }}?status=menunggu' },
                        onError: () => { this.message = 'Pembayaran gagal diproses. Silakan coba lagi atau pilih metode lain.'; this.paying = false },
                        onClose: () => { this.paying = false; this.closed = true },
                    });
                    window.snap ? open() : window.addEventListener('load', open, { once: true });
                } catch (e) { this.message = 'Koneksi bermasalah. Periksa internet Anda lalu coba lagi.'; this.paying = false; }
            },
        }"
        @if ($autoPay) x-init="$nextTick(() => pay())" @endif
    >
        <div class="container-lg max-w-5xl">
            @if ($status === BookingStatus::Pending)
                <x-ui.steps :steps="$steps" :current="4" class="mb-8" />
            @endif

            {{-- Kembali dari Midtrans --}}
            @if (request('status') === 'berhasil' && $status === BookingStatus::Pending)
                <x-ui.alert variant="sukses" title="Pembayaran diterima Midtrans" class="mb-6">Kami sedang memverifikasi pembayaran Anda. Halaman ini diperbarui otomatis dalam beberapa detik.</x-ui.alert>
                <script>setTimeout(() => window.location.reload(), 5000)</script>
            @elseif (request('status') === 'menunggu' && $status === BookingStatus::Pending)
                <x-ui.alert variant="info" title="Menunggu pembayaran Anda" class="mb-6">Selesaikan pembayaran sesuai instruksi Midtrans (mis. transfer ke nomor virtual account) sebelum batas waktu di bawah. Status berubah otomatis setelah dibayar.</x-ui.alert>
            @elseif (request('status') === 'berhasil' && $status->isConfirmed())
                <x-ui.alert variant="sukses" title="Pembayaran terverifikasi" class="mb-6">Terima kasih! Bukti pembayaran dan e-ticket dikirim ke {{ $booking->customer->email ?: 'email Anda' }}.</x-ui.alert>
            @endif

            {{-- ===================== STATUS UTAMA & AKSI ===================== --}}
            <div class="border border-kabut-300 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-kabut-200 p-6 sm:p-8">
                    <div>
                        <p class="label-kecil text-kabut-600">Kode booking</p>
                        <h1 class="mt-1 font-display text-display-md tracking-wide text-hutan-950">{{ $booking->code }}</h1>
                        <p class="mt-1 text-sm text-kabut-700">a.n. {{ $booking->customer->name }} · {{ $booking->accommodationSummary() }}</p>
                    </div>
                    <x-ui.badge :variant="$badgeVariant" class="!px-3 !py-1.5">{{ $status->getLabel() }}</x-ui.badge>
                </div>

                <div class="p-6 sm:p-8">
                    @if ($holdActive)
                        <div class="border-l-2 border-peringatan-600 bg-peringatan-50 px-5 py-4"
                             x-data="{ end: {{ $booking->hold_expires_at->getTimestampMs() }}, now: Date.now(), get left() { return Math.max(0, Math.floor((this.end - this.now) / 1000)) } }"
                             x-init="setInterval(() => { now = Date.now(); if (left === 0) window.location.reload() }, 1000)">
                            <p class="font-semibold text-peringatan-800">Selesaikan pembayaran dalam <span class="tabular-nums" x-text="String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0')">--:--</span></p>
                            <p class="mt-1 text-sm text-peringatan-800">Unit kami tahan sampai {{ $booking->hold_expires_at->format('H:i') }} WIB. Setelah itu pesanan batal otomatis dan unit dilepas.</p>
                        </div>
                    @elseif ($status === BookingStatus::Expired || $status === BookingStatus::Pending)
                        {{-- Pending tanpa hold aktif = waktu bayar sudah lewat (penjadwal belum sempat mengubah status) --}}
                        <x-ui.alert variant="bahaya" title="Waktu pembayaran habis">Unit sudah dilepas karena pembayaran belum diterima. Tidak ada uang yang ditagih. Silakan buat pemesanan baru.</x-ui.alert>
                        <x-ui.button variant="primary" class="mt-4" :href="route('pesan', ['check_in' => $booking->check_in->toDateString(), 'check_out' => $booking->check_out->toDateString(), 'guests' => $booking->guests])">Pesan ulang tanggal yang sama</x-ui.button>
                    @elseif ($status === BookingStatus::Cancelled)
                        <x-ui.alert variant="bahaya" title="Booking dibatalkan">Hubungi kami bila ada pertanyaan tentang pembatalan atau pengembalian dana.</x-ui.alert>
                    @elseif ($status === BookingStatus::DpPaid)
                        <x-ui.alert variant="info" title="DP sudah diterima, unit Anda terkonfirmasi">Sisa pembayaran {{ PaymentService::rupiah($booking->outstanding()) }} paling lambat {{ $booking->due_date?->translatedFormat('l, d F Y') }}. E-ticket sudah bisa dipakai.</x-ui.alert>
                    @elseif ($status === BookingStatus::FullyPaid)
                        <x-ui.alert variant="sukses" title="Pembayaran lunas">E-ticket sudah dikirim ke email Anda. Sampai jumpa di Lingkung Gunung!</x-ui.alert>
                    @elseif ($status === BookingStatus::CheckedIn)
                        <x-ui.alert variant="sukses" title="Selamat menikmati">Anda sudah check-in. Semoga liburan Anda berkesan.</x-ui.alert>
                    @endif

                    <div x-show="message" x-cloak class="mt-4">
                        <x-ui.alert variant="bahaya" title="Pembayaran belum berhasil"><span x-text="message"></span></x-ui.alert>
                    </div>
                    <div x-show="closed" x-cloak class="mt-4">
                        <x-ui.alert variant="info" title="Jendela pembayaran ditutup">Pembayaran belum selesai. @if ($holdActive) Unit masih ditahan sampai {{ $booking->hold_expires_at->format('H:i') }} WIB. @endif Tekan tombol bayar untuk mencoba lagi atau memilih metode lain.</x-ui.alert>
                    </div>

                    @if ($charge)
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                            @if ($canPayOnline)
                                <x-ui.button variant="primary" size="lg" icon="heroicon-m-lock-closed" x-on:click="pay()" x-bind:disabled="paying">
                                    <span x-show="! paying"><span x-text="closed || message ? 'Coba bayar lagi' : 'Bayar'">Bayar</span> {{ $charge['type']->getLabel() }} {{ PaymentService::rupiah($charge['amount']) }}</span>
                                    <span x-show="paying" x-cloak>Membuka pembayaran…</span>
                                </x-ui.button>
                            @else
                                <x-ui.alert variant="info" class="flex-1" title="Bayar lewat transfer">Pembayaran online belum aktif. Transfer {{ PaymentService::rupiah($charge['amount']) }} ke {{ \App\Models\Setting::get('bank_name') }} {{ \App\Models\Setting::get('bank_account_number') }} a.n. {{ \App\Models\Setting::get('bank_account_name') }}, lalu konfirmasi via WhatsApp dengan menyebut kode booking.</x-ui.alert>
                            @endif
                            <x-ui.button variant="outline" size="lg" :href="WhatsApp::link($waMessage)" target="_blank" rel="noopener">Tanya via WhatsApp</x-ui.button>
                        </div>
                        @if ($canPayOnline)
                            <p class="mt-3 flex items-center gap-2 text-xs text-kabut-600"><x-heroicon-o-shield-check class="size-4" aria-hidden="true" /> Diproses aman oleh Midtrans: QRIS, virtual account bank, e-wallet.</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- ===================== ALUR BOOKING ===================== --}}
            <div class="mt-8">
                <h2 class="label-kecil mb-3 text-kabut-700">Tahapan booking Anda</h2>
                <x-booking.langkah :booking="$booking" />
            </div>

            {{-- ===================== RINCIAN & E-TICKET ===================== --}}
            <div class="mt-8 grid gap-8 md:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="border border-kabut-300 bg-white p-6 sm:p-8">
                    <h2 class="label-kecil text-kabut-700">Rincian pemesanan</h2>
                    <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-kabut-600">Check-in</dt><dd class="font-semibold text-kabut-900">{{ $booking->check_in->translatedFormat('l, d F Y') }}</dd></div>
                        <div><dt class="text-kabut-600">Check-out</dt><dd class="font-semibold text-kabut-900">{{ $booking->check_out->translatedFormat('l, d F Y') }}</dd></div>
                        <div><dt class="text-kabut-600">Lama menginap</dt><dd class="font-semibold text-kabut-900">{{ $booking->nights }} malam</dd></div>
                        <div><dt class="text-kabut-600">Jumlah tamu</dt><dd class="font-semibold text-kabut-900">{{ $booking->guests }} orang</dd></div>
                    </dl>
                    <ul class="mt-6 divide-y divide-kabut-200 border-y border-kabut-200 text-sm">
                        @foreach ($booking->items as $item)
                            <li class="flex justify-between gap-4 py-3"><span>{{ $item->description }} × {{ $item->qty }}</span><span class="font-medium tabular-nums">{{ PaymentService::rupiah($item->subtotal) }}</span></li>
                        @endforeach
                        @if ($booking->discount > 0)
                            <li class="flex justify-between gap-4 py-3 text-sukses-800"><span>Diskon promo</span><span class="tabular-nums">−{{ PaymentService::rupiah($booking->discount) }}</span></li>
                        @endif
                    </ul>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="font-semibold">Total</dt><dd class="text-lg font-semibold tabular-nums text-hutan-950">{{ PaymentService::rupiah($booking->total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-kabut-600">Sudah dibayar</dt><dd class="tabular-nums text-sukses-800">{{ PaymentService::rupiah($booking->paid_amount) }}</dd></div>
                        @if ($booking->outstanding() > 0 && ! $status->isFinal())
                            <div class="flex justify-between"><dt class="text-kabut-600">Sisa</dt><dd class="font-semibold tabular-nums">{{ PaymentService::rupiah($booking->outstanding()) }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="border border-kabut-300 bg-white p-6 text-center">
                    <h2 class="label-kecil text-kabut-700">E-ticket</h2>
                    @if ($hasETicket)
                        <div class="mx-auto mt-4 w-44 border border-kabut-200 bg-white p-2 [&_svg]:h-auto [&_svg]:w-full" role="img" aria-label="QR check-in booking {{ $booking->code }}">{!! $qr !!}</div>
                        <p class="mt-3 text-xs text-kabut-600">Tunjukkan QR ini kepada front office saat tiba.</p>
                        <x-ui.button variant="outline" size="sm" class="mt-4" :href="route('booking.eticket', $booking)" target="_blank" icon="heroicon-m-arrow-down-tray">Unduh PDF</x-ui.button>
                    @else
                        <div class="mx-auto mt-4 flex size-44 items-center justify-center bg-kabut-100 text-kabut-400">
                            <x-heroicon-o-qr-code class="size-16" aria-hidden="true" />
                        </div>
                        <p class="mt-3 text-xs text-kabut-600">E-ticket terbit setelah pembayaran pertama terverifikasi.</p>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3 border-t border-kabut-300/70 pt-6 text-sm text-kabut-700 sm:flex-row sm:items-center sm:justify-between">
                <p>Ada pertanyaan? <a class="font-semibold text-hutan-800 underline underline-offset-4" href="{{ WhatsApp::link($waMessage) }}" target="_blank" rel="noopener">Chat kami</a> dan sebutkan kode {{ $booking->code }}.</p>
                @if ($ownedByViewer)
                    <a href="{{ route('akun.booking.index') }}" class="tautan-panah text-hutan-900">Semua booking saya <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
                @else
                    <p>Simpan tautan halaman ini untuk memantau status booking.</p>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
