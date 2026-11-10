<x-mail::message>
# Terima kasih, {{ $booking->customer->name }}!

Pembayaran **{{ $payment->type->getLabel() }} {{ \App\Services\PaymentService::rupiah($payment->amount) }}** untuk booking **{{ $booking->code }}** sudah kami terima.

<x-mail::table>
| | |
|:--|:--|
| Kode booking | **{{ $booking->code }}** |
| Check-in | {{ $booking->check_in->translatedFormat('l, d F Y') }} |
| Check-out | {{ $booking->check_out->translatedFormat('l, d F Y') }} |
| Akomodasi | {{ $booking->items->map(fn ($i) => "{$i->description} × {$i->qty}")->implode(', ') }} |
| Total | {{ \App\Services\PaymentService::rupiah($booking->total) }} |
| Sudah dibayar | {{ \App\Services\PaymentService::rupiah($booking->paid_amount) }} |
@if ($booking->outstanding() > 0)
| Sisa pembayaran | **{{ \App\Services\PaymentService::rupiah($booking->outstanding()) }}** (paling lambat {{ $booking->due_date?->translatedFormat('d F Y') }}) |
@endif
</x-mail::table>

E-ticket terlampir dalam bentuk PDF. Tunjukkan QR di e-ticket kepada front office saat tiba.

<x-mail::button :url="$url" color="success">
Lihat status booking
</x-mail::button>

@if ($booking->outstanding() > 0)
Pelunasan bisa dilakukan kapan saja melalui halaman status booking di atas.
@endif

Sampai jumpa di Lingkung Gunung,<br>
*Tempat Alam Bercerita*
</x-mail::message>
