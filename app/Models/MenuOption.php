<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['menu_set_id', 'meal', 'option_code', 'label', 'items', 'sort_order'])]
class MenuOption extends Model
{
    use LogsChanges;

    public const MEALS = [
        'breakfast' => 'Sarapan',
        'lunch' => 'Makan Siang',
        'dinner' => 'Makan Malam',
        'coffee_break' => 'Coffee Break',
    ];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function menuSet(): BelongsTo
    {
        return $this->belongsTo(MenuSet::class);
    }
}
