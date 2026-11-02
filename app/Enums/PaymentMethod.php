<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Metode pembayaran. */
enum PaymentMethod: string implements HasColor, HasLabel
{
    case Midtrans = 'midtrans';
    case Transfer = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Midtrans => 'Midtrans',
            self::Transfer => 'Transfer Manual',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Midtrans => 'info',
            self::Transfer => 'gray',
        };
    }
}
