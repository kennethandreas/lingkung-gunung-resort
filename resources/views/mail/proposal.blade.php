<x-mail::message>
# Halo, {{ $quotation->customer->name }}

Terima kasih telah merancang paket rombongan di Lingkung Gunung. Berikut ringkasan penawaran Anda:

<x-mail::table>
| | |
|:--|:--|
| Nomor penawaran | **{{ $quotation->number }}** |
| Paket | {{ $quotation->variant?->name }} |
| Tanggal | {{ $quotation->event_date->translatedFormat('d F Y') }} · {{ $quotation->nights }} malam |
| Peserta | {{ $quotation->pax }} orang |
| Estimasi total | **{{ \App\Services\PaymentService::rupiah($quotation->total) }}** |
</x-mail::table>

Proposal lengkap beserta rincian menu terlampir dalam bentuk PDF. Tim sales kami akan menghubungi Anda untuk finalisasi.

<x-mail::button :url="$url" color="success">
Lihat proposal
</x-mail::button>

Ingin berdiskusi lebih cepat? Hubungi kami via WhatsApp {{ \App\Support\WhatsApp::display() }} dan sebutkan nomor penawaran di atas.

Salam hangat,<br>
Tim Sales Lingkung Gunung
</x-mail::message>
