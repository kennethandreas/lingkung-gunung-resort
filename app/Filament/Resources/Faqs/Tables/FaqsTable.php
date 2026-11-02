<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Models\Faq;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('question')->label('Pertanyaan')->searchable()->wrap(),
                TextColumn::make('category')->label('Kategori')->badge()->formatStateUsing(fn ($state) => Faq::CATEGORIES[$state] ?? $state),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(Faq::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
