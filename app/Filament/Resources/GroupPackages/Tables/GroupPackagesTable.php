<?php

namespace App\Filament\Resources\GroupPackages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->withCount('variants'))
            ->columns([
                TextColumn::make('name')->label('Paket')->searchable()->weight('bold'),
                TextColumn::make('accommodation')->label('Akomodasi'),
                TextColumn::make('variants_count')->label('Varian')->alignCenter(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
