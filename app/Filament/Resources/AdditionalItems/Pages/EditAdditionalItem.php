<?php

namespace App\Filament\Resources\AdditionalItems\Pages;

use App\Filament\Resources\AdditionalItems\AdditionalItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdditionalItem extends EditRecord
{
    protected static string $resource = AdditionalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
