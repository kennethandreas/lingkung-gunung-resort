<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Unit;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Support\ImageSizes;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/** @property Booking $record */
class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return "Booking {$this->record->code}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->recordPaymentAction(),
            $this->checkInAction(),
            $this->cancelAction(),
        ];
    }

    private function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label('Catat pembayaran')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn () => in_array($this->record->status, [BookingStatus::Pending, BookingStatus::DpPaid], true)
                && auth()->user()->can('update', $this->record))
            ->modalHeading('Catat transfer manual')
            ->modalDescription(fn () => 'Sisa tagihan: '.PaymentService::rupiah($this->record->outstanding())
                .($this->record->paid_amount === 0 ? ' · Minimal pembayaran pertama: '.PaymentService::rupiah($this->record->dp_amount) : ''))
            ->schema([
                TextInput::make('amount')
                    ->label('Nominal diterima')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->default(fn () => $this->record->paid_amount === 0 ? $this->record->dp_amount : $this->record->outstanding()),
                FileUpload::make('proof_path')
                    ->label('Bukti transfer')
                    ->image()
                    ->acceptedFileTypes(ImageSizes::UPLOAD_MIME_TYPES)
                    ->maxSize(5120)
                    ->disk('local')
                    ->directory('bukti-transfer')
                    ->visibility('private')
                    ->required(),
                TextInput::make('note')->label('Catatan')->placeholder('mis. Transfer Mandiri a.n. Budi')->maxLength(255),
            ])
            ->action(function (array $data, Action $action) {
                try {
                    app(PaymentService::class)->recordManualPayment($this->record, (int) $data['amount'], auth()->user(), $data['proof_path'] ?? null, $data['note'] ?? null);
                } catch (BookingException $e) {
                    Notification::make()->title('Pembayaran belum tercatat')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }

                $this->record->refresh();
                Notification::make()->title('Pembayaran tercatat')->body('Status booking: '.$this->record->status->getLabel())->success()->send();
            });
    }

    private function checkInAction(): Action
    {
        return Action::make('checkIn')
            ->label('Check-in')
            ->icon(Heroicon::OutlinedKey)
            ->color('primary')
            ->visible(fn () => $this->record->status === BookingStatus::FullyPaid && auth()->user()->can('update', $this->record))
            ->modalHeading('Check-in tamu')
            ->modalDescription('Pilih unit fisik yang ditempati (boleh dikosongkan dan diisi nanti).')
            ->schema(fn () => $this->record->items->filter->isUnitType()
                ->filter(fn ($item) => Unit::query()->where('unit_type_id', $item->item_id)->exists())
                ->map(fn ($item) => Select::make("units.{$item->id}")
                    ->label("{$item->description} ({$item->qty} unit)")
                    ->multiple()
                    ->maxItems($item->qty)
                    ->options(Unit::query()->where('unit_type_id', $item->item_id)->where('is_active', true)->orderBy('code')->pluck('code', 'id')))
                ->values()
                ->all())
            ->action(function (array $data, Action $action) {
                try {
                    app(BookingService::class)->checkIn($this->record, $data['units'] ?? []);
                } catch (BookingException $e) {
                    Notification::make()->title('Check-in gagal')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }

                $this->record->refresh();
                Notification::make()->title('Tamu berhasil check-in')->success()->send();
            });
    }

    private function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Batalkan')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn () => ! $this->record->status->isFinal() && auth()->user()->can('Cancel:Booking'))
            ->requiresConfirmation()
            ->modalHeading('Batalkan booking?')
            ->modalDescription('Stok langsung dikembalikan. Pengembalian dana (bila ada) diproses manual sesuai kebijakan resort.')
            ->schema([
                Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255),
            ])
            ->action(function (array $data, Action $action) {
                try {
                    app(BookingService::class)->cancel($this->record, $data['reason']);
                } catch (BookingException $e) {
                    Notification::make()->title('Gagal membatalkan')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }

                $this->record->refresh();
                Notification::make()->title('Booking dibatalkan')->success()->send();
            });
    }
}
