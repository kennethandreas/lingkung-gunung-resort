<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Judul promo')->placeholder('mis. Diskon 10% weekday bulan ini')->required()->maxLength(120)->columnSpanFull(),
                TextInput::make('body')->label('Keterangan singkat')->maxLength(255)->columnSpanFull(),
                TextInput::make('cta_label')->label('Teks tombol')->placeholder('mis. Pesan sekarang')->maxLength(40),
                TextInput::make('cta_url')->label('Tautan tombol')->placeholder('/pesan atau https://...')->maxLength(255),
                DateTimePicker::make('starts_at')->label('Mulai tayang')->native(false)->seconds(false),
                DateTimePicker::make('ends_at')->label('Selesai tayang')->native(false)->seconds(false)->afterOrEqual('starts_at'),
                TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
            ]);
    }
}
