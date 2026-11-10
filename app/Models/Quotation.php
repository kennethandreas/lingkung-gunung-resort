<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Models\Concerns\LogsChanges;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Proposal B2B dari Package Builder (dipakai penuh di F3). */
#[Fillable([
    'number', 'code', 'customer_id', 'group_package_variant_id', 'event_date', 'days', 'nights', 'pax',
    'menu_plan', 'status', 'subtotal', 'total', 'pdf_path', 'contacted_at', 'notes', 'booking_id', 'handled_by',
])]
class Quotation extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'event_date' => 'date',
            'menu_plan' => 'array',
            'contacted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function checkOut(): Carbon
    {
        return $this->event_date->copy()->addDays($this->nights);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GroupPackageVariant::class, 'group_package_variant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
