@php
    $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
    $mealLabels = ['breakfast' => 'Sarapan', 'lunch' => 'Makan siang', 'dinner' => 'Makan malam'];
    $package = $quotation->items->firstWhere('item_type', 'package');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Proposal {{ $quotation->number }}</title>
    <style>
        @page { margin: 16mm 14mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #26231e; font-size: 9.5pt; line-height: 1.45; }
        h1 { color: #1f402d; font-size: 18pt; margin: 0; }
        h2 { color: #1f402d; font-size: 12pt; margin: 18px 0 6px; border-bottom: 1px solid #d9cdb8; padding-bottom: 4px; }
        .tag { color: #84521f; font-size: 7.5pt; letter-spacing: 2px; text-transform: uppercase; }
        .muted { color: #6e6452; font-size: 8.5pt; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f5f0e6; color: #3a352d; padding: 5px 6px; font-size: 8.5pt; }
        td { padding: 5px 6px; border-bottom: 1px solid #ebe3d3; vertical-align: top; }
        .right { text-align: right; }
        .total td { font-weight: bold; font-size: 11pt; color: #1f402d; border-bottom: none; }
        .box { background: #fbf7ec; border: 1px solid #ebd596; border-radius: 6px; padding: 8px 10px; margin-top: 10px; }
        .menu td { font-size: 8pt; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="border:none; padding:0">
                <div class="tag">Lingkung Gunung Adventure Camp Bogor</div>
                <h1>Proposal Paket Rombongan</h1>
                <div class="muted">Tempat Alam Bercerita</div>
            </td>
            <td class="right" style="border:none; padding:0">
                <strong>{{ $quotation->number }}</strong><br>
                <span class="muted">Dibuat {{ $quotation->created_at->translatedFormat('d F Y') }}</span>
            </td>
        </tr>
    </table>

    <h2>Data klien</h2>
    <table>
        <tr><td style="width:30%" class="muted">Nama</td><td>{{ $quotation->customer->name }}</td></tr>
        @if ($quotation->customer->organization)
            <tr><td class="muted">Instansi</td><td>{{ $quotation->customer->organization }}</td></tr>
        @endif
        <tr><td class="muted">Kontak</td><td>{{ $quotation->customer->phone }} @if ($quotation->customer->email)· {{ $quotation->customer->email }}@endif</td></tr>
        <tr><td class="muted">Tanggal acara</td><td>{{ $quotation->event_date->translatedFormat('l, d F Y') }} s.d. {{ $quotation->checkOut()->translatedFormat('l, d F Y') }} ({{ $quotation->nights }} malam)</td></tr>
        <tr><td class="muted">Jumlah peserta</td><td>{{ $quotation->pax }} orang</td></tr>
    </table>

    @if ($quotation->variant)
        <h2>Paket: {{ $quotation->variant->name }}</h2>
        <p class="muted" style="margin:0 0 4px">Termasuk: {{ implode(', ', $quotation->variant->includes ?? []) }}</p>
        @if ($package && ! empty($package->meta['nights']))
            <p class="muted" style="margin:0">Harga per peserta per malam:
                @foreach ($package->meta['nights'] as $night)
                    {{ \Carbon\Carbon::parse($night['date'])->translatedFormat('D d/m') }} ({{ $night['day_type'] === 'weekday' ? 'WD' : 'WE' }}) {{ $rupiah($night['price']) }}@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif
    @endif

    <h2>Rincian biaya</h2>
    <table>
        <thead><tr><th>Item</th><th class="right">Jumlah</th><th class="right">Harga satuan</th><th class="right">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->qty }} {{ $item->meta['unit'] ?? '' }}</td>
                    <td class="right">{{ $rupiah($item->unit_price) }}</td>
                    <td class="right">{{ $rupiah($item->subtotal) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="3">Estimasi total</td><td class="right">{{ $rupiah($quotation->total) }}</td></tr>
        </tbody>
    </table>

    @if (! empty($quotation->menu_plan) && $menuOptions->isNotEmpty())
        <h2>Rencana menu ({{ $quotation->variant?->menuSet?->name }})</h2>
        <table class="menu">
            <thead><tr><th style="width:12%">Hari</th><th>Sarapan</th><th>Makan siang</th><th>Makan malam</th><th>Coffee break</th></tr></thead>
            <tbody>
                @foreach ($quotation->menu_plan as $day)
                    <tr>
                        <td>Hari {{ $day['day'] }}</td>
                        @foreach ($mealLabels as $meal => $label)
                            @php
                                $option = $menuOptions->get($meal)?->firstWhere('option_code', $day[$meal] ?? null);
                            @endphp
                            <td>
                                @if ($option)
                                    <strong>Opsi {{ $option->option_code }}</strong><br>{{ implode(', ', $option->items) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @forelse ($day['coffee_breaks'] ?? [] as $i => $picks)
                                {{ $i + 1 }}. {{ implode(', ', $picks) }}<br>
                            @empty
                                -
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="box">
        <strong>Ketentuan pembayaran:</strong> DP 25% ({{ $rupiah($dpAmount) }}) saat konfirmasi, pelunasan paling lambat H-3 sebelum acara. Seluruh pembayaran non-tunai.
        Penawaran ini merupakan <strong>estimasi</strong> dan akan difinalisasi oleh tim sales kami.
    </div>

    @if ($quotation->notes)
        <p class="muted" style="margin-top:10px"><strong>Catatan klien:</strong> {{ $quotation->notes }}</p>
    @endif

    <p class="muted" style="margin-top:14px">
        {{ \App\Models\Setting::get('address') }}<br>
        WhatsApp {{ \App\Support\WhatsApp::display() }} · Telp {{ \App\Models\Setting::get('contact_phone') }} · Instagram {{ '@'.\App\Models\Setting::get('contact_instagram') }}
    </p>
</body>
</html>
