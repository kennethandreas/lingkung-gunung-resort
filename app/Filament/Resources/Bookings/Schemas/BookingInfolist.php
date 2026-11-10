<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
use App\Models\BookingItem;
use Carbon\Carbon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (TextEntry $entry) => $entry->money('IDR', locale: 'id', decimalPlaces: 0);

        return $schema
            ->columns(3)
            ->components([
                Section::make('Ringkasan')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')->label('Kode booking')->copyable()->weight('bold')->size('lg'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('channel')->label('Sumber')->badge(),
                        TextEntry::make('check_in')->label('Check-in')->date('l, d M Y'),
                        TextEntry::make('check_out')->label('Check-out')->date('l, d M Y'),
                        TextEntry::make('nights')->label('Lama menginap')->suffix(' malam'),
                        TextEntry::make('guests')->label('Jumlah tamu')->suffix(' orang'),
                        TextEntry::make('hold_expires_at')
                            ->label('Stok ditahan sampai')
                            ->dateTime('d M Y H:i')
                            ->visible(fn (Booking $record) => $record->status->isHolding())
                            ->color('warning'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                    ]),
                Section::make('Pemesan')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('customer.name')->label('Nama'),
                        TextEntry::make('customer.phone')
                            ->label('No. HP')
                            ->copyable()
                            ->url(fn (Booking $record) => 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $record->customer->phone)), shouldOpenInNewTab: true),
                        TextEntry::make('customer.email')->label('Email')->placeholder('-'),
                        TextEntry::make('customer.organization')->label('Instansi')->placeholder('-'),
                    ]),
                Section::make('Rincian')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('description')->label('Item')->weight('bold'),
                                TextEntry::make('qty')->label('Jumlah'),
                                TextEntry::make('unit_price')->label('Rata-rata/malam')->money('IDR', locale: 'id', decimalPlaces: 0),
                                TextEntry::make('subtotal')->label('Subtotal')->money('IDR', locale: 'id', decimalPlaces: 0),
                                TextEntry::make('breakdown')
                                    ->label('Harga per malam')
                                    ->columnSpanFull()
                                    ->state(fn (BookingItem $record) => collect($record->breakdown ?? [])
                                        ->map(fn ($night) => Carbon::parse($night['date'])->translatedFormat('D d/m').' ('.strtoupper($night['day_type'] === 'weekday' ? 'WD' : ($night['day_type'] === 'weekend' ? 'WE' : 'HS')).') Rp'.number_format($night['price'], 0, ',', '.'))
                                        ->implode(' · ')),
                                TextEntry::make('units.code')
                                    ->label('Unit ditempati')
                                    ->badge()
                                    ->placeholder('Belum ditugaskan')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Pembayaran')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('subtotal')->label('Subtotal')->tap($money),
                        TextEntry::make('discount')->label('Diskon')->tap($money)->visible(fn (Booking $record) => $record->discount > 0),
                        TextEntry::make('total')->label('Total')->tap($money)->weight('bold'),
                        TextEntry::make('dp_amount')->label('Minimal pembayaran pertama')->tap($money),
                        TextEntry::make('paid_amount')->label('Sudah dibayar')->tap($money)->color('success'),
                        TextEntry::make('outstanding')
                            ->label('Sisa tagihan')
                            ->state(fn (Booking $record) => $record->outstanding())
                            ->tap($money)
                            ->color(fn (Booking $record) => $record->outstanding() > 0 ? 'danger' : 'success'),
                        TextEntry::make('due_date')->label('Batas pelunasan')->date('d M Y')->placeholder('-'),
                    ]),
                Section::make('Catatan')
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('notes')->label('Permintaan tamu')->placeholder('-'),
                        TextEntry::make('internal_notes')->label('Catatan internal')->placeholder('-'),
                        TextEntry::make('cancel_reason')->label('Alasan batal')->placeholder('-')->visible(fn (Booking $record) => filled($record->cancel_reason)),
                    ]),
            ]);
    }
}
