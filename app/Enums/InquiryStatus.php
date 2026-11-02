<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status pesan dari form kontak. */
enum InquiryStatus: string implements HasColor, HasLabel
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Spam = 'spam';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Sedang dibalas',
            self::Selesai => 'Selesai',
            self::Spam => 'Spam',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baru => 'info',
            self::Diproses => 'warning',
            self::Selesai => 'success',
            self::Spam => 'gray',
        };
    }
}
