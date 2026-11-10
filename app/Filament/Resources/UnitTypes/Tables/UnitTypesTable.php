<?php

namespace App\Filament\Resources\UnitTypes\Tables;

use App\Enums\DayType;
use App\Models\UnitType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('rates'))
            ->columns([
                TextColumn::make('marketing_name')
                    ->label('Nama')
                    ->description(fn (UnitType $record) => $record->name)
                    ->searchable(['marketing_name', 'name']),
                TextColumn::make('pricing_unit')->label('Satuan')->badge(),
                TextColumn::make('total_units')->label('Stok/malam')->numeric()->alignCenter(),
                TextColumn::make('capacity_pax')->label('Kapasitas')->suffix(' pax')->alignCenter(),
                TextColumn::make('weekday_price')
                    ->label('WD')
                    ->state(fn (UnitType $record) => self::currentPrice($record, DayType::Weekday))
                    ->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('weekend_price')
                    ->label('WE')
                    ->state(fn (UnitType $record) => self::currentPrice($record, DayType::Weekend))
                    ->money('IDR', locale: 'id', decimalPlaces: 0),
                IconColumn::make('is_bookable_online')->label('Online')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    private static function currentPrice(UnitType $record, DayType $dayType): ?int
    {
        return $record->rates
            ->filter(fn ($rate) => $rate->day_type === $dayType && $rate->valid_from->lte(today()) && (! $rate->valid_to || $rate->valid_to->gte(today())))
            ->sortByDesc('valid_from')
            ->first()?->price;
    }
}
