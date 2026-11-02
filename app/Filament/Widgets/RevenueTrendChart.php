<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Support\BrandColors;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

/** Uang diterima per hari (pembayaran terverifikasi, per tanggal verifikasi). */
class RevenueTrendChart extends ChartWidget
{
    use HasWidgetShield;

    /** Grafik keuangan hanya di halaman Laporan: izin sama dengan halaman itu (admin & owner). */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('View:Reports');
    }

    protected static ?int $sort = 3;

    protected ?string $heading = 'Uang diterima per hari';

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    public function getDescription(): ?string
    {
        return 'Pembayaran terverifikasi (Midtrans & transfer), per tanggal verifikasi.';
    }

    protected function getFilters(): ?array
    {
        return ['7' => '7 hari', '30' => '30 hari', '90' => '90 hari'];
    }

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->dailyTrend((int) $this->filter);

        return [
            'datasets' => [[
                'label' => 'Diterima (Rp)',
                'data' => $trend['received'],
                'borderColor' => BrandColors::HUTAN[600],
                'backgroundColor' => 'rgba(47, 100, 65, 0.12)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
            ]],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]],
            ],
            'interaction' => ['intersect' => false, 'mode' => 'index'],
        ];
    }
}
