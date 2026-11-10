<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function getTabs(): array
    {
        $counts = Review::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            ...collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $status) => [
                $status->value => Tab::make($status->getLabel())
                    ->badge($status === ReviewStatus::Pending ? (($counts[$status->value] ?? 0) ?: null) : null)
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status)),
            ])->all(),
            'semua' => Tab::make('Semua'),
        ];
    }
}
