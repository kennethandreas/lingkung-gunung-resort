<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Models\Program;
use App\Support\ImageSizes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Isi')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('section')->label('Halaman')->options(Program::SECTIONS)->required(),
                        TextInput::make('group')
                            ->label('Kelompok')
                            ->placeholder('mis. Adventure & Adrenaline, School, Corporate')
                            ->datalist(fn () => Program::query()->distinct()->orderBy('group')->pluck('group')->all())
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => blank($get('slug')) ? $set('slug', Str::slug($get('section').' '.$get('group').' '.$state)) : null),
                        TextInput::make('slug')->label('Slug')->required()->alphaDash()->unique(ignoreRecord: true),
                        TextInput::make('summary')->label('Ringkasan / tagline')->maxLength(255)->columnSpanFull(),
                        Textarea::make('description')->label('Deskripsi')->rows(5)->columnSpanFull(),
                    ]),
                Section::make('Foto & Tampilan')
                    ->columnSpan(1)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->label('Foto sampul')
                            ->collection('cover')
                            ->image()
                            ->acceptedFileTypes(ImageSizes::UPLOAD_MIME_TYPES)
                            ->imageEditor()
                            ->maxSize(8192)
                            ->helperText('JPG/PNG/WebP maks. 8 MB. Otomatis dikonversi ke WebP.'),
                        Toggle::make('is_featured')->label('Tonjolkan di beranda'),
                        Toggle::make('is_active')->label('Tampil di website')->default(true),
                        TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                    ]),
            ]);
    }
}
