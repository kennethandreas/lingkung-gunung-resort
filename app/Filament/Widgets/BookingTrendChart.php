<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Support\BrandColors;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

/** Booking dibuat per hari: terkonfirmasi (DP/lunas/check-in) vs batal/kedaluwarsa. */
class BookingTrendChart extends ChartWidget
{
    use HasWidgetShield;

    /** Grafik keuangan hanya di halaman Laporan: izin sama dengan halaman itu (admin & owner). */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('View:Reports');
    }

    protected static ?int $sort = 4;

    protected ?string $heading = 'Booking masuk per hari';

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    public function getDescription(): ?string
    {
        return 'Menurut tanggal booking dibuat. Booking yang masih menunggu pembayaran tidak dihitung.';
    }

    protected function getFilters(): ?array
    {
        return ['7' => '7 hari', '30' => '30 hari', '90' => '90 hari'];
    }

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->dailyTrend((int) $this->filter);

        return [
            'datasets' => [
                [
                    'label' => 'Terkonfirmasi',
                    'data' => $trend['confirmed'],
                    'backgroundColor' => BrandColors::HUTAN[600],
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Batal / kedaluwarsa',
                    'data' => $trend['lost'],
                    'backgroundColor' => BrandColors::KABUT[300],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'x' => ['stacked' => true, 'grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]],
                'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'interaction' => ['intersect' => false, 'mode' => 'index'],
        ];
    }
}
