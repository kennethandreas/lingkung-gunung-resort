<?php

namespace App\Filament\Resources\GroupPackages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GroupPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama paket')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug URL')->required()->alphaDash()->unique(ignoreRecord: true),
                TextInput::make('accommodation')->label('Akomodasi')->placeholder('mis. Tenda Dome')->required()->maxLength(255),
                TextInput::make('sort_order')->label('Urutan tampil')->numeric()->default(0),
                Textarea::make('description')->label('Deskripsi')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
