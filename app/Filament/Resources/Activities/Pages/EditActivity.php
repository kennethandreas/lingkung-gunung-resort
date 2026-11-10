<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\Activity;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Aktivitas yang sudah punya ulasan tamu tidak dihapus (ulasan ikut hilang); nonaktifkan saja
                ->before(function (DeleteAction $action, Activity $record) {
                    if ($record->reviews()->exists()) {
                        Notification::make()->title('Aktivitas ini sudah punya ulasan tamu')->body('Matikan "Aktif" agar tidak tampil di website, tanpa menghapus ulasannya.')->warning()->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
