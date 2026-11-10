<?php

namespace App\Filament\Resources\Promos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kode Promo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->helperText('Huruf besar, tanpa spasi. Contoh: LIBURAN10')
                            ->required()
                            ->alphaDash()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')->label('Nama promo')->required()->maxLength(255),
                        Select::make('type')
                            ->label('Jenis diskon')
                            ->options(['percent' => 'Persen (%)', 'fixed' => 'Nominal (Rp)'])
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->label('Besar diskon')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn ($get) => $get('type') === 'percent' ? 100 : null)
                            ->prefix(fn ($get) => $get('type') === 'fixed' ? 'Rp' : null)
                            ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                            ->required(),
                        TextInput::make('max_discount')
                            ->label('Diskon maksimal')
                            ->numeric()->prefix('Rp')
                            ->visible(fn ($get) => $get('type') === 'percent'),
                        TextInput::make('min_total')->label('Minimal transaksi')->numeric()->prefix('Rp')->default(0),
                    ]),
                Section::make('Masa Berlaku & Kuota')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('valid_from')->label('Berlaku mulai')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('valid_to')->label('Berlaku sampai')->native(false)->displayFormat('d/m/Y')->afterOrEqual('valid_from'),
                        TextInput::make('quota')->label('Kuota pemakaian')->helperText('Kosongkan bila tanpa batas.')->numeric()->minValue(1),
                        TextInput::make('used')->label('Sudah dipakai')->numeric()->disabled()->dehydrated(false),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
            ]);
    }
}
