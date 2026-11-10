<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    public function getTabs(): array
    {
        $counts = Inquiry::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'aktif' => Tab::make('Perlu dibalas')
                ->badge(($counts['baru'] ?? 0) + ($counts['diproses'] ?? 0) ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [InquiryStatus::Baru, InquiryStatus::Diproses])),
            ...collect(InquiryStatus::cases())->mapWithKeys(fn (InquiryStatus $status) => [
                $status->value => Tab::make($status->getLabel())->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status)),
            ])->all(),
            'semua' => Tab::make('Semua'),
        ];
    }
}
