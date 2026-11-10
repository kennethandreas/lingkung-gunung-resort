<?php

namespace App\Filament\Resources\UnitTypes\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutParentUpdate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsRelationManager extends RelationManager
{
    use ReadOnlyWithoutParentUpdate;

    protected static string $relationship = 'units';

    protected static ?string $title = 'Unit Fisik';

    protected static ?string $modelLabel = 'unit';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode unit')->required()->maxLength(20),
                TextInput::make('note')->label('Catatan')->maxLength(255),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('note')->label('Catatan')->placeholder('-'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah unit'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
