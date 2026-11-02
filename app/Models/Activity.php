<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Aktivitas berbayar (trekking, rafting, Sanggar Alam, dll).
 * Bukan log aktivitas admin: itu Spatie\Activitylog\Models\Activity.
 */
#[Fillable([
    'slug', 'name', 'category', 'price', 'price_unit', 'min_pax',
    'includes', 'schedule', 'description', 'is_active', 'sort_order',
])]
class Activity extends Model
{
    use LogsChanges;

    public const CATEGORIES = [
        'adventure' => 'Adventure & Adrenaline',
        'sanggar_alam' => 'Sanggar Alam',
        'konsumsi' => 'Konsumsi',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
