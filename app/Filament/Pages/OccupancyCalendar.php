<?php

namespace App\Filament\Pages;

use App\Exceptions\BookingException;
use App\Models\DailyInventory;
use App\Models\UnitType;
use App\Services\BookingService;
use App\Services\PricingService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Kalender okupansi: stok per tipe unit per tanggal + blokir tanggal.
 */
class OccupancyCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.occupancy-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional Harian';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Kalender Okupansi';

    protected static ?string $title = 'Kalender Okupansi';

    public string $start = '';

    public int $days = 14;

    public function mount(): void
    {
        $this->start = today()->toDateString();
    }

    public function shift(int $days): void
    {
        $this->start = CarbonImmutable::parse($this->start)->addDays($days)->toDateString();
    }

    public function goToday(): void
    {
        $this->start = today()->toDateString();
    }

    public function updatedDays(): void
    {
        $this->days = in_array($this->days, [7, 14, 30], true) ? $this->days : 14;
    }

    protected function getViewData(): array
    {
        $from = CarbonImmutable::parse($this->start);
        $to = $from->addDays($this->days - 1);
        $unitTypes = UnitType::query()->active()->ordered()->get();

        app(BookingService::class)->ensureInventory($unitTypes->pluck('id')->all(), $from, $to);

        $rows = DailyInventory::query()
            ->whereIn('unit_type_id', $unitTypes->pluck('id'))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('unit_type_id')
            ->map(fn ($group) => $group->keyBy(fn ($row) => $row->date->toDateString()));

        $pricing = app(PricingService::class);
        $dates = $pricing->nights($from, $to->addDay())->map(fn ($date) => [
            'date' => $date,
            'day_type' => $pricing->dayTypeFor($date),
        ]);

        return compact('unitTypes', 'rows', 'dates');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('block')
                ->label('Blokir / buka tanggal')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('gray')
                ->visible(fn () => auth()->user()->can('Block:OccupancyCalendar'))
                ->modalDescription('Blokir untuk perawatan atau stok yang sudah terjual di OTA. Booking yang sudah ada tidak terpengaruh.')
                ->schema([
                    Select::make('unit_type_id')
                        ->label('Tipe unit')
                        ->options(UnitType::query()->ordered()->pluck('marketing_name', 'id'))
                        ->required(),
                    DatePicker::make('from')->label('Dari tanggal')->required()->native(false)->displayFormat('d/m/Y'),
                    DatePicker::make('to')->label('Sampai tanggal')->required()->native(false)->displayFormat('d/m/Y')->afterOrEqual('from'),
                    Radio::make('blocked')
                        ->label('Aksi')
                        ->options(['1' => 'Blokir (tidak bisa dipesan)', '0' => 'Buka blokir'])
                        ->default('1')
                        ->required(),
                    TextInput::make('note')->label('Keterangan')->placeholder('mis. Perawatan atap')->maxLength(255),
                ])
                ->action(function (array $data, Action $action) {
                    $blocked = (int) $data['blocked'] === 1; // nilai radio bisa berupa '1' atau 1

                    try {
                        $count = app(BookingService::class)->setBlocked(
                            UnitType::findOrFail($data['unit_type_id']),
                            CarbonImmutable::parse($data['from']),
                            CarbonImmutable::parse($data['to']),
                            $blocked,
                            $data['note'] ?? null,
                        );
                    } catch (BookingException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        $action->halt();
                    }

                    activity()->causedBy(auth()->user())
                        ->withProperties($data)
                        ->log($blocked ? 'Blokir tanggal' : 'Buka blokir tanggal');

                    Notification::make()->title("{$count} tanggal diperbarui")->success()->send();
                }),
        ];
    }
}
