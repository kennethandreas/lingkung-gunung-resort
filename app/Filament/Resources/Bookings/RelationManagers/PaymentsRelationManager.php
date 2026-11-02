<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Riwayat pembayaran (hanya baca; pembayaran tidak bisa dihapus/diubah). */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat Pembayaran';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('type')->label('Jenis')->badge(),
                TextColumn::make('method')->label('Metode')->badge(),
                TextColumn::make('amount')->label('Nominal')->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('recorder.name')->label('Dicatat oleh')->placeholder('Sistem'),
                TextColumn::make('note')->label('Catatan')->placeholder('-')->limit(40),
                TextColumn::make('proof_path')
                    ->label('Bukti')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat' : null)
                    ->placeholder('-')
                    ->url(fn (Payment $record) => $record->proof_path ? route('admin.payment-proof', $record) : null, shouldOpenInNewTab: true)
                    ->color('primary'),
            ]);
    }
}
