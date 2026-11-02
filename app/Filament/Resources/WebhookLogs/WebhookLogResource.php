<?php

namespace App\Filament\Resources\WebhookLogs;

use App\Filament\Resources\WebhookLogs\Pages\ListWebhookLogs;
use App\Models\WebhookLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Log mentah notifikasi Midtrans (hanya baca) untuk menelusuri masalah pembayaran. */
class WebhookLogResource extends Resource
{
    protected static ?string $model = WebhookLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Pengaturan';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'log webhook';

    protected static ?string $pluralModelLabel = 'Log Webhook Midtrans';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('Diterima')->dateTime('d M Y H:i:s'),
                TextEntry::make('order_id')->label('Order ID')->copyable(),
                IconEntry::make('signature_valid')->label('Signature valid')->boolean(),
                TextEntry::make('result')->label('Hasil'),
                CodeEntry::make('payload')
                    ->label('Payload')
                    ->state(fn (WebhookLog $record) => json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Diterima')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('order_id')->label('Order ID')->searchable()->copyable(),
                TextColumn::make('payload.transaction_status')->label('Status Midtrans')->badge(),
                IconColumn::make('signature_valid')->label('Signature')->boolean(),
                TextColumn::make('result')->label('Hasil')->wrap(),
            ])
            ->filters([
                TernaryFilter::make('signature_valid')->label('Signature valid'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookLogs::route('/'),
        ];
    }
}
