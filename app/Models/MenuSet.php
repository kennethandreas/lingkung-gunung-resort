<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Menu Makan A-E untuk paket rombongan. */
#[Fillable(['code', 'name', 'coffee_break_choices'])]
class MenuSet extends Model
{
    use LogsChanges;

    public function options(): HasMany
    {
        return $this->hasMany(MenuOption::class)->orderBy('sort_order');
    }
}
