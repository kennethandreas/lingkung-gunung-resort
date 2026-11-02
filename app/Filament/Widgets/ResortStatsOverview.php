<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PricingUnit;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\DailyInventory;
use App\Services\DashboardService;
use App\Services\PaymentService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan harian di dashboard (dokumen teknis 4.4).
 * Baris 1 = operasional hari ini, baris 2 = uang. "Uang diterima" ≠ "nilai booking" (lihat DashboardService).
 */
class ResortStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Ringkasan';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $dashboard = app(DashboardService::class);
        $today = today()->toDateString();

        // Okupansi malam ini: hanya tipe unit berbasis kamar/tenda/kavling (bukan per orang)
        $inventory = DailyInventory::query()
            ->whereDate('date', $today)
            ->whereHas('unitType', fn ($q) => $q->where('is_active', true)->where('pricing_unit', '!=', PricingUnit::Person))
            ->selectRaw('COALESCE(SUM(capacity),0) as capacity, COALESCE(SUM(booked),0) as booked, COALESCE(SUM(held),0) as held')
            ->first();
        $occupancy = $inventory->capacity > 0 ? round($inventory->booked / $inventory->capacity * 100) : 0;

        $active = DashboardService::confirmedStatuses();
        $checkIns = Booking::query()->whereDate('check_in', $today)->whereIn('status', $active)->count();
        $checkedIn = Booking::query()->whereDate('check_in', $today)->where('status', BookingStatus::CheckedIn)->count();
        $checkOuts = Booking::query()->whereDate('check_out', $today)->whereIn('status', $active)->count();

        $monthStart = CarbonImmutable::today()->startOfMonth();
        $receivedMonth = $dashboard->received($monthStart, CarbonImmutable::today());
        // pembanding adil: periode yang sama di bulan lalu (tgl 1 s.d. tanggal hari ini)
        $lastMonthStart = $monthStart->subMonthNoOverflow();
        $receivedLast = $dashboard->received($lastMonthStart, $lastMonthStart->addDays(today()->day - 1)->min($lastMonthStart->endOfMonth()));
        $created = $dashboard->bookingsCreated($monthStart, CarbonImmutable::today());
        $outstanding = $dashboard->outstanding();

        $delta = $receivedLast > 0 ? (int) round(($receivedMonth - $receivedLast) / $receivedLast * 100) : null;
        $month = now()->translatedFormat('F');

        return [
            Stat::make('Okupansi malam ini', "{$occupancy}%")
                ->description("{$inventory->booked} dari {$inventory->capacity} unit terisi · {$inventory->held} ditahan")
                ->descriptionIcon(Heroicon::OutlinedHomeModern)
                ->color($occupancy >= 80 ? 'success' : 'primary'),
            Stat::make('Check-in hari ini', (string) $checkIns)
                ->description("{$checkedIn} sudah datang")
                ->descriptionIcon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->url(BookingResource::getUrl('index')),
            Stat::make('Check-out hari ini', (string) $checkOuts)
                ->descriptionIcon(Heroicon::OutlinedArrowLeftStartOnRectangle)
                ->description('Booking aktif yang selesai hari ini'),

            Stat::make("Uang diterima {$month}", PaymentService::rupiah($receivedMonth))
                ->description($delta === null ? 'Pembayaran terverifikasi bulan ini' : ($delta >= 0 ? "Naik {$delta}%" : 'Turun '.abs($delta).'%').' dari periode sama bulan lalu')
                ->descriptionIcon($delta !== null && $delta < 0 ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp)
                ->color($delta !== null && $delta < 0 ? 'danger' : 'success'),
            Stat::make("Nilai booking terkonfirmasi {$month}", PaymentService::rupiah($created['value']))
                ->description("{$created['count']} booking · {$created['lost']} batal/kedaluwarsa · bukan uang masuk")
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('primary'),
            Stat::make('Sisa tagihan (booking DP)', PaymentService::rupiah($outstanding['amount']))
                ->description("{$outstanding['count']} booking menunggu pelunasan".($outstanding['overdue'] ? " · {$outstanding['overdue']} lewat batas" : ''))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color($outstanding['overdue'] ? 'danger' : 'warning'),
        ];
    }
}
