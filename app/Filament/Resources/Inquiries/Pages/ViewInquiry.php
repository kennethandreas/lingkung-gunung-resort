<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use App\Services\InquiryService;
use App\Support\WhatsApp;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/** @property Inquiry $record */
class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    public function getTitle(): string
    {
        return "Pesan dari {$this->record->name}";
    }

    /** Dipanggil saat tombol "Balas via WhatsApp" diklik: pesan baru menjadi "Sedang dibalas". */
    public function markContacted(): void
    {
        if (auth()->user()->can('update', $this->record)) {
            app(InquiryService::class)->markContacted($this->record, auth()->user());
            $this->record->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('Balas via WhatsApp')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn () => WhatsApp::link(
                    "Halo {$this->record->name}, terima kasih sudah menghubungi Lingkung Gunung lewat website. Terkait pertanyaan Anda ({$this->record->topic->getLabel()}): ",
                    $this->record->whatsAppNumber(),
                ), shouldOpenInNewTab: true)
                // Aksi URL tidak memanggil server; klik Alpine mencatat status tanpa menghalangi tab WhatsApp terbuka.
                ->alpineClickHandler('$wire.markContacted()'),
            Action::make('email')
                ->label('Balas via email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn () => filled($this->record->email))
                ->url(fn () => 'mailto:'.$this->record->email.'?subject='.rawurlencode('Balasan pertanyaan Anda · Lingkung Gunung')),
            Action::make('status')
                ->label('Ubah status / catatan')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn () => auth()->user()->can('update', $this->record))
                ->fillForm(fn () => ['status' => $this->record->status->value, 'staff_note' => $this->record->staff_note])
                ->schema([
                    Select::make('status')->label('Status')->options(InquiryStatus::class)->required()->native(false),
                    Textarea::make('staff_note')->label('Catatan internal (tidak terlihat tamu)')->rows(4)->maxLength(2000),
                ])
                ->action(function (array $data) {
                    $status = $data['status'] instanceof InquiryStatus ? $data['status'] : InquiryStatus::from($data['status']);
                    app(InquiryService::class)->updateStatus($this->record, $status, auth()->user(), $data['staff_note'] ?? '');
                    Notification::make()->title('Pesan diperbarui')->success()->send();
                }),
        ];
    }
}
