<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Kedatangan 7 hari ke depan, supaya FO dan admin siap. */
class UpcomingArrivals extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Kedatangan 7 hari ke depan';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Booking::query()
                ->with(['customer', 'items'])
                ->whereBetween('check_in', [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::DpPaid, BookingStatus::FullyPaid, BookingStatus::CheckedIn])
                ->orderBy('check_in'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Belum ada kedatangan dalam 7 hari ke depan')
            ->columns([
                TextColumn::make('check_in')->label('Tiba')->date('D, d M'),
                TextColumn::make('code')->label('Kode')->weight('bold'),
                TextColumn::make('customer.name')->label('Pemesan'),
                TextColumn::make('items.description')->label('Akomodasi')->listWithLineBreaks()->limitList(2),
                TextColumn::make('guests')->label('Tamu')->suffix(' org'),
                TextColumn::make('outstanding')
                    ->label('Sisa tagihan')
                    ->state(fn (Booking $record) => $record->outstanding())
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->color(fn (Booking $record) => $record->outstanding() > 0 ? 'danger' : 'success'),
                TextColumn::make('status')->label('Status')->badge(),
            ]);
    }
}
