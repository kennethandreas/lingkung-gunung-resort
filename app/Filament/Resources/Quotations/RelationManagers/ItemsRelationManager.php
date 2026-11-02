<?php

namespace App\Filament\Resources\Quotations\RelationManagers;

use App\Models\Quotation;
use App\Services\QuotationService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Item penawaran. Sales bisa menyesuaikan jumlah/harga (mis. hasil negosiasi);
 * subtotal & total selalu dihitung ulang oleh QuotationService, lalu PDF dibuat ulang.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Rincian biaya';

    protected static ?string $modelLabel = 'item';

    public function isReadOnly(): bool
    {
        /** @var Quotation $quotation */
        $quotation = $this->getOwnerRecord();

        // Setelah jadi booking, harga dikunci (ubah lewat booking)
        return $quotation->booking_id !== null || ! auth()->user()?->can('update', $quotation);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('description')->label('Keterangan')->required()->maxLength(255)->columnSpanFull(),
                Select::make('item_type')
                    ->label('Jenis')
                    ->options(['package' => 'Paket', 'activity' => 'Aktivitas', 'additional_item' => 'Item tambahan', 'custom' => 'Lainnya / penyesuaian'])
                    ->default('custom')
                    ->required(),
                TextInput::make('qty')->label('Jumlah')->numeric()->minValue(1)->default(1)->required(),
                TextInput::make('unit_price')->label('Harga satuan')->numeric()->minValue(0)->prefix('Rp')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('description')->label('Item')->wrap(),
                TextColumn::make('qty')->label('Jumlah')->alignCenter(),
                TextColumn::make('unit_price')->label('Harga satuan')->money('IDR', locale: 'id', decimalPlaces: 0),
                TextColumn::make('subtotal')->label('Subtotal')->money('IDR', locale: 'id', decimalPlaces: 0)->weight('bold'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah item')
                    ->mutateDataUsing(fn (array $data) => $data + ['subtotal' => 0])
                    ->after(fn () => $this->recalculate()),
            ])
            ->recordActions([
                EditAction::make()->after(fn () => $this->recalculate()),
                DeleteAction::make()->after(fn () => $this->recalculate()),
            ]);
    }

    private function recalculate(): void
    {
        app(QuotationService::class)->recalculate($this->getOwnerRecord()->fresh('items'));
        $this->dispatch('$refresh');
    }
}
