@use('App\Enums\BookingStatus')
@use('App\Services\PaymentService')
@use('App\Support\WhatsApp')

<x-layouts.akun :title="'Booking '.$booking->code" :heading="$booking->accommodationSummary()">
    <a href="{{ route('akun.booking.index') }}" class="tautan-panah text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Semua booking</a>

    {{-- Status & aksi utama --}}
    <div @class(['mt-6 border bg-white', 'border-emas-500' => $charge, 'border-kabut-300' => ! $charge])>
        <div class="flex flex-wrap items-start justify-between gap-4 p-6 sm:p-8">
            <div>
                <p class="label-kecil text-kabut-600">Kode booking</p>
                <p class="mt-1 font-display text-2xl tracking-wide text-hutan-950">{{ $booking->code }}</p>
                <p class="mt-1 text-sm text-kabut-600">Dipesan {{ $booking->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-akun.status-badge :status="$booking->status" class="!px-3 !py-1.5" />
                <x-ui.badge variant="netral" class="!px-3 !py-1.5">{{ $booking->paymentStatusLabel() }}</x-ui.badge>
            </div>
        </div>

        @if ($charge)
            <div class="border-t border-emas-500/50 bg-emas-50 px-6 py-4 sm:px-8">
                <p class="text-emas-900"><strong>Langkah Anda:</strong> bayar {{ ['dp' => 'DP', 'settlement' => 'pelunasan', 'full' => 'lunas'][$charge['type']->value] }} <strong class="tabular-nums">{{ PaymentService::rupiah($charge['amount']) }}</strong>
                    @if ($booking->status === BookingStatus::Pending && $booking->hold_expires_at)
                        sebelum {{ $booking->hold_expires_at->translatedFormat('d M, H:i') }} WIB (unit sedang ditahan).
                    @elseif ($booking->due_date)
                        paling lambat {{ $booking->due_date->translatedFormat('l, d F Y') }}.
                    @endif
                </p>
            </div>
        @endif

        <div class="flex flex-wrap gap-3 border-t border-kabut-200 px-6 py-5 sm:px-8">
            @if ($charge)
                <x-ui.button variant="primary" :href="route('booking.show', $booking)" icon="heroicon-m-lock-closed">Bayar {{ $charge['type']->getLabel() }} {{ PaymentService::rupiah($charge['amount']) }}</x-ui.button>
            @endif
            @if ($booking->status->isConfirmed())
                <x-ui.button :variant="$charge ? 'outline' : 'primary'" :href="route('booking.show', $booking)" icon="heroicon-o-qr-code">Lihat e-ticket</x-ui.button>
            @elseif (! $charge)
                <x-ui.button variant="outline" :href="route('booking.show', $booking)">Halaman status booking</x-ui.button>
            @endif
            <x-ui.button variant="ghost" :href="WhatsApp::link('Halo Lingkung Gunung, saya ingin bertanya tentang booking '.$booking->code.'.')" target="_blank" rel="noopener">Tanya via WhatsApp</x-ui.button>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="label-kecil mb-3 text-kabut-700">Tahapan booking</h2>
        <x-booking.langkah :booking="$booking" />
        @if ($booking->status === BookingStatus::Expired || ($booking->status === BookingStatus::Pending && $booking->hold_expires_at?->isPast()))
            <p class="border border-kabut-300 bg-white p-5 text-sm text-kabut-700">Waktu pembayaran habis dan unit sudah dilepas. Tidak ada uang yang ditagih. <a href="{{ route('pesan', ['check_in' => $booking->check_in->toDateString(), 'check_out' => $booking->check_out->toDateString(), 'guests' => $booking->guests]) }}" class="font-semibold text-hutan-800 underline underline-offset-4">Pesan ulang tanggal yang sama</a>.</p>
        @elseif ($booking->status === BookingStatus::Cancelled)
            <p class="border border-kabut-300 bg-white p-5 text-sm text-kabut-700">Booking ini dibatalkan. Hubungi kami bila ada pertanyaan tentang pengembalian dana.</p>
        @endif
    </div>

    {{-- Ulasan: hanya setelah check-in & tanggal check-out tiba (aturan di ReviewService) --}}
    @if ($booking->status->isConfirmed())
        <div class="mt-8 scroll-mt-24" id="ulasan">
            <h2 class="label-kecil mb-3 text-kabut-700">Ulasan Anda</h2>
            @if (! $reviewOptions['eligible'])
                <p class="border border-kabut-300 bg-white p-5 text-sm text-kabut-700">{{ $reviewOptions['reason'] }}</p>
            @else
                <ul class="divide-y divide-kabut-200 border border-kabut-300 bg-white">
                    @foreach ($reviewOptions['items'] as ['target' => $target, 'review' => $review])
                        <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="label-kecil text-kabut-600">{{ $target->type->getLabel() }}</p>
                                <p class="mt-1 font-semibold text-hutan-950">{{ $target->name() }}</p>
                                @if ($review)
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <x-ulasan.bintang :value="$review->rating" size="size-3.5" />
                                        <x-ui.badge :variant="match ($review->status->value) { 'published' => 'sukses', 'pending' => 'peringatan', default => 'netral' }">{{ $review->status->customerLabel() }}</x-ui.badge>
                                    </div>
                                    @if ($review->status === \App\Enums\ReviewStatus::Pending)
                                        <p class="mt-2 text-sm text-kabut-700">Tim kami sedang memeriksa ulasan Anda. Ulasan tampil di website setelah disetujui.</p>
                                    @elseif ($review->status === \App\Enums\ReviewStatus::Rejected)
                                        <p class="mt-2 text-sm text-kabut-700">{{ $review->customer_message ?: 'Ulasan ini belum bisa kami tayangkan.' }} Anda bisa mengubah dan mengirimnya lagi.</p>
                                    @elseif ($review->status === \App\Enums\ReviewStatus::Hidden)
                                        <p class="mt-2 text-sm text-kabut-700">Ulasan ini sedang tidak ditayangkan. Anda bisa mengubah dan mengirimnya lagi untuk diperiksa.</p>
                                    @endif
                                @else
                                    <p class="mt-1 text-sm text-kabut-700">Belum diulas</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($review?->isPublished() && $review->publicUrl())
                                    <x-ui.button variant="ghost" size="sm" :href="$review->publicUrl()">Lihat di website</x-ui.button>
                                @endif
                                <x-ui.button :variant="$review ? 'outline' : 'primary'" size="sm" :href="route('akun.ulasan.edit', [$booking->code, $target->key()])" :icon="$review ? 'heroicon-m-pencil-square' : 'heroicon-m-star'">
                                    {{ $review ? 'Ubah ulasan' : 'Tulis ulasan' }}
                                </x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        {{-- Rincian --}}
        <div class="border border-kabut-300 bg-white p-6 sm:p-8">
            <h2 class="label-kecil text-kabut-700">Rincian pemesanan</h2>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-kabut-600">Check-in</dt><dd class="font-semibold text-kabut-900">{{ $booking->check_in->translatedFormat('l, d F Y') }}</dd></div>
                <div><dt class="text-kabut-600">Check-out</dt><dd class="font-semibold text-kabut-900">{{ $booking->check_out->translatedFormat('l, d F Y') }}</dd></div>
                <div><dt class="text-kabut-600">Lama menginap</dt><dd class="font-semibold text-kabut-900">{{ $booking->nights }} malam</dd></div>
                <div><dt class="text-kabut-600">Jumlah tamu</dt><dd class="font-semibold text-kabut-900">{{ $booking->guests }} orang</dd></div>
                @if ($booking->due_date && $booking->outstanding() > 0 && ! $booking->status->isFinal())
                    <div class="sm:col-span-2"><dt class="text-kabut-600">Batas pelunasan</dt><dd class="font-semibold text-peringatan-800">{{ $booking->due_date->translatedFormat('l, d F Y') }}</dd></div>
                @endif
                @if ($booking->notes)
                    <div class="sm:col-span-2"><dt class="text-kabut-600">Catatan Anda</dt><dd class="text-kabut-900">{{ $booking->notes }}</dd></div>
                @endif
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
                @if ($booking->outstanding() > 0 && ! $booking->status->isFinal())
                    <div class="flex justify-between"><dt class="text-kabut-600">Sisa</dt><dd class="font-semibold tabular-nums">{{ PaymentService::rupiah($booking->outstanding()) }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Riwayat pembayaran --}}
        <div class="border border-kabut-300 bg-white p-6">
            <h2 class="label-kecil text-kabut-700">Riwayat pembayaran</h2>
            @forelse ($booking->payments as $payment)
                <div class="mt-4 border-t border-kabut-200 pt-4 first-of-type:border-0 first-of-type:pt-0">
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <div>
                            <p class="font-semibold text-kabut-900">{{ $payment->type->getLabel() }}</p>
                            <p class="text-xs text-kabut-600">{{ $payment->method->getLabel() }} · {{ ($payment->verified_at ?? $payment->created_at)->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <p class="font-semibold tabular-nums">{{ PaymentService::rupiah($payment->amount) }}</p>
                    </div>
                    <x-ui.badge :variant="match ($payment->status->value) { 'paid' => 'sukses', 'pending' => 'peringatan', default => 'netral' }" class="mt-2">{{ $payment->status->getLabel() }}</x-ui.badge>
                </div>
            @empty
                <p class="mt-4 text-sm text-kabut-700">Belum ada pembayaran.</p>
            @endforelse
        </div>
    </div>
</x-layouts.akun>
