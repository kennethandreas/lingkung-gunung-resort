<?php

namespace App\Filament\Resources\AdditionalItems\Pages;

use App\Filament\Resources\AdditionalItems\AdditionalItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdditionalItems extends ListRecords
{
    protected static string $resource = AdditionalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
