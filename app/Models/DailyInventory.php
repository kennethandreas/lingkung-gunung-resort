<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stok satu tipe unit pada satu tanggal (malam menginap).
 * Hanya diubah lewat App\Services\BookingService.
 */
#[Fillable(['unit_type_id', 'date', 'capacity', 'booked', 'held', 'is_blocked', 'block_note'])]
class DailyInventory extends Model
{
    protected $table = 'daily_inventory';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_blocked' => 'boolean',
        ];
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function available(): int
    {
        if ($this->is_blocked) {
            return 0;
        }

        return max(0, $this->capacity - $this->booked - $this->held);
    }
}
