<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('phone')->label('No. HP / WhatsApp')->tel()->required()->maxLength(30),
                TextInput::make('email')->label('Email')->email()->maxLength(255),
                TextInput::make('organization')->label('Instansi / Perusahaan')->maxLength(255),
            ]);
    }
}
