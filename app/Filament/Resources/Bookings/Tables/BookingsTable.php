<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'items']))
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->copyable()->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Pemesan')
                    ->description(fn (Booking $record) => $record->customer->phone)
                    ->searchable(['name', 'phone']),
                TextColumn::make('items.description')->label('Akomodasi')->listWithLineBreaks()->limitList(2),
                TextColumn::make('check_in')->label('Check-in')->date('d M Y')->sortable(),
                TextColumn::make('nights')->label('Malam')->alignCenter(),
                TextColumn::make('total')->label('Total')->money('IDR', locale: 'id', decimalPlaces: 0)->sortable(),
                TextColumn::make('paid_amount')->label('Dibayar')->money('IDR', locale: 'id', decimalPlaces: 0)->toggleable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('channel')->label('Sumber')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(BookingStatus::class)->multiple(),
                SelectFilter::make('channel')->label('Sumber')->options(BookingChannel::class),
                Filter::make('check_in')
                    ->label('Tanggal check-in')
                    ->schema([
                        DatePicker::make('from')->label('Dari')->native(false),
                        DatePicker::make('until')->label('Sampai')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('check_in', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('check_in', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
