<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input reservasi manual'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'menunggu' => Tab::make('Menunggu bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::Pending)),
            'pelunasan' => Tab::make('Menunggu pelunasan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', BookingStatus::DpPaid)->orderBy('due_date')),
            'checkin_hari_ini' => Tab::make('Check-in hari ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('check_in', today())
                    ->whereIn('status', [BookingStatus::DpPaid, BookingStatus::FullyPaid, BookingStatus::CheckedIn])),
            'mendatang' => Tab::make('Mendatang')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('check_in', '>=', today())
                    ->whereIn('status', [BookingStatus::DpPaid, BookingStatus::FullyPaid])
                    ->reorder('check_in')),
        ];
    }
}
