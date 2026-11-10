<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Jejak perubahan admin (tabel activity_log).
 * Dipisah dari App\Models\Activity (aktivitas resort) agar nama izin Shield tidak bentrok.
 */
class ActivityLog extends SpatieActivity
{
    public const SUBJECT_LABELS = [
        Booking::class => 'Booking',
        Payment::class => 'Pembayaran',
        Rate::class => 'Harga',
        UnitType::class => 'Tipe unit',
        Unit::class => 'Unit',
        CalendarDay::class => 'Hari khusus',
        Promo::class => 'Promo',
        Customer::class => 'Pelanggan',
        Setting::class => 'Pengaturan',
        GroupPackage::class => 'Paket group',
        GroupPackageVariant::class => 'Varian paket',
        MenuSet::class => 'Set menu',
        MenuOption::class => 'Opsi menu',
        Activity::class => 'Aktivitas',
        AdditionalItem::class => 'Item tambahan',
        Quotation::class => 'Penawaran',
        User::class => 'Pengguna',
    ];
}
