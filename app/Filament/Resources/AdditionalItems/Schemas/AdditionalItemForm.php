<?php

namespace App\Filament\Resources\AdditionalItems\Schemas;

use App\Models\AdditionalItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdditionalItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama item')->required()->maxLength(255),
                Select::make('category')->label('Kategori')->options(AdditionalItem::CATEGORIES)->required(),
                Select::make('unit')
                    ->label('Satuan')
                    ->options(['sett' => 'Sett', 'area' => 'Area', 'pax' => 'Pax', 'porsi' => 'Porsi', 'kursi' => 'Kursi', 'meja' => 'Meja', 'paket' => 'Paket'])
                    ->required(),
                TextInput::make('unit_label')->label('Keterangan satuan')->placeholder('mis. 1 Sett (Max 20 Orang, U Shape)')->maxLength(255),
                TextInput::make('capacity')->label('Kapasitas (orang)')->numeric()->minValue(1),
                Select::make('layout')->label('Layout')->options(['U Shape' => 'U Shape', 'Lesehan' => 'Lesehan', 'Classroom' => 'Classroom']),
                TextInput::make('price')->label('Harga')->numeric()->minValue(0)->prefix('Rp')->required(),
                TextInput::make('min_qty')->label('Minimal pesan')->numeric()->minValue(1)->default(1),
                TextInput::make('includes')->label('Termasuk')->maxLength(255)->columnSpanFull(),
                TextInput::make('sort_order')->label('Urutan tampil')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
            ]);
    }
}
