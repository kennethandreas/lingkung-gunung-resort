<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Models\Faq;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')->label('Pertanyaan')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('answer')->label('Jawaban')->required()->rows(5)->columnSpanFull(),
                Select::make('category')->label('Kategori')->options(Faq::CATEGORIES)->required(),
                TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_active')->label('Tampil di website')->default(true),
            ]);
    }
}
