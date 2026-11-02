<?php

namespace App\Filament\Resources\GalleryPhotos\Schemas;

use App\Models\GalleryPhoto;
use App\Support\ImageSizes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GalleryPhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('photo')
                    ->label('Foto')
                    ->collection('photo')
                    ->image()
                    ->acceptedFileTypes(ImageSizes::UPLOAD_MIME_TYPES)
                    ->imageEditor()
                    ->maxSize(8192)
                    ->required()
                    ->helperText('JPG/PNG/WebP maks. 8 MB. Otomatis dikonversi ke WebP 480/960/1600 px.')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label('Keterangan foto')
                    ->helperText('Juga dibaca pembaca layar (aksesibilitas) dan mesin pencari. Jelaskan isi foto.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('category')->label('Kategori')->options(GalleryPhoto::CATEGORIES)->required(),
                TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_featured')->label('Tampil di beranda'),
                Toggle::make('is_active')->label('Tampil di galeri')->default(true),
            ]);
    }
}
