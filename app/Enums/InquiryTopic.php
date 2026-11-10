<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Topik pesan dari form kontak. */
enum InquiryTopic: string implements HasLabel
{
    case Menginap = 'menginap';
    case Rombongan = 'rombongan';
    case Meeting = 'meeting';
    case Aktivitas = 'aktivitas';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menginap => 'Menginap & ketersediaan',
            self::Rombongan => 'Paket rombongan / LDKS / outbound',
            self::Meeting => 'Meeting & acara',
            self::Aktivitas => 'Aktivitas & Sanggar Alam',
            self::Lainnya => 'Lainnya',
        };
    }

    /** Topik yang perlu tanggal & jumlah peserta. */
    public function isGroup(): bool
    {
        return in_array($this, [self::Rombongan, self::Meeting, self::Aktivitas], true);
    }
}
