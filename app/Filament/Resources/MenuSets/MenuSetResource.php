<?php

namespace App\Filament\Resources\MenuSets;

use App\Filament\Resources\MenuSets\Pages\CreateMenuSet;
use App\Filament\Resources\MenuSets\Pages\EditMenuSet;
use App\Filament\Resources\MenuSets\Pages\ListMenuSets;
use App\Filament\Resources\MenuSets\RelationManagers\OptionsRelationManager;
use App\Filament\Resources\MenuSets\Schemas\MenuSetForm;
use App\Filament\Resources\MenuSets\Tables\MenuSetsTable;
use App\Models\MenuSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MenuSetResource extends Resource
{
    protected static ?string $model = MenuSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Paket Rombongan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'set menu';

    protected static ?string $pluralModelLabel = 'Menu Makan A-E';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MenuSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuSets::route('/'),
            'create' => CreateMenuSet::route('/create'),
            'edit' => EditMenuSet::route('/{record}/edit'),
        ];
    }
}
