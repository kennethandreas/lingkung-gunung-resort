<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\ReviewTargetType;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['booking:id,code', 'customerAccount:id,name,email', 'unitType:id,marketing_name', 'activity:id,name', 'groupPackage:id,name'])->withCount('media'))
            ->recordUrl(fn (Review $record) => ReviewResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('rating')
                    ->label('Nilai')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('target_type')
                    ->label('Untuk')
                    ->badge()
                    ->color('gray')
                    ->description(fn (Review $record) => $record->targetName()),
                TextColumn::make('comment')
                    ->label('Ulasan')
                    ->limit(70)
                    ->tooltip(fn (Review $record) => $record->comment)
                    ->placeholder('(tanpa komentar)')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('media_count')->label('Foto')->alignCenter()->placeholder('-')->formatStateUsing(fn ($state) => $state ?: null),
                TextColumn::make('customerAccount.name')
                    ->label('Pelanggan')
                    ->description(fn (Review $record) => $record->booking?->code)
                    ->searchable(['reviewer_name']),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('submitted_at')->label('Dikirim')->since()->dateTimeTooltip('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('target_type')->label('Jenis')->options(ReviewTargetType::class),
                SelectFilter::make('rating')->label('Nilai')->options([5 => '5 bintang', 4 => '4 bintang', 3 => '3 bintang', 2 => '2 bintang', 1 => '1 bintang']),
                Filter::make('dikirim')
                    ->label('Tanggal kirim')
                    ->schema([
                        DatePicker::make('dari')->label('Dari')->native(false),
                        DatePicker::make('sampai')->label('Sampai')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '>=', $date))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()->label('Buka'),
            ])
            ->emptyStateIcon('heroicon-o-star')
            ->emptyStateHeading('Belum ada ulasan di sini')
            ->emptyStateDescription('Ulasan dikirim pelanggan dari akunnya setelah check-in dan masa menginap selesai.');
    }
}
