<?php

namespace App\Filament\Resources\MenuSets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode')->placeholder('A')->required()->maxLength(5)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama')->placeholder('Menu Makan A')->required()->maxLength(255),
                TextInput::make('coffee_break_choices')
                    ->label('Jumlah pilihan coffee break')
                    ->helperText('Brosur: "Untuk coffee break pilih 2 macam menu".')
                    ->numeric()->minValue(0)->default(2),
            ]);
    }
}
