<?php

namespace App\Support;

use App\Models\UnitType;
use Carbon\CarbonImmutable;

/**
 * Pesanan yang sedang menunggu tamu masuk/daftar: dibaca dari URL lanjutan wizard yang disimpan
 * di sesi (`url.intended`, diisi BookingWizard::redirectedToLogin). Hanya untuk tampilan;
 * tidak ada stok yang ditahan sebelum tamu menekan tombol bayar.
 */
class PendingBooking
{
    /** @return array{check_in: CarbonImmutable, check_out: CarbonImmutable, nights: int, guests: int, units: list<string>, url: string}|null */
    public static function fromSession(): ?array
    {
        $url = (string) session('url.intended', '');
        if ($url === '' || parse_url($url, PHP_URL_PATH) !== parse_url(route('pesan'), PHP_URL_PATH)) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        try {
            $in = CarbonImmutable::parse((string) ($query['check_in'] ?? ''))->startOfDay();
            $out = CarbonImmutable::parse((string) ($query['check_out'] ?? ''))->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($out->lte($in) || $in->lt(CarbonImmutable::today())) {
            return null;
        }

        $selected = collect((array) ($query['pilih'] ?? []))
            ->filter(fn ($qty, $id) => ctype_digit((string) $id) && (int) $qty > 0)
            ->map(fn ($qty) => min(50, (int) $qty));

        $names = UnitType::query()->whereIn('id', $selected->keys()->all())->pluck('marketing_name', 'id');

        return [
            'check_in' => $in,
            'check_out' => $out,
            'nights' => (int) $in->diffInDays($out),
            'guests' => max(1, min(200, (int) ($query['guests'] ?? 1))),
            'units' => $selected->map(fn ($qty, $id) => isset($names[$id]) ? $names[$id].($qty > 1 ? ' × '.$qty : '') : null)->filter()->values()->all(),
            'url' => $url,
        ];
    }
}
