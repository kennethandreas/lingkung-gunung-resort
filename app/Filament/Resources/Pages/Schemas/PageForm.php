<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Judul')->required()->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug URL')
                    ->helperText('Alamat halaman: /info/{slug}. Jangan ubah slug halaman bawaan (mis. kebijakan-privasi).')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('meta_description')
                    ->label('Deskripsi untuk Google')
                    ->helperText('1-2 kalimat, maks. 160 karakter.')
                    ->maxLength(160)
                    ->columnSpanFull(),
                RichEditor::make('body')
                    ->label('Isi halaman')
                    ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'table'], ['undo', 'redo']])
                    ->columnSpanFull(),
                Toggle::make('is_published')->label('Tampil di website')->default(true),
            ]);
    }
}
