<?php

namespace App\Filament\Resources\AdditionalItems\Tables;

use App\Models\AdditionalItem;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdditionalItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (AdditionalItem $record) => $record->unit_label),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn ($state) => AdditionalItem::CATEGORIES[$state] ?? $state),
                TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('min_qty')->label('Min.')->alignCenter(),
                TextColumn::make('includes')->label('Termasuk')->limit(45)->wrap(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(AdditionalItem::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
