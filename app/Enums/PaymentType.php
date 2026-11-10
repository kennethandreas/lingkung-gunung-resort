<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Jenis pembayaran. */
enum PaymentType: string implements HasColor, HasLabel
{
    case Dp = 'dp';
    case Settlement = 'settlement';
    case Full = 'full';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dp => 'DP',
            self::Settlement => 'Pelunasan',
            self::Full => 'Lunas Langsung',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dp => 'info',
            self::Settlement => 'primary',
            self::Full => 'success',
        };
    }
}
