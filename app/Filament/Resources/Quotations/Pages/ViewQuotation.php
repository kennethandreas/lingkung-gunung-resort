<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Exceptions\BookingException;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\UnitType;
use App\Services\PaymentService;
use App\Services\QuotationService;
use App\Support\WhatsApp;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/** @property Quotation $record */
class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    public function getTitle(): string
    {
        return "Penawaran {$this->record->number}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->whatsAppAction(),
            $this->convertAction(),
            $this->sendPaymentLinkAction(),
            ActionGroup::make([
                $this->statusAction(),
                Action::make('pdf')
                    ->label('Unduh proposal PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn () => route('proposal.pdf', $this->record), shouldOpenInNewTab: true),
                Action::make('publicPage')
                    ->label('Lihat halaman proposal')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => route('proposal.show', $this->record), shouldOpenInNewTab: true),
            ])->label('Lainnya')->button()->color('gray'),
        ];
    }

    private function whatsAppAction(): Action
    {
        return Action::make('whatsapp')
            ->label('Chat klien')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(function () {
                $q = $this->record;
                $message = "Halo {$q->customer->name}, kami dari tim sales Lingkung Gunung terkait penawaran {$q->number} ({$q->variant?->name}, {$q->pax} peserta, {$q->event_date->translatedFormat('d F Y')}). Proposal: ".route('proposal.show', $q);

                return WhatsApp::link($message, preg_replace('/^0/', '62', preg_replace('/\D/', '', $q->customer->phone)));
            }, shouldOpenInNewTab: true)
            // Aksi URL tidak memanggil server (->after() tidak pernah jalan); klik Alpine mencatat status tanpa menghalangi tab WhatsApp.
            ->alpineClickHandler('$wire.markContacted()');
    }

    /** Dipanggil saat tombol "Chat klien" diklik: penawaran baru otomatis menjadi "Dihubungi". */
    public function markContacted(): void
    {
        if ($this->record->status === QuotationStatus::Baru && auth()->user()->can('update', $this->record)) {
            app(QuotationService::class)->updateStatus($this->record, QuotationStatus::Dihubungi, auth()->user());
            $this->record->refresh();
        }
    }

    private function statusAction(): Action
    {
        return Action::make('status')
            ->label('Ubah status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn () => auth()->user()->can('update', $this->record))
            ->fillForm(fn () => ['status' => $this->record->status->value])
            ->schema([
                Select::make('status')->label('Status')->options(QuotationStatus::class)->required(),
            ])
            ->action(function (array $data, Action $action) {
                try {
                    app(QuotationService::class)->updateStatus($this->record, QuotationStatus::from($data['status']), auth()->user());
                } catch (BookingException $e) {
                    Notification::make()->title('Status tidak bisa diubah')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Status diperbarui')->success()->send();
            });
    }

    private function convertAction(): Action
    {
        return Action::make('convert')
            ->label('Konversi ke booking')
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->color('primary')
            ->visible(fn () => ! $this->record->booking_id && $this->record->status !== QuotationStatus::Batal && auth()->user()->can('Convert:Quotation'))
            ->modalHeading('Konversi penawaran menjadi booking')
            ->modalDescription(fn () => 'Total booking = '.PaymentService::rupiah($this->record->total).' ('.$this->record->event_date->translatedFormat('d M Y').', '.$this->record->nights.' malam). Pilih unit yang dialokasikan untuk rombongan agar stok terkunci (aturan sama seperti booking online). Kosongkan bila paket tidak memakai stok unit (mis. tenda dome rombongan).')
            ->schema([
                Repeater::make('lines')
                    ->label('Alokasi unit')
                    ->addActionLabel('Tambah unit')
                    ->defaultItems(0)
                    ->columns(2)
                    ->schema([
                        Select::make('unit_type_id')->label('Tipe unit')->options(UnitType::query()->active()->ordered()->pluck('marketing_name', 'id'))->required(),
                        TextInput::make('qty')->label('Jumlah')->numeric()->minValue(1)->default(1)->required(),
                    ]),
                TextInput::make('hold_minutes')
                    ->label('Lama stok ditahan menunggu DP')
                    ->helperText('Default sama dengan booking online. Bisa diperpanjang untuk proses pembayaran instansi (maks. 7 hari).')
                    ->numeric()->minValue(15)->maxValue(10080)->suffix('menit')
                    ->default(fn () => (int) Setting::get('hold_minutes', 60)),
            ])
            ->action(function (array $data, Action $action) {
                $lines = collect($data['lines'] ?? [])->groupBy('unit_type_id')->map(fn ($g) => $g->sum(fn ($l) => (int) $l['qty']))->all();

                try {
                    $booking = app(QuotationService::class)->convertToBooking($this->record, $lines, auth()->user(), (int) $data['hold_minutes']);
                } catch (BookingException $e) {
                    Notification::make()->title('Konversi gagal')->body($e->getMessage())->danger()->persistent()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title("Booking {$booking->code} dibuat")
                    ->body('Kirim link pembayaran DP ke klien dengan tombol "Kirim link pembayaran".')
                    ->success()
                    ->send();

                $this->record->refresh();
            });
    }

    private function sendPaymentLinkAction(): Action
    {
        return Action::make('paymentLink')
            ->label('Kirim link pembayaran')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn () => (bool) $this->record->booking_id)
            ->url(function () {
                $booking = $this->record->booking;
                $q = $this->record;
                $message = "Halo {$q->customer->name}, penawaran {$q->number} sudah kami konfirmasi menjadi booking {$booking->code}. Total ".PaymentService::rupiah($booking->total)
                    .', DP '.PaymentService::rupiah($booking->dp_amount).'. Silakan lakukan pembayaran melalui link berikut: '.route('booking.show', $booking);

                return WhatsApp::link($message, preg_replace('/^0/', '62', preg_replace('/\D/', '', $q->customer->phone)));
            }, shouldOpenInNewTab: true);
    }

    public function getSubheading(): ?string
    {
        return $this->record->booking ? 'Sudah dikonversi menjadi booking '.$this->record->booking->code : null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            QuotationResource::getUrl() => 'Penawaran B2B',
            $this->record->number,
        ];
    }
}
