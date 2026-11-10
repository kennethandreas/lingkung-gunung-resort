<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Activity;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Aktivitas')->searchable()->weight('bold'),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn ($state) => Activity::CATEGORIES[$state] ?? $state),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->description(fn (Activity $record) => "per {$record->price_unit}"),
                TextColumn::make('min_pax')->label('Min. pax')->alignCenter(),
                TextColumn::make('includes')->label('Termasuk')->limit(50)->wrap(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(Activity::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
