<?php

namespace App\Filament\Resources\GroupPackages;

use App\Filament\Resources\GroupPackages\Pages\CreateGroupPackage;
use App\Filament\Resources\GroupPackages\Pages\EditGroupPackage;
use App\Filament\Resources\GroupPackages\Pages\ListGroupPackages;
use App\Filament\Resources\GroupPackages\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\GroupPackages\Schemas\GroupPackageForm;
use App\Filament\Resources\GroupPackages\Tables\GroupPackagesTable;
use App\Models\GroupPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GroupPackageResource extends Resource
{
    protected static ?string $model = GroupPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Paket Rombongan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'paket group';

    protected static ?string $pluralModelLabel = 'Paket Group';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GroupPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupPackagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroupPackages::route('/'),
            'create' => CreateGroupPackage::route('/create'),
            'edit' => EditGroupPackage::route('/{record}/edit'),
        ];
    }
}
