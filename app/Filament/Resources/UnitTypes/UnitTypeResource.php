<?php

namespace App\Filament\Resources\UnitTypes;

use App\Filament\Resources\UnitTypes\Pages\CreateUnitType;
use App\Filament\Resources\UnitTypes\Pages\EditUnitType;
use App\Filament\Resources\UnitTypes\Pages\ListUnitTypes;
use App\Filament\Resources\UnitTypes\Pages\ViewUnitType;
use App\Filament\Resources\UnitTypes\RelationManagers\RatesRelationManager;
use App\Filament\Resources\UnitTypes\RelationManagers\UnitsRelationManager;
use App\Filament\Resources\UnitTypes\Schemas\UnitTypeForm;
use App\Filament\Resources\UnitTypes\Tables\UnitTypesTable;
use App\Models\UnitType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnitTypeResource extends Resource
{
    protected static ?string $model = UnitType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|UnitEnum|null $navigationGroup = 'Akomodasi & Harga';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'tipe unit';

    protected static ?string $pluralModelLabel = 'Tipe Unit & Harga';

    protected static ?string $recordTitleAttribute = 'marketing_name';

    public static function form(Schema $schema): Schema
    {
        return UnitTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RatesRelationManager::class,
            UnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnitTypes::route('/'),
            'create' => CreateUnitType::route('/create'),
            'view' => ViewUnitType::route('/{record}'),
            'edit' => EditUnitType::route('/{record}/edit'),
        ];
    }
}
