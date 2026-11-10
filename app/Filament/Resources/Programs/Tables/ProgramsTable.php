<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Models\Program;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->defaultGroup(Group::make('group')->label('Kelompok'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->label('')->collection('cover')->conversion('sm')->square()->size(48),
                TextColumn::make('name')->label('Nama')->searchable()->weight('bold')->description(fn (Program $record) => $record->summary),
                TextColumn::make('section')->label('Halaman')->badge()->formatStateUsing(fn ($state) => ucfirst($state)),
                IconColumn::make('is_featured')->label('Beranda')->boolean(),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
            ])
            ->filters([
                SelectFilter::make('section')->label('Halaman')->options(Program::SECTIONS),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
