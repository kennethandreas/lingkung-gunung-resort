<x-filament-panels::page>
    @php
        $rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $revenue = $report['revenue'];
        $bookings = $report['bookings'];
        $occupancy = $report['occupancy'];
        $maxDaily = max(1, ...array_values($revenue['daily'] ?: [0]));
    @endphp

    {{-- Filter periode --}}
    <div class="flex flex-wrap items-end gap-3">
        <label class="text-sm">
            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Dari</span>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
        </label>
        <label class="text-sm">
            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Sampai</span>
            <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
        </label>
        <div class="flex flex-wrap gap-2">
            <x-filament::button size="sm" color="gray" wire:click="preset('bulan-ini')">Bulan ini</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="preset('bulan-lalu')">Bulan lalu</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="preset('30-hari')">30 hari terakhir</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="preset('tahun-ini')">Tahun ini</x-filament::button>
        </div>
    </div>
    <p class="text-sm text-gray-500">Periode {{ $report['period']['from']->translatedFormat('d M Y') }} – {{ $report['period']['to']->translatedFormat('d M Y') }} ({{ $report['period']['days'] }} hari)</p>

    {{-- Angka utama --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Pendapatan', $rp($revenue['total']), $revenue['count'].' pembayaran terverifikasi'],
            ['Booking terkonfirmasi', $bookings['confirmed'].' / '.$bookings['count'], 'Konversi '.$bookings['conversion'].'%'],
            ['Nilai booking terkonfirmasi', $rp($bookings['value']), 'Rata-rata '.$bookings['average_nights'].' malam'],
            ['Okupansi kamar & tenda', $occupancy['overall'].'%', $occupancy['nights_sold'].' unit-malam terjual'],
        ] as [$label, $value, $hint])
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ $value }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Pendapatan --}}
        <x-filament::section heading="Pendapatan" description="Pembayaran yang terverifikasi di dalam periode (basis kas).">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                @foreach ($revenue['by_method'] as $label => $amount)
                    <dt class="text-gray-500">{{ $label }}</dt><dd class="text-right font-semibold tabular-nums">{{ $rp($amount) }}</dd>
                @endforeach
                @foreach ($revenue['by_type'] as $label => $amount)
                    <dt class="text-gray-500">{{ $label }}</dt><dd class="text-right font-semibold tabular-nums">{{ $rp($amount) }}</dd>
                @endforeach
            </dl>
            @if ($revenue['daily'])
                <div class="mt-6">
                    <p class="mb-2 text-xs font-medium text-gray-500">Pendapatan harian</p>
                    <div class="flex h-32 items-end gap-1" role="img" aria-label="Grafik pendapatan harian">
                        @foreach ($revenue['daily'] as $date => $amount)
                            <div class="group relative flex-1 rounded-t bg-primary-600" style="height: {{ max(4, round($amount / $maxDaily * 100)) }}%" title="{{ \Carbon\Carbon::parse($date)->translatedFormat('d M') }}: {{ $rp($amount) }}"></div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="mt-4 text-sm text-gray-500">Belum ada pembayaran di periode ini.</p>
            @endif
        </x-filament::section>

        {{-- Sumber booking --}}
        <x-filament::section heading="Sumber booking" description="Booking yang dibuat di dalam periode.">
            <table class="w-full text-left text-sm">
                <thead class="text-gray-500"><tr><th class="py-2">Sumber</th><th class="py-2 text-right">Booking</th><th class="py-2 text-right">Terkonfirmasi</th><th class="py-2 text-right">Nilai</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($bookings['by_channel'] as $label => $row)
                        <tr><td class="py-2 font-medium">{{ $label }}</td><td class="py-2 text-right tabular-nums">{{ $row['count'] }}</td><td class="py-2 text-right tabular-nums">{{ $row['confirmed'] }}</td><td class="py-2 text-right font-semibold tabular-nums">{{ $rp($row['value']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($bookings['by_status'] as $label => $count)
                    @if ($count)
                        <x-filament::badge color="gray">{{ $label }}: {{ $count }}</x-filament::badge>
                    @endif
                @endforeach
            </div>
        </x-filament::section>
    </div>

    {{-- Okupansi --}}
    <x-filament::section heading="Okupansi per tipe unit" description="Unit-malam terjual dibanding kapasitas pada tanggal menginap di dalam periode.">
        <div class="space-y-3">
            @foreach ($occupancy['types'] as $type)
                <div>
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="font-medium">{{ $type['name'] }} @if ($type['per_person'])<span class="text-xs text-gray-500">(per orang)</span>@endif</span>
                        <span class="tabular-nums text-gray-600 dark:text-gray-400">{{ $type['booked'] }} / {{ $type['capacity'] }} · <strong>{{ $type['rate'] }}%</strong></span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, $type['rate']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
