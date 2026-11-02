{{--
    Alur sebuah booking dari dibuat sampai check-in, berdasarkan status & data booking yang sebenarnya.
    Dipakai halaman status booking (/booking/{code}) dan detail booking di akun.
    Booking kedaluwarsa/dibatalkan tidak menampilkan alur (halaman menampilkan pesan statusnya sendiri).
--}}
@props(['booking'])

@use('App\Enums\BookingStatus')
@use('App\Services\PaymentService')

@php
    $status = $booking->status;
    $isDp = $booking->payment_option !== 'full';
    $paidFirst = in_array($status, [BookingStatus::DpPaid, BookingStatus::FullyPaid, BookingStatus::CheckedIn], true);
    $fullyPaid = in_array($status, [BookingStatus::FullyPaid, BookingStatus::CheckedIn], true);

    $steps = [
        ['Pesanan dibuat', $booking->created_at->translatedFormat('d M Y, H:i').' WIB', 'selesai'],
        [
            $isDp ? 'Bayar DP' : 'Bayar lunas',
            $isDp ? PaymentService::rupiah($booking->dp_amount).' agar unit dikonfirmasi' : PaymentService::rupiah($booking->total).' agar unit dikonfirmasi',
            $paidFirst ? 'selesai' : 'sekarang',
        ],
    ];
    if ($isDp) {
        // Sebelum DP dibayar, sisa pelunasan = total - DP (bukan total penuh)
        $settlement = $paidFirst ? $booking->outstanding() : max(0, $booking->total - $booking->dp_amount);
        $steps[] = [
            'Pelunasan',
            ($fullyPaid ? 'Lunas' : PaymentService::rupiah($settlement).($booking->due_date ? ' paling lambat '.$booking->due_date->translatedFormat('d M Y') : '')),
            $fullyPaid ? 'selesai' : ($status === BookingStatus::DpPaid ? 'sekarang' : 'nanti'),
        ];
    }
    $steps[] = [
        'Check-in',
        $booking->check_in->translatedFormat('l, d M Y').' · tunjukkan e-ticket',
        $status === BookingStatus::CheckedIn ? 'selesai' : ($fullyPaid ? 'sekarang' : 'nanti'),
    ];
@endphp

@php
    // Hold sudah lewat tapi status belum diperbarui penjadwal: pembayaran tidak bisa lagi, jangan tampilkan "Bayar" sebagai langkah
    $lapsed = $status === BookingStatus::Pending && $booking->hold_expires_at?->isPast();
@endphp

@unless ($lapsed || ($status->isFinal() && ! in_array($status, [BookingStatus::FullyPaid, BookingStatus::CheckedIn], true)))
    <ol {{ $attributes->class(['grid gap-px border border-kabut-300/70 bg-kabut-300/70', count($steps) === 4 ? 'sm:grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-3']) }} aria-label="Tahapan booking">
        @foreach ($steps as $i => [$title, $detail, $state])
            <li @class(['bg-white p-4', 'border-t-2' => true, 'border-hutan-800' => $state === 'selesai', 'border-emas-500' => $state === 'sekarang', 'border-transparent' => $state === 'nanti'])
                @if ($state === 'sekarang') aria-current="step" @endif>
                <p class="label-kecil flex items-center gap-2 {{ $state === 'nanti' ? 'text-kabut-500' : ($state === 'sekarang' ? 'text-emas-800' : 'text-hutan-800') }}">
                    @if ($state === 'selesai')
                        <x-heroicon-m-check class="size-4" aria-hidden="true" /><span class="sr-only">Selesai:</span>
                    @else
                        <span class="tabular-nums">{{ $i + 1 }}</span>
                    @endif
                    {{ $state === 'sekarang' ? 'Langkah sekarang' : ($state === 'selesai' ? 'Selesai' : 'Berikutnya') }}
                </p>
                <p @class(['mt-2 font-semibold', 'text-kabut-900' => $state !== 'nanti', 'text-kabut-600' => $state === 'nanti'])>{{ $title }}</p>
                <p class="mt-0.5 text-sm text-kabut-600">{{ $detail }}</p>
            </li>
        @endforeach
    </ol>
@endunless
