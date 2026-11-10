<x-mail::message>
# Halo, {{ $booking->customer->name }}

Kedatangan Anda di Lingkung Gunung tinggal **{{ (int) today()->diffInDays($booking->check_in) }} hari lagi** ({{ $booking->check_in->translatedFormat('l, d F Y') }}).

Sisa pembayaran booking **{{ $booking->code }}** sebesar **{{ \App\Services\PaymentService::rupiah($booking->outstanding()) }}** perlu dilunasi paling lambat **{{ $booking->due_date?->translatedFormat('l, d F Y') }}**.

<x-mail::button :url="$url" color="success">
Lunasi sekarang
</x-mail::button>

Pembayaran bisa melalui QRIS, virtual account, atau e-wallet. Abaikan email ini bila Anda sudah melunasi.

Salam hangat,<br>
Lingkung Gunung Adventure Camp
</x-mail::message>
