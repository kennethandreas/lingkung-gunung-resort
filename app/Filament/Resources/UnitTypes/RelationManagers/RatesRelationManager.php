<?php

namespace App\Filament\Resources\UnitTypes\RelationManagers;

use App\Enums\DayType;
use App\Filament\Concerns\ReadOnlyWithoutParentUpdate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    use ReadOnlyWithoutParentUpdate;

    protected static string $relationship = 'rates';

    protected static ?string $title = 'Harga';

    protected static ?string $modelLabel = 'harga';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day_type')
                    ->label('Kategori hari')
                    ->options(DayType::class)
                    ->required(),
                TextInput::make('price')
                    ->label('Harga per malam')
                    ->helperText('Per unit, atau per orang untuk satuan Per Orang.')
                    ->numeric()->minValue(0)->prefix('Rp')->required(),
                DatePicker::make('valid_from')->label('Berlaku mulai')->required()->native(false)->displayFormat('d/m/Y'),
                DatePicker::make('valid_to')
                    ->label('Berlaku sampai')
                    ->helperText('Kosongkan bila berlaku seterusnya.')
                    ->native(false)->displayFormat('d/m/Y')
                    ->afterOrEqual('valid_from'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_type')
            ->defaultSort('valid_from', 'desc')
            ->columns([
                TextColumn::make('day_type')->label('Kategori')->badge(),
                TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('valid_from')->label('Mulai')->date('d M Y'),
                TextColumn::make('valid_to')->label('Sampai')->date('d M Y')->placeholder('Seterusnya'),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah harga'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
