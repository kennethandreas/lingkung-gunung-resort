<?php

namespace App\Filament\Resources\MenuSets\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutParentUpdate;
use App\Models\MenuOption;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    use ReadOnlyWithoutParentUpdate;

    protected static string $relationship = 'options';

    protected static ?string $title = 'Isi Menu';

    protected static ?string $modelLabel = 'opsi menu';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('meal')->label('Waktu makan')->options(MenuOption::MEALS)->required(),
                TextInput::make('option_code')
                    ->label('Kode opsi')
                    ->helperText('A-E untuk makan; nama kelompok untuk coffee break.')
                    ->required()->maxLength(30),
                TextInput::make('label')->label('Label')->maxLength(255),
                TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                TagsInput::make('items')->label('Isi hidangan')->placeholder('Ketik lalu tekan Enter')->required()->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('option_code')
            ->defaultSort('sort_order')
            ->defaultGroup(Group::make('meal')->label('Waktu makan')->getTitleFromRecordUsing(fn (MenuOption $record) => MenuOption::MEALS[$record->meal] ?? $record->meal))
            ->columns([
                TextColumn::make('label')->label('Opsi')->weight('bold'),
                TextColumn::make('items')->label('Isi')->badge()->separator(','),
            ])
            ->filters([
                SelectFilter::make('meal')->label('Waktu makan')->options(MenuOption::MEALS),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah opsi'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
