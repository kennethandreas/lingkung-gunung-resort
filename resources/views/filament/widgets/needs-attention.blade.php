@php
    $tones = [
        'danger' => 'bg-red-50 text-red-700 ring-red-600/15 dark:bg-red-500/10 dark:text-red-300',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-300',
        'info' => 'bg-primary-50 text-primary-800 ring-primary-600/15 dark:bg-primary-500/10 dark:text-primary-300',
    ];
    $base = 'flex h-full items-start gap-3 rounded-xl p-4 ring-1 ring-inset transition duration-300';
@endphp

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-bell-alert" heading="Perlu tindakan" description="Diperbarui tiap menit. Klik untuk membuka daftar terkait.">
        @if (empty($items))
            <div class="flex items-center gap-3 rounded-xl bg-primary-50 p-4 text-sm text-primary-800 dark:bg-primary-500/10 dark:text-primary-200">
                <x-filament::icon icon="heroicon-o-check-badge" class="size-6 shrink-0" />
                <p><span class="font-semibold">Semua beres.</span> Tidak ada pembayaran bermasalah, pelunasan terlambat, atau pesan yang belum dibalas.</p>
            </div>
        @else
            <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $item)
                    <li>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" class="{{ $base }} {{ $tones[$item['color']] ?? $tones['info'] }} hover:ring-current focus-visible:outline-2 focus-visible:outline-primary-600">
                                @include('filament.widgets.partials.attention-item', ['item' => $item])
                            </a>
                        @else
                            <div class="{{ $base }} {{ $tones[$item['color']] ?? $tones['info'] }}">
                                @include('filament.widgets.partials.attention-item', ['item' => $item])
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
