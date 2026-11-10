<?php

namespace App\Filament\Resources\MenuSets\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuSetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->modifyQueryUsing(fn ($query) => $query->withCount('options'))
            ->columns([
                TextColumn::make('code')->label('Kode')->weight('bold'),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('options_count')->label('Jumlah opsi')->alignCenter(),
                TextColumn::make('coffee_break_choices')->label('Pilihan coffee break')->alignCenter(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
