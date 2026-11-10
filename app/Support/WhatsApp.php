<?php

namespace App\Support;

use App\Models\Setting;

/** Tautan wa.me dengan pesan terisi otomatis (tanpa API berbayar). */
class WhatsApp
{
    public static function link(?string $message = null, ?string $number = null): string
    {
        $number = preg_replace('/\D/', '', $number ?? (string) Setting::get('contact_whatsapp'));

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    /** Format tampilan: 628111516727 -> 0811-1516-727 */
    public static function display(?string $number = null): string
    {
        $digits = preg_replace('/^62/', '0', preg_replace('/\D/', '', $number ?? (string) Setting::get('contact_whatsapp')));

        return preg_replace('/^(\d{4})(\d{4})(\d+)$/', '$1-$2-$3', $digits);
    }
}
