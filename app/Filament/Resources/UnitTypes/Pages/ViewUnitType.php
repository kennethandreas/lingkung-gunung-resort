<?php

namespace App\Filament\Resources\UnitTypes\Pages;

use App\Filament\Resources\UnitTypes\UnitTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitType extends ViewRecord
{
    protected static string $resource = UnitTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
