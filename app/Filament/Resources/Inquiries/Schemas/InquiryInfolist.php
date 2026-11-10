<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pesan')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('topic')->label('Topik')->badge()->color('gray'),
                        TextEntry::make('visit_date')->label('Rencana tanggal')->date('l, d M Y')->placeholder('-'),
                        TextEntry::make('participants')->label('Peserta')->suffix(' orang')->placeholder('-'),
                        TextEntry::make('message')->label('Isi pesan')->columnSpanFull()->prose()->extraAttributes(['class' => 'whitespace-pre-line']),
                    ]),
                Section::make('Pengirim')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('name')->label('Nama')->weight('bold'),
                        TextEntry::make('phone')
                            ->label('WhatsApp')
                            ->copyable()
                            ->url(fn (Inquiry $record) => 'https://wa.me/'.$record->whatsAppNumber(), shouldOpenInNewTab: true)
                            ->color('primary'),
                        TextEntry::make('email')->label('Email')->placeholder('-')->copyable(),
                        TextEntry::make('customerAccount.email')->label('Akun pelanggan')->placeholder('Tidak login'),
                        TextEntry::make('created_at')->label('Masuk')->dateTime('d M Y H:i'),
                    ]),
                Section::make('Penanganan')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('handler.name')->label('Ditangani oleh')->placeholder('-'),
                        TextEntry::make('handled_at')->label('Pertama ditangani')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('staff_note')->label('Catatan internal')->placeholder('Belum ada catatan')->columnSpanFull(),
                    ]),
            ]);
    }
}
