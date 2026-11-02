<?php

namespace App\Filament\Resources\UnitTypes\Pages;

use App\Filament\Resources\UnitTypes\UnitTypeResource;
use App\Services\BookingService;
use Filament\Resources\Pages\EditRecord;

class EditUnitType extends EditRecord
{
    protected static string $resource = UnitTypeResource::class;

    protected function afterSave(): void
    {
        // Stok tanggal mendatang ikut berubah bila jumlah unit diubah
        if ($this->record->wasChanged('total_units')) {
            app(BookingService::class)->syncCapacity($this->record);
        }
    }
}
