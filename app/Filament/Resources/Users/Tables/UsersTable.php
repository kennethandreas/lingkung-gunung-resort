<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state),
                IconColumn::make('two_factor')
                    ->label('2FA')
                    ->state(fn (User $record) => filled($record->app_authentication_secret))
                    ->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetTwoFactor')
                    ->label('Reset 2FA')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Pengguna harus memasang ulang aplikasi authenticator saat login berikutnya. Gunakan bila HP pengguna hilang atau diganti.')
                    ->visible(fn (User $record) => filled($record->app_authentication_secret) && auth()->user()?->hasRole('owner'))
                    ->action(function (User $record) {
                        $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                        Notification::make()->title('2FA berhasil direset')->success()->send();
                    }),
            ]);
    }
}
