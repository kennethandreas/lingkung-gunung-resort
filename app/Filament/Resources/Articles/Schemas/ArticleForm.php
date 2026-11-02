<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Models\Article;
use App\Support\ImageSizes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Isi artikel')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && blank($get('slug')) ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')
                            ->label('Alamat (slug)')
                            ->prefix('/artikel/')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->helperText('Tampil di daftar artikel dan Google. Maks. 300 karakter.')
                            ->rows(3)
                            ->maxLength(300),
                        RichEditor::make('body')
                            ->label('Isi')
                            ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']])
                            ->required(),
                    ]),
                Section::make('Terbit')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('category')->label('Kategori')->options(Article::CATEGORIES)->required()->native(false)->default('kabar'),
                        Toggle::make('is_published')->label('Tampil di website')->default(false),
                        DateTimePicker::make('published_at')
                            ->label('Tanggal terbit')
                            ->helperText('Isi tanggal di masa depan untuk menjadwalkan.')
                            ->native(false)
                            ->seconds(false)
                            ->default(now())
                            ->required(fn (Get $get) => (bool) $get('is_published')),
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->label('Foto sampul')
                            ->collection('cover')
                            ->image()
                            ->acceptedFileTypes(ImageSizes::UPLOAD_MIME_TYPES)
                            ->imageEditor()
                            ->maxSize(8192)
                            ->helperText('JPG/PNG/WebP maks. 8 MB. Otomatis dikonversi ke WebP.'),
                        TextInput::make('source_url')
                            ->label('Sumber (opsional)')
                            ->helperText('Tautan sumber bila artikel disusun dari dokumen/website lain.')
                            ->url()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
