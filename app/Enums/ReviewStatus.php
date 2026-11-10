<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status moderasi ulasan. Hanya Published yang tampil di website dan dihitung di rata-rata. */
enum ReviewStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Hidden = 'hidden';

    /** Perpindahan status yang boleh dilakukan staf moderasi. */
    public function canMoveTo(self $to): bool
    {
        return match ($to) {
            self::Published => in_array($this, [self::Pending, self::Rejected, self::Hidden], true),
            self::Rejected => $this === self::Pending,
            self::Hidden => $this === self::Published,
            self::Pending => false,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu persetujuan',
            self::Published => 'Tayang',
            self::Rejected => 'Ditolak',
            self::Hidden => 'Disembunyikan',
        };
    }

    /** Label untuk pelanggan di halaman akun. */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu persetujuan',
            self::Published => 'Sudah tayang',
            self::Rejected => 'Tidak ditayangkan',
            self::Hidden => 'Tidak ditayangkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
            self::Hidden => 'gray',
        };
    }
}
