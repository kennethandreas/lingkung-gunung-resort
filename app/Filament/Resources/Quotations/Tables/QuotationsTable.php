<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'variant']))
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->copyable()->weight('bold'),
                TextColumn::make('customer.organization')
                    ->label('Instansi')
                    ->description(fn (Quotation $record) => $record->customer->name.' · '.$record->customer->phone)
                    ->searchable(['organization', 'name', 'phone'])
                    ->placeholder('-'),
                TextColumn::make('variant.name')->label('Paket')->placeholder('-'),
                TextColumn::make('event_date')->label('Tanggal acara')->date('d M Y')->sortable(),
                TextColumn::make('pax')->label('Peserta')->alignCenter(),
                TextColumn::make('total')->label('Estimasi')->money('IDR', locale: 'id', decimalPlaces: 0)->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Masuk')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(QuotationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
