<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            ...collect(QuotationStatus::cases())->mapWithKeys(fn (QuotationStatus $status) => [
                $status->value => Tab::make($status->getLabel())->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status)),
            ])->all(),
        ];
    }
}
