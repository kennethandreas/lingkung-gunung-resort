<?php

namespace App\Enums;

use App\Models\Activity;
use App\Models\GroupPackage;
use App\Models\UnitType;
use Filament\Support\Contracts\HasLabel;

/** Yang diulas: resort secara umum, atau satu akomodasi / aktivitas / paket rombongan. */
enum ReviewTargetType: string implements HasLabel
{
    case Resort = 'resort';
    case UnitType = 'unit_type';
    case Activity = 'activity';
    case GroupPackage = 'group_package';

    public function getLabel(): string
    {
        return match ($this) {
            self::Resort => 'Resort',
            self::UnitType => 'Akomodasi',
            self::Activity => 'Aktivitas',
            self::GroupPackage => 'Paket rombongan',
        };
    }

    /** Kolom foreign key di tabel reviews (null untuk resort). */
    public function column(): ?string
    {
        return match ($this) {
            self::Resort => null,
            self::UnitType => 'unit_type_id',
            self::Activity => 'activity_id',
            self::GroupPackage => 'group_package_id',
        };
    }

    /** @return class-string|null */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::Resort => null,
            self::UnitType => UnitType::class,
            self::Activity => Activity::class,
            self::GroupPackage => GroupPackage::class,
        };
    }

    /** Bagian URL (/ulasan/{slug}/... dan kunci target di halaman akun, mis. "akomodasi-5"). */
    public function slug(): string
    {
        return match ($this) {
            self::Resort => 'resort',
            self::UnitType => 'akomodasi',
            self::Activity => 'aktivitas',
            self::GroupPackage => 'paket',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }
}
