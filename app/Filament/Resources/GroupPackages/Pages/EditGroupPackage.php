<?php

namespace App\Filament\Resources\GroupPackages\Pages;

use App\Filament\Resources\GroupPackages\GroupPackageResource;
use App\Models\GroupPackage;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditGroupPackage extends EditRecord
{
    protected static string $resource = GroupPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Paket yang sudah punya ulasan tamu tidak dihapus (ulasan ikut hilang); nonaktifkan saja
                ->before(function (DeleteAction $action, GroupPackage $record) {
                    if ($record->reviews()->exists()) {
                        Notification::make()->title('Paket ini sudah punya ulasan tamu')->body('Matikan "Aktif" agar tidak tampil di website, tanpa menghapus ulasannya.')->warning()->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
