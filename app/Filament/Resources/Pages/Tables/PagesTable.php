<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->weight('bold'),
                TextColumn::make('slug')->label('Alamat')->formatStateUsing(fn ($state) => "/info/{$state}"),
                IconColumn::make('is_published')->label('Tampil')->boolean(),
                TextColumn::make('updated_at')->label('Diubah')->since(),
            ])
            ->recordActions([
                Action::make('lihat')->label('Lihat')->icon('heroicon-m-arrow-top-right-on-square')->url(fn (Page $record) => route('halaman', $record->slug), shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }
}
