<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tulis artikel'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'terbit' => Tab::make('Terbit')->modifyQueryUsing(fn (Builder $q) => $q->where('is_published', true)->where('published_at', '<=', now())),
            'terjadwal' => Tab::make('Terjadwal')->modifyQueryUsing(fn (Builder $q) => $q->where('is_published', true)->where('published_at', '>', now())),
            'draf' => Tab::make('Draf')->modifyQueryUsing(fn (Builder $q) => $q->where('is_published', false)),
        ];
    }
}
