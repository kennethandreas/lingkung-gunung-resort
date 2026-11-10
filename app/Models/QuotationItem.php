<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quotation_id', 'item_type', 'item_id', 'description', 'qty', 'unit_price', 'subtotal', 'meta'])]
class QuotationItem extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
