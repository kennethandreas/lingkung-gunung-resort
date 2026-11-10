<?php

namespace App\Filament\Resources\GroupPackages\Pages;

use App\Filament\Resources\GroupPackages\GroupPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroupPackages extends ListRecords
{
    protected static string $resource = GroupPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
