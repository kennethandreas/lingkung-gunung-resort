<?php

namespace App\Filament\Resources\CalendarDays\Tables;

use App\Enums\DayType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CalendarDaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date('l, d F Y')->sortable(),
                TextColumn::make('day_type')->label('Dihitung sebagai')->badge(),
                TextColumn::make('note')->label('Keterangan')->searchable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('day_type')->label('Kategori')->options(DayType::class),
                Filter::make('upcoming')
                    ->label('Hanya tanggal mendatang')
                    ->default()
                    ->query(fn ($query) => $query->whereDate('date', '>=', today())),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
