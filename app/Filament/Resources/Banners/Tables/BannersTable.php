<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->weight('bold')->description(fn ($record) => $record->body),
                TextColumn::make('starts_at')->label('Mulai')->dateTime('d M Y H:i')->placeholder('Segera'),
                TextColumn::make('ends_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('Tanpa batas'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
