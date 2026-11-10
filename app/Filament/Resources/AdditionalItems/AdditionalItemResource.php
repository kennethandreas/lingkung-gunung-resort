<?php

namespace App\Filament\Resources\AdditionalItems;

use App\Filament\Resources\AdditionalItems\Pages\CreateAdditionalItem;
use App\Filament\Resources\AdditionalItems\Pages\EditAdditionalItem;
use App\Filament\Resources\AdditionalItems\Pages\ListAdditionalItems;
use App\Filament\Resources\AdditionalItems\Schemas\AdditionalItemForm;
use App\Filament\Resources\AdditionalItems\Tables\AdditionalItemsTable;
use App\Models\AdditionalItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AdditionalItemResource extends Resource
{
    protected static ?string $model = AdditionalItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Paket Rombongan';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'item tambahan';

    protected static ?string $pluralModelLabel = 'Item Tambahan';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AdditionalItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdditionalItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdditionalItems::route('/'),
            'create' => CreateAdditionalItem::route('/create'),
            'edit' => EditAdditionalItem::route('/{record}/edit'),
        ];
    }
}
