<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status penawaran B2B. */
enum QuotationStatus: string implements HasColor, HasLabel
{
    case Baru = 'baru';
    case Dihubungi = 'dihubungi';
    case Deal = 'deal';
    case Batal = 'batal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dihubungi => 'Dihubungi',
            self::Deal => 'Deal',
            self::Batal => 'Batal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baru => 'info',
            self::Dihubungi => 'warning',
            self::Deal => 'success',
            self::Batal => 'danger',
        };
    }
}
