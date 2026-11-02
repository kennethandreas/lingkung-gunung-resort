<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Enums\InquiryStatus;
use App\Enums\InquiryTopic;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('handler'))
            ->recordUrl(fn (Inquiry $record) => InquiryResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Pengirim')
                    ->description(fn (Inquiry $record) => $record->phone.($record->email ? " · {$record->email}" : ''))
                    ->searchable(['name', 'phone', 'email'])
                    ->weight(fn (Inquiry $record) => $record->status === InquiryStatus::Baru ? 'bold' : null),
                TextColumn::make('topic')->label('Topik')->badge()->color('gray'),
                TextColumn::make('message')->label('Pesan')->limit(60)->tooltip(fn (Inquiry $record) => $record->message)->wrap()->searchable(),
                TextColumn::make('visit_date')->label('Rencana')->date('d M Y')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('participants')->label('Peserta')->suffix(' org')->placeholder('-')->alignCenter()->toggleable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('handler.name')->label('Ditangani')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Masuk')->since()->dateTimeTooltip('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('topic')->label('Topik')->options(InquiryTopic::class),
                Filter::make('ada_tanggal')->label('Ada rencana tanggal')->query(fn (Builder $query) => $query->whereNotNull('visit_date')),
            ])
            ->recordActions([
                ViewAction::make()->label('Buka'),
            ])
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateHeading('Belum ada pesan di sini')
            ->emptyStateDescription('Pesan dari form "Kirim pertanyaan" di halaman Kontak akan muncul di sini.');
    }
}
