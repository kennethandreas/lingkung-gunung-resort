<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Satuan harga tipe unit. */
enum PricingUnit: string implements HasLabel
{
    case Room = 'room';
    case Tent = 'tent';
    case Person = 'person';
    case Plot = 'plot';

    public function getLabel(): string
    {
        return match ($this) {
            self::Room => 'Per Kamar',
            self::Tent => 'Per Tenda',
            self::Person => 'Per Orang',
            self::Plot => 'Per Kavling',
        };
    }
}
