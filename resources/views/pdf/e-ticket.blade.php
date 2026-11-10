<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-ticket {{ $booking->code }}</title>
    <style>
        @page { margin: 11mm 12mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #26231e; font-size: 10pt; margin: 0; }
        .brand { color: #1f402d; font-size: 16pt; font-weight: bold; }
        .tag { color: #84521f; font-size: 8pt; letter-spacing: 2px; text-transform: uppercase; }
        .box { border: 1px solid #d9cdb8; border-radius: 8px; padding: 10px 14px; margin-top: 9px; }
        .code { font-size: 20pt; font-weight: bold; color: #1f402d; letter-spacing: 2px; }
        .muted { color: #6e6452; font-size: 8.5pt; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .label { color: #6e6452; width: 38%; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9pt; font-weight: bold; }
        .lunas { background: #ecfdf3; color: #0f5c3b; }
        .dp { background: #fff8eb; color: #7c3a06; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="brand">Lingkung Gunung</div>
                <div class="tag">Adventure Camp Bogor · E-ticket</div>
            </td>
            <td style="text-align:right">
                @if ($booking->outstanding() === 0)
                    <span class="status lunas">LUNAS</span>
                @else
                    <span class="status dp">DP DIBAYAR</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- QR di kiri, kode di kanan: muat satu halaman A5 walau item banyak (B2B) --}}
    <div class="box">
        <table>
            <tr>
                <td style="width:140px; padding:0"><img src="{{ $qr }}" width="130" height="130" alt="QR check-in"></td>
                <td style="vertical-align:middle; padding-left:8px">
                    <div class="muted">Kode booking</div>
                    <div class="code">{{ $booking->code }}</div>
                    <div class="muted" style="margin-top:6px">Tunjukkan QR ini kepada front office saat check-in.</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table>
            <tr><td class="label">Nama pemesan</td><td><strong>{{ $booking->customer->name }}</strong></td></tr>
            <tr><td class="label">Check-in</td><td>{{ $booking->check_in->translatedFormat('l, d F Y') }}</td></tr>
            <tr><td class="label">Check-out</td><td>{{ $booking->check_out->translatedFormat('l, d F Y') }}</td></tr>
            <tr><td class="label">Lama menginap</td><td>{{ $booking->nights }} malam · {{ $booking->guests }} tamu</td></tr>
            @foreach ($booking->items as $item)
                <tr><td class="label">{{ $loop->first ? 'Akomodasi' : '' }}</td><td>{{ $item->description }} × {{ $item->qty }}</td></tr>
            @endforeach
        </table>
    </div>

    <div class="box">
        <table>
            <tr><td class="label">Total</td><td>{{ \App\Services\PaymentService::rupiah($booking->total) }}</td></tr>
            <tr><td class="label">Sudah dibayar</td><td>{{ \App\Services\PaymentService::rupiah($booking->paid_amount) }}</td></tr>
            @if ($booking->outstanding() > 0)
                <tr><td class="label">Sisa pembayaran</td><td><strong>{{ \App\Services\PaymentService::rupiah($booking->outstanding()) }}</strong> paling lambat {{ $booking->due_date?->translatedFormat('d F Y') }}</td></tr>
            @endif
        </table>
    </div>

    <p class="muted" style="margin:10px 0 0">
        {{ \App\Models\Setting::get('address') }}<br>
        WhatsApp {{ \App\Support\WhatsApp::display() }} · Telp {{ \App\Models\Setting::get('contact_phone') }} · Seluruh pembayaran non-tunai.
    </p>
</body>
</html>
