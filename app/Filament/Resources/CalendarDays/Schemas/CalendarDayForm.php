<?php

namespace App\Filament\Resources\CalendarDays\Schemas;

use App\Enums\DayType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CalendarDayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal (malam menginap)')
                    ->helperText('Tarif berlaku untuk tamu yang menginap pada malam tanggal ini.')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->unique(ignoreRecord: true),
                Select::make('day_type')
                    ->label('Dihitung sebagai')
                    ->options(DayType::class)
                    ->required()
                    ->helperText('Musim Ramai tanpa tarif khusus otomatis memakai tarif weekend.'),
                TextInput::make('note')
                    ->label('Keterangan')
                    ->placeholder('mis. Libur Natal')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
