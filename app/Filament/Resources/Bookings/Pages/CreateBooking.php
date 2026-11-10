<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingChannel;
use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Setting;
use App\Services\BookingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected static ?string $title = 'Input Reservasi Manual';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $lines = collect($data['lines'] ?? [])
            ->groupBy('unit_type_id')
            ->map(fn ($group) => $group->sum(fn ($line) => (int) $line['qty']))
            ->all();

        try {
            return app(BookingService::class)->createHold([
                'customer' => $data['customer'],
                'lines' => $lines,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => (int) $data['guests'],
                'payment_option' => $data['payment_option'],
                'promo_code' => $data['promo_code'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'channel' => BookingChannel::Manual,
                'created_by' => auth()->id(),
            ]);
        } catch (BookingException $e) {
            Notification::make()->title('Booking belum bisa dibuat')->body($e->getMessage())->danger()->persistent()->send();
            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Booking dibuat. Stok ditahan '.Setting::get('hold_minutes').' menit sampai pembayaran dicatat.';
    }

    protected function getRedirectUrl(): string
    {
        return BookingResource::getUrl('view', ['record' => $this->record]);
    }
}
