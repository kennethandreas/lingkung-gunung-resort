<?php

namespace App\Filament\Resources\MenuSets\Pages;

use App\Filament\Resources\MenuSets\MenuSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuSet extends EditRecord
{
    protected static string $resource = MenuSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
