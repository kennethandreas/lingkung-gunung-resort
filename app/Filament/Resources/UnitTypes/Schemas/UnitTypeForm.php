<?php

namespace App\Filament\Resources\UnitTypes\Schemas;

use App\Enums\PricingUnit;
use App\Support\ImageSizes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UnitTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('marketing_name')
                            ->label('Nama di website')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => blank($get('slug')) ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('name')
                            ->label('Nama di daftar harga')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->helperText('Dipakai di alamat halaman, mis. /akomodasi/glamourcamp')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('tagline')->maxLength(255),
                        Textarea::make('description')->label('Deskripsi')->rows(4)->columnSpanFull(),
                    ]),
                Section::make('Foto')
                    ->description('Foto pertama menjadi sampul. Seret untuk mengubah urutan. Otomatis dikonversi ke WebP 480/960/1600 px.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->hiddenLabel()
                            ->collection('photos')
                            ->multiple()
                            ->reorderable()
                            ->panelLayout('grid')
                            ->image()
                            ->acceptedFileTypes(ImageSizes::UPLOAD_MIME_TYPES)
                            ->imageEditor()
                            ->maxSize(8192)
                            ->maxFiles(12),
                    ]),
                Section::make('Kapasitas & Stok')
                    ->columns(3)
                    ->schema([
                        Select::make('pricing_unit')
                            ->label('Satuan harga')
                            ->options(PricingUnit::class)
                            ->required()
                            ->helperText('Per Orang: stok dan harga dihitung per orang.'),
                        TextInput::make('total_units')
                            ->label('Stok harian')
                            ->helperText('Jumlah unit (atau jumlah orang untuk satuan per orang) yang bisa dijual per malam.')
                            ->numeric()->minValue(0)->required(),
                        TextInput::make('capacity_pax')
                            ->label('Kapasitas per unit (pax)')
                            ->numeric()->minValue(1)->required(),
                        TextInput::make('breakfast_pax')
                            ->label('Sarapan termasuk (pax)')
                            ->numeric()->minValue(0)->default(0),
                        TextInput::make('extra_pax_price')
                            ->label('Harga pax tambahan')
                            ->helperText('Kosongkan bila belum ditentukan.')
                            ->numeric()->minValue(0)->prefix('Rp'),
                        TextInput::make('bed_configuration')->label('Konfigurasi tempat tidur'),
                    ]),
                Section::make('Fasilitas & Status')
                    ->columns(3)
                    ->schema([
                        TagsInput::make('includes')
                            ->label('Termasuk')
                            ->placeholder('Ketik lalu tekan Enter')
                            ->columnSpanFull(),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                        Toggle::make('is_bookable_online')->label('Bisa dipesan online')->default(true),
                        TextInput::make('sort_order')->label('Urutan tampil')->numeric()->default(0),
                    ]),
            ]);
    }
}
