<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['booking_id', 'item_type', 'item_id', 'description', 'qty', 'pax', 'unit_price', 'subtotal', 'breakdown'])]
class BookingItem extends Model
{
    public const TYPE_UNIT_TYPE = 'unit_type';

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_ADDITIONAL_ITEM = 'additional_item';

    /** Paket rombongan dari penawaran B2B (item_id = quotation id). */
    public const TYPE_PACKAGE = 'package';

    protected function casts(): array
    {
        return ['breakdown' => 'array'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'item_id');
    }

    /** Unit fisik yang ditugaskan saat check-in. */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withTimestamps();
    }

    public function isUnitType(): bool
    {
        return $this->item_type === self::TYPE_UNIT_TYPE;
    }
}
