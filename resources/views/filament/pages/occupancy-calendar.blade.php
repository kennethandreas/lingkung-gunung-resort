<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-left" wire:click="shift(-{{ $days }})">Sebelumnya</x-filament::button>
        <x-filament::button color="gray" size="sm" wire:click="goToday">Hari ini</x-filament::button>
        <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-right" icon-position="after" wire:click="shift({{ $days }})">Berikutnya</x-filament::button>

        <x-filament::input.wrapper class="ms-auto w-40">
            <x-filament::input type="date" wire:model.live="start" />
        </x-filament::input.wrapper>
        <x-filament::input.wrapper class="w-32">
            <x-filament::input.select wire:model.live="days">
                <option value="7">7 hari</option>
                <option value="14">14 hari</option>
                <option value="30">30 hari</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
        <span class="flex items-center gap-1"><span class="size-3 rounded bg-success-100 ring-1 ring-success-300 dark:bg-success-500/20"></span> Masih tersedia</span>
        <span class="flex items-center gap-1"><span class="size-3 rounded bg-warning-100 ring-1 ring-warning-300 dark:bg-warning-500/20"></span> Hampir penuh (sisa ≤ 20%)</span>
        <span class="flex items-center gap-1"><span class="size-3 rounded bg-danger-100 ring-1 ring-danger-300 dark:bg-danger-500/20"></span> Penuh</span>
        <span class="flex items-center gap-1"><span class="size-3 rounded bg-gray-300 dark:bg-gray-600"></span> Diblokir</span>
        <span>Angka besar = sisa · kecil = terjual + ditahan / kapasitas</span>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="sticky start-0 z-10 bg-white px-3 py-2 text-start font-semibold dark:bg-gray-900">Tipe unit</th>
                    @foreach ($dates as $day)
                        @php
                            $isWeekend = $day['day_type']->value !== 'weekday';
                        @endphp
                        <th @class([
                            'min-w-16 px-1 py-2 text-center font-medium',
                            'bg-primary-50 dark:bg-primary-500/10' => $isWeekend,
                            'ring-2 ring-inset ring-primary-500' => $day['date']->isToday(),
                        ]) title="{{ $day['day_type']->getLabel() }}">
                            <div class="text-xs text-gray-500">{{ $day['date']->translatedFormat('D') }}</div>
                            <div>{{ $day['date']->format('d/m') }}</div>
                            <div class="text-[10px] font-semibold uppercase text-primary-600">{{ match ($day['day_type']->value) { 'weekday' => 'WD', 'weekend' => 'WE', default => 'HS' } }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($unitTypes as $type)
                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                        <td class="sticky start-0 z-10 bg-white px-3 py-2 dark:bg-gray-900">
                            <div class="font-medium">{{ $type->marketing_name }}</div>
                            <div class="text-xs text-gray-500">{{ $type->pricing_unit->getLabel() }} · {{ $type->total_units }}/malam</div>
                        </td>
                        @foreach ($dates as $day)
                            @php
                                $row = $rows->get($type->id)?->get($day['date']->toDateString());
                                $available = $row?->available() ?? 0;
                                $ratio = $row && $row->capacity > 0 ? $available / $row->capacity : 0;
                            @endphp
                            <td class="p-1 text-center">
                                @if ($row)
                                    <div @class([
                                        'rounded-md px-1 py-1.5',
                                        'bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200' => $row->is_blocked,
                                        'bg-danger-100 text-danger-800 dark:bg-danger-500/20 dark:text-danger-300' => ! $row->is_blocked && $available === 0,
                                        'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300' => ! $row->is_blocked && $available > 0 && $ratio <= 0.2,
                                        'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-300' => ! $row->is_blocked && $ratio > 0.2,
                                    ]) title="{{ $row->is_blocked ? 'Diblokir: '.($row->block_note ?: '-') : "Terjual {$row->booked}, ditahan {$row->held}, kapasitas {$row->capacity}" }}">
                                        @if ($row->is_blocked)
                                            <x-filament::icon icon="heroicon-m-no-symbol" class="mx-auto size-4" />
                                        @else
                                            <div class="text-base font-bold leading-none">{{ $available }}</div>
                                            <div class="mt-0.5 text-[10px] leading-none opacity-75">{{ $row->booked + $row->held }}/{{ $row->capacity }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
