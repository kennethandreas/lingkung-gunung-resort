<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Enums\ReviewStatus;
use App\Exceptions\ReviewException;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/** @property Review $record */
class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    public function getTitle(): string
    {
        return "Ulasan dari {$this->record->reviewer_name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(fn () => $this->record->status === ReviewStatus::Pending ? 'Setujui & tayangkan' : 'Tayangkan lagi')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->canMoveTo(ReviewStatus::Published))
                ->requiresConfirmation()
                ->modalDescription('Ulasan akan tampil di website dan dihitung dalam rata-rata rating.')
                ->action(fn () => $this->moderate(fn (ReviewService $reviews) => $reviews->approve($this->record, auth()->user()), 'Ulasan ditayangkan')),
            Action::make('reject')
                ->label('Tolak')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn () => $this->canMoveTo(ReviewStatus::Rejected))
                ->schema([
                    Textarea::make('note')->label('Alasan (catatan internal, tidak terlihat pelanggan)')->required()->rows(3)->maxLength(2000),
                    Textarea::make('customer_message')->label('Pesan untuk pelanggan (opsional)')->helperText('Tampil di halaman booking pelanggan, mis. "Mohon hapus nomor telepon dari ulasan."')->rows(2)->maxLength(500),
                ])
                ->action(fn (array $data) => $this->moderate(fn (ReviewService $reviews) => $reviews->reject($this->record, auth()->user(), $data['note'], $data['customer_message'] ?? null), 'Ulasan ditolak')),
            Action::make('hide')
                ->label('Sembunyikan')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('gray')
                ->visible(fn () => $this->canMoveTo(ReviewStatus::Hidden))
                ->schema([
                    Textarea::make('note')->label('Alasan (catatan internal, tidak terlihat pelanggan)')->required()->rows(3)->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->moderate(fn (ReviewService $reviews) => $reviews->hide($this->record, auth()->user(), $data['note']), 'Ulasan disembunyikan')),
            Action::make('public')
                ->label('Lihat halaman publik')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn () => $this->record->isPublished() && $this->record->publicUrl())
                ->url(fn () => $this->record->publicUrl(), shouldOpenInNewTab: true),
        ];
    }

    private function canMoveTo(ReviewStatus $to): bool
    {
        return auth()->user()->can('moderate', $this->record) && $this->record->status->canMoveTo($to);
    }

    private function moderate(callable $change, string $title): void
    {
        // Izin dicek lagi di server (aksi Livewire bisa dipanggil langsung tanpa tombol)
        abort_unless(auth()->user()->can('moderate', $this->record), 403);

        try {
            $change(app(ReviewService::class));
        } catch (ReviewException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        $this->record->refresh();
        Notification::make()->title($title)->success()->send();
    }
}
