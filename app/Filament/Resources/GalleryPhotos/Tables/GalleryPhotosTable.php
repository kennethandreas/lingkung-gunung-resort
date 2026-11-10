<?php

namespace App\Filament\Resources\GalleryPhotos\Tables;

use App\Models\GalleryPhoto;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GalleryPhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')->label('Foto')->collection('photo')->conversion('sm')->height(64),
                TextColumn::make('title')->label('Keterangan')->searchable()->wrap(),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn ($state) => GalleryPhoto::CATEGORIES[$state] ?? $state),
                IconColumn::make('is_featured')->label('Beranda')->boolean(),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(GalleryPhoto::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
