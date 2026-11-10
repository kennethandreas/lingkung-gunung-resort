<?php

namespace App\Support;

use App\Services\CustomerAccountService;
use Illuminate\Support\Facades\Auth;

/**
 * Status "tersimpan" akomodasi untuk tampilan (tombol hati di kartu & detail).
 * Daftar id diambil sekali per request dari akun pelanggan yang login.
 */
class SavedRooms
{
    private const KEY = 'saved_unit_type_ids';

    public static function has(int $unitTypeId): bool
    {
        $request = request();

        if (! $request->attributes->has(self::KEY)) {
            $request->attributes->set(self::KEY, app(CustomerAccountService::class)->savedIds(Auth::guard('customer')->user()));
        }

        return in_array($unitTypeId, $request->attributes->get(self::KEY), true);
    }
}
