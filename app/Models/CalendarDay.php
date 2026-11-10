<?php

namespace App\Models;

use App\Enums\DayType;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'day_type', 'note'])]
class CalendarDay extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'day_type' => DayType::class,
        ];
    }
}
