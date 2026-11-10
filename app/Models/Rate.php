<?php

namespace App\Models;

use App\Enums\DayType;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_type_id', 'day_type', 'price', 'valid_from', 'valid_to'])]
class Rate extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'day_type' => DayType::class,
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }
}
