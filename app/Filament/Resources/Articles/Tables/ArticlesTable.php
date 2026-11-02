<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Models\Article;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->label('')->collection('cover')->conversion('sm')->imageHeight(48)->square(),
                TextColumn::make('title')->label('Judul')->searchable()->weight('bold')->wrap()->description(fn (Article $record) => $record->excerpt ? str($record->excerpt)->limit(80) : null),
                TextColumn::make('category')->label('Kategori')->badge()->color('gray')->formatStateUsing(fn (string $state) => Article::CATEGORIES[$state] ?? $state),
                IconColumn::make('is_published')->label('Tampil')->boolean(),
                TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y')->sortable()->placeholder('-'),
                TextColumn::make('updated_at')->label('Diubah')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(Article::CATEGORIES),
            ])
            ->recordActions([
                Action::make('lihat')->label('Lihat')->icon('heroicon-m-arrow-top-right-on-square')->visible(fn (Article $record) => $record->is_published)->url(fn (Article $record) => route('artikel.show', $record), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateHeading('Belum ada artikel')
            ->emptyStateDescription('Tulis kabar, tips, atau cerita acara untuk ditampilkan di halaman Artikel.');
    }
}
