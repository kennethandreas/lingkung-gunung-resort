<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Varian A/B/C dari paket group (harga per pax, WD/WE). */
#[Fillable([
    'group_package_id', 'code', 'name', 'min_pax', 'menu_set_id', 'coffee_breaks',
    'weekday_only', 'price_weekday', 'price_weekend', 'includes', 'is_active',
])]
class GroupPackageVariant extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'weekday_only' => 'boolean',
            'includes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function groupPackage(): BelongsTo
    {
        return $this->belongsTo(GroupPackage::class);
    }

    public function menuSet(): BelongsTo
    {
        return $this->belongsTo(MenuSet::class);
    }
}
