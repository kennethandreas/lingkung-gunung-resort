<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status booking (dokumen teknis bab 6). */
enum BookingStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case DpPaid = 'dp_paid';
    case FullyPaid = 'fully_paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case CheckedIn = 'checked_in';

    /** Status yang menahan stok sementara (kolom held). */
    public function isHolding(): bool
    {
        return $this === self::Pending;
    }

    /** Status yang sudah memakai stok permanen (kolom booked). */
    public function isConfirmed(): bool
    {
        return in_array($this, [self::DpPaid, self::FullyPaid, self::CheckedIn], true);
    }

    /** Status akhir: tidak bisa diubah lagi. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Expired, self::Cancelled, self::CheckedIn], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::DpPaid => 'DP Dibayar',
            self::FullyPaid => 'Lunas',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::CheckedIn => 'Sudah Check-in',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::DpPaid => 'info',
            self::FullyPaid => 'success',
            self::Expired => 'gray',
            self::Cancelled => 'danger',
            self::CheckedIn => 'primary',
        };
    }
}
