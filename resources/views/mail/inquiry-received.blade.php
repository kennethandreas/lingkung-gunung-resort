<x-mail::message>
# Pesan baru dari website

<x-mail::table>
| | |
|:--|:--|
| Nama | **{{ $inquiry->name }}** |
| WhatsApp | {{ $inquiry->phone }} |
| Email | {{ $inquiry->email ?: '-' }} |
| Topik | {{ $inquiry->topic->getLabel() }} |
@if ($inquiry->visit_date)
| Rencana tanggal | {{ $inquiry->visit_date->translatedFormat('d F Y') }} |
@endif
@if ($inquiry->participants)
| Jumlah peserta | {{ $inquiry->participants }} orang |
@endif
</x-mail::table>

{{ $inquiry->message }}

<x-mail::button :url="$url" color="success">
Buka di admin panel
</x-mail::button>

Balas lewat tombol "Balas via WhatsApp" di admin supaya status pesan tercatat.
</x-mail::message>
