<?php

namespace App\Filament\Resources\MenuSets\Pages;

use App\Filament\Resources\MenuSets\MenuSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenuSets extends ListRecords
{
    protected static string $resource = MenuSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
