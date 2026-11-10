<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Models\Activity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama aktivitas')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug URL')->required()->alphaDash()->unique(ignoreRecord: true),
                Select::make('category')->label('Kategori')->options(Activity::CATEGORIES)->required(),
                TextInput::make('price')->label('Harga')->numeric()->minValue(0)->prefix('Rp')->required(),
                Select::make('price_unit')->label('Satuan harga')->options(['pax' => 'Per pax', 'ekor' => 'Per ekor', 'paket' => 'Per paket'])->required(),
                TextInput::make('min_pax')->label('Minimal peserta')->numeric()->minValue(1)->default(1),
                TextInput::make('schedule')->label('Jam kegiatan')->placeholder('mis. 09.00 - 15.00')->maxLength(100),
                TextInput::make('sort_order')->label('Urutan tampil')->numeric()->default(0),
                Textarea::make('includes')->label('Termasuk')->rows(2)->columnSpanFull(),
                Textarea::make('description')->label('Deskripsi')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
