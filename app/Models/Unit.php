<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_type_id', 'code', 'is_active', 'note'])]
class Unit extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }
}
