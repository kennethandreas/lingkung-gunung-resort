<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Kategori hari untuk tarif. */
enum DayType: string implements HasColor, HasLabel
{
    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case HighSeason = 'high_season';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weekday => 'Weekday (WD)',
            self::Weekend => 'Weekend (WE)',
            self::HighSeason => 'Musim Ramai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Weekday => 'gray',
            self::Weekend => 'info',
            self::HighSeason => 'danger',
        };
    }
}
