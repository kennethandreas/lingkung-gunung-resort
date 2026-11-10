{{--
    Satu booking di daftar akun: tanggal, akomodasi, status, dan (bila ada) tindakan yang perlu dilakukan.
    Tagihan dihitung PaymentService::nextCharge (aturan yang sama dengan halaman status & tombol bayar).
--}}
@props(['booking'])

@use('App\Enums\BookingStatus')
@use('App\Services\PaymentService')
@use('App\Services\ReviewService')

@php
    $charge = app(PaymentService::class)->nextCharge($booking);
    $deadline = match (true) {
        ! $charge => null,
        $booking->status === BookingStatus::Pending => 'sebelum '.$booking->hold_expires_at?->translatedFormat('d M, H:i').' WIB',
        default => $booking->due_date ? 'paling lambat '.$booking->due_date->translatedFormat('d M Y') : null,
    };
    // Booking selesai yang masih punya hal yang belum diulas
    $openReviews = $booking->status === BookingStatus::CheckedIn && auth('customer')->check()
        ? app(ReviewService::class)->openCount(auth('customer')->user(), $booking) : 0;
@endphp

<article {{ $attributes->class(['border bg-white', 'border-emas-500' => $charge, 'border-kabut-300' => ! $charge]) }}>
    <a href="{{ route('akun.booking.show', $booking->code) }}" class="group grid gap-4 p-5 sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] sm:items-center sm:p-6">
        <div class="flex size-16 flex-col items-center justify-center border border-kabut-300 text-hutan-900" aria-hidden="true">
            <span class="font-display text-2xl leading-none">{{ $booking->check_in->format('d') }}</span>
            <span class="label-kecil mt-1 text-kabut-600">{{ $booking->check_in->translatedFormat('M') }}</span>
        </div>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-display text-xl text-hutan-950 group-hover:text-hutan-700">{{ $booking->accommodationSummary() }}</h3>
                <x-akun.status-badge :status="$booking->status" />
            </div>
            <p class="mt-1 text-sm text-kabut-700">
                {{ $booking->check_in->translatedFormat('D, d M Y') }} – {{ $booking->check_out->translatedFormat('D, d M Y') }}
                · {{ $booking->nights }} malam · {{ $booking->guests }} tamu
            </p>
            <p class="mt-1 text-xs text-kabut-600">Kode <span class="font-semibold tracking-wide text-kabut-800">{{ $booking->code }}</span> · {{ $booking->paymentStatusLabel() }} · Total {{ PaymentService::rupiah($booking->total) }}</p>
        </div>

        <span class="tautan-panah justify-self-start text-hutan-900 sm:justify-self-end">Detail <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></span>
    </a>

    @if ($openReviews > 0)
        <div class="flex flex-col gap-3 border-t border-kabut-200 bg-kabut-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-kabut-800"><strong>Bagikan pengalaman Anda:</strong> {{ $openReviews }} hal dari booking ini bisa diulas.</p>
            <a href="{{ route('akun.booking.show', $booking->code) }}#ulasan" class="tautan-panah shrink-0 text-hutan-900">Tulis ulasan <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
        </div>
    @endif

    @if ($charge)
        <div class="flex flex-col gap-3 border-t border-emas-500/50 bg-emas-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-emas-900"><strong>Perlu tindakan:</strong> bayar {{ ['dp' => 'DP', 'settlement' => 'pelunasan', 'full' => 'lunas'][$charge['type']->value] }} {{ PaymentService::rupiah($charge['amount']) }}@if ($deadline) {{ $deadline }}@endif.</p>
            <a href="{{ route('booking.show', $booking) }}" class="tautan-panah shrink-0 text-hutan-900">Bayar sekarang <x-heroicon-m-arrow-right class="size-3.5" aria-hidden="true" /></a>
        </div>
    @endif
</article>
