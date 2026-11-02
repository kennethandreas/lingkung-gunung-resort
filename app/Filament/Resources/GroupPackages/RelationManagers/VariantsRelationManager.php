<?php

namespace App\Filament\Resources\GroupPackages\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutParentUpdate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    use ReadOnlyWithoutParentUpdate;

    protected static string $relationship = 'variants';

    protected static ?string $title = 'Varian A/B/C';

    protected static ?string $modelLabel = 'varian';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')->label('Kode varian')->placeholder('A')->required()->maxLength(5),
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('min_pax')->label('Minimal peserta')->numeric()->minValue(1)->suffix('pax')->required(),
                Select::make('menu_set_id')->label('Set menu')->relationship('menuSet', 'name')->preload(),
                TextInput::make('coffee_breaks')->label('Coffee break per hari')->numeric()->minValue(0)->default(0),
                Toggle::make('weekday_only')->label('Hanya weekday')->inline(false),
                TextInput::make('price_weekday')->label('Harga WD per pax')->numeric()->prefix('Rp')->required(),
                TextInput::make('price_weekend')->label('Harga WE per pax')->numeric()->prefix('Rp')->required(),
                TagsInput::make('includes')->label('Termasuk')->placeholder('Ketik lalu tekan Enter')->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Varian')->weight('bold'),
                TextColumn::make('min_pax')->label('Min. pax')->alignCenter(),
                TextColumn::make('menuSet.name')->label('Menu')->placeholder('-'),
                TextColumn::make('coffee_breaks')->label('Coffee break')->suffix('x')->alignCenter(),
                IconColumn::make('weekday_only')->label('Hanya WD')->boolean(),
                TextColumn::make('price_weekday')->label('WD')->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('price_weekend')->label('WE')->money('IDR', locale: 'id', decimalPlaces: 0),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah varian'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
