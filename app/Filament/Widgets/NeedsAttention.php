<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\Review;
use App\Services\DashboardService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

/** Daftar hal yang perlu ditindaklanjuti hari ini, dengan tautan langsung ke daftar terkait. */
class NeedsAttention extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected string $view = 'filament.widgets.needs-attention';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $links = [
            'payment_action' => BookingResource::getUrl('index'),
            'overdue' => BookingResource::getUrl('index', ['tab' => 'pelunasan']),
            'arrivals_unpaid' => BookingResource::getUrl('index', ['tab' => 'pelunasan']),
            'holds' => BookingResource::getUrl('index', ['tab' => 'menunggu']),
            'quotations' => $user?->can('viewAny', Quotation::class) ? QuotationResource::getUrl('index', ['tab' => 'baru']) : null,
            'inquiries' => $user?->can('viewAny', Inquiry::class) ? InquiryResource::getUrl('index', ['tab' => 'baru']) : null,
            'reviews' => $user?->can('viewAny', Review::class) ? ReviewResource::getUrl('index', ['tab' => 'pending']) : null,
        ];

        // Ulasan hanya untuk staf yang boleh melihatnya (front office tidak)
        $items = array_filter(app(DashboardService::class)->attentionItems(), fn (array $item) => $item['key'] !== 'reviews' || $links['reviews']);

        return [
            'items' => array_values(array_map(fn (array $item) => $item + ['url' => $links[$item['key']] ?? null], $items)),
        ];
    }
}
