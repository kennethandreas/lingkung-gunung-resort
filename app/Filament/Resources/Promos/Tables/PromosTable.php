<?php

namespace App\Filament\Resources\Promos\Tables;

use App\Models\Promo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->copyable()->weight('bold'),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('value')
                    ->label('Diskon')
                    ->formatStateUsing(fn (Promo $record) => $record->type === 'percent'
                        ? "{$record->value}%"
                        : 'Rp'.number_format($record->value, 0, ',', '.')),
                TextColumn::make('used')
                    ->label('Terpakai')
                    ->formatStateUsing(fn (Promo $record) => $record->used.' / '.($record->quota ?? '∞')),
                TextColumn::make('valid_to')->label('Berlaku sampai')->date('d M Y')->placeholder('Tanpa batas'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
