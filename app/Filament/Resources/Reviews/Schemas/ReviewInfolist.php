<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Review;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Ulasan')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('rating')
                            ->label('Nilai')
                            ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)." ({$state}/5)")
                            ->color('warning'),
                        TextEntry::make('target_type')
                            ->label('Untuk')
                            ->formatStateUsing(fn (Review $record) => $record->target_type->getLabel().': '.$record->targetName())
                            ->url(fn (Review $record) => $record->publicUrl(), shouldOpenInNewTab: true)
                            ->color('primary'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('comment')->label('Isi ulasan')->placeholder('(tanpa komentar)')->columnSpanFull()
                            // Teks pelanggan di-escape dulu, baru baris barunya dijadikan <br>
                            ->formatStateUsing(fn (?string $state) => nl2br(e((string) $state)))->html(),
                        ViewEntry::make('photos')->label('Foto dari tamu')->view('filament.reviews.photos')->columnSpanFull(),
                    ]),
                Section::make('Pengulas')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('reviewer_name')->label('Nama tampil di website')->weight('bold'),
                        TextEntry::make('customerAccount.name')->label('Akun pelanggan'),
                        TextEntry::make('customerAccount.email')->label('Email akun')->copyable(),
                        TextEntry::make('booking.code')
                            ->label('Booking')
                            ->url(fn (Review $record) => $record->booking && auth()->user()->can('view', $record->booking) ? BookingResource::getUrl('view', ['record' => $record->booking]) : null)
                            ->color('primary'),
                        TextEntry::make('booking.check_out')->label('Check-out')->date('d M Y'),
                        TextEntry::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i'),
                        TextEntry::make('edited_at')->label('Terakhir diubah pelanggan')->dateTime('d M Y H:i')->placeholder('-'),
                    ]),
                Section::make('Moderasi')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('moderator.name')->label('Oleh')->placeholder('-'),
                        TextEntry::make('moderated_at')->label('Waktu')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('published_at')->label('Tayang sejak')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('moderation_note')->label('Catatan internal (tidak terlihat pelanggan)')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('customer_message')->label('Pesan untuk pelanggan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
