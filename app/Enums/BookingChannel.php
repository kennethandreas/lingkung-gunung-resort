<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Asal pemesanan. */
enum BookingChannel: string implements HasColor, HasLabel
{
    case Web = 'web';
    case Manual = 'manual';
    case B2b = 'b2b';

    public function getLabel(): string
    {
        return match ($this) {
            self::Web => 'Website',
            self::Manual => 'Input Manual',
            self::B2b => 'B2B (Penawaran)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Web => 'info',
            self::Manual => 'gray',
            self::B2b => 'primary',
        };
    }
}
