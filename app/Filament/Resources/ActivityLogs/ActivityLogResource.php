<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Jejak siapa mengubah apa (hanya baca). */
class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Pengaturan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'log aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s'),
                TextEntry::make('causer.name')->label('Oleh')->placeholder('Sistem'),
                TextEntry::make('subject_type')->label('Data')->formatStateUsing(fn ($state) => ActivityLog::SUBJECT_LABELS[$state] ?? class_basename((string) $state)),
                TextEntry::make('description')->label('Kejadian'),
                KeyValueEntry::make('old')
                    ->label('Sebelum')
                    ->state(fn (ActivityLog $record) => self::flatten($record->attribute_changes?->get('old'))),
                KeyValueEntry::make('new')
                    ->label('Sesudah')
                    ->state(fn (ActivityLog $record) => self::flatten($record->attribute_changes?->get('attributes') ?? $record->properties?->all())),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('causer.name')->label('Oleh')->placeholder('Sistem')->searchable(),
                TextColumn::make('event')->label('Kejadian')->badge()->formatStateUsing(fn ($state) => match ($state) {
                    'created' => 'Dibuat',
                    'updated' => 'Diubah',
                    'deleted' => 'Dihapus',
                    default => $state,
                })->placeholder('-'),
                TextColumn::make('subject_type')->label('Data')->formatStateUsing(fn ($state) => ActivityLog::SUBJECT_LABELS[$state] ?? class_basename((string) $state))->placeholder('-'),
                TextColumn::make('subject_id')->label('ID')->placeholder('-'),
                TextColumn::make('description')->label('Keterangan')->limit(60),
            ])
            ->filters([
                SelectFilter::make('subject_type')->label('Data')->options(ActivityLog::SUBJECT_LABELS),
                SelectFilter::make('event')->label('Kejadian')->options(['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus']),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /** Ubah nilai bersarang menjadi teks agar mudah dibaca. */
    private static function flatten(?array $values): array
    {
        return collect($values ?? [])
            ->map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE))
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
