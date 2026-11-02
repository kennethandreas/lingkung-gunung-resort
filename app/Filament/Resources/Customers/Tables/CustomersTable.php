<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('bookings'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('phone')->label('No. HP')->searchable()->copyable(),
                TextColumn::make('email')->label('Email')->searchable()->placeholder('-'),
                TextColumn::make('organization')->label('Instansi')->searchable()->placeholder('-'),
                TextColumn::make('bookings_count')->label('Booking')->alignCenter(),
                TextColumn::make('created_at')->label('Terdaftar')->date('d M Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
