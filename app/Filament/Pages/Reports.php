<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingTrendChart;
use App\Filament\Widgets\RevenueTrendChart;
use App\Services\ReportService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/** Laporan pendapatan, okupansi, dan sumber booking + ekspor Excel. */
class Reports extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Pengaturan';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan';

    protected static ?string $slug = 'laporan';

    public string $from = '';

    public string $to = '';

    /** Grafik tren (dipindah dari dasbor agar dasbor tetap ringkas). */
    protected function getHeaderWidgets(): array
    {
        return [RevenueTrendChart::class, BookingTrendChart::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['md' => 2];
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    public function preset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'bulan-lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'tahun-ini' => [now()->startOfYear(), now()->endOfYear()],
            '30-hari' => [now()->subDays(29), now()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(): array
    {
        try {
            $from = CarbonImmutable::parse($this->from);
            $to = CarbonImmutable::parse($this->to);
        } catch (\Throwable) {
            [$from, $to] = [CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth()];
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        // Batasi rentang 2 tahun agar laporan tetap ringan
        if ($from->diffInDays($to) > 731) {
            $to = $from->addDays(731);
        }

        return [$from, $to];
    }

    protected function getViewData(): array
    {
        return ['report' => app(ReportService::class)->summary(...$this->range())];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function (): BinaryFileResponse {
                    [$from, $to] = $this->range();
                    $path = app(ReportService::class)->exportXlsx($from, $to);

                    return response()->download($path, 'laporan-lingkung-gunung-'.$from->format('Y-m-d').'-sd-'.$to->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
                }),
        ];
    }
}
