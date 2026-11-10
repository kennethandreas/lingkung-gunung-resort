<?php

namespace App\Services;

use App\Enums\DayType;
use App\Exceptions\BookingException;
use App\Models\CalendarDay;
use App\Models\Promo;
use App\Models\Rate;
use App\Models\Setting;
use App\Models\UnitType;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Semua perhitungan harga. Angka dari browser tidak pernah dipakai:
 * harga selalu dihitung ulang di sini dari tabel rates, calendar_days, promos.
 */
class PricingService
{
    /** Cache per-request supaya satu perhitungan tidak query berulang. */
    private array $calendarCache = [];

    /**
     * Kategori hari untuk satu MALAM menginap (tanggal check-in malam itu).
     * Urutan: penanda di calendar_days (libur/musim ramai) > aturan malam weekend.
     */
    public function dayTypeFor(CarbonImmutable $night): DayType
    {
        $key = $night->toDateString();

        if (! array_key_exists($key, $this->calendarCache)) {
            $this->calendarCache[$key] = CalendarDay::query()->whereDate('date', $key)->value('day_type');
        }

        if ($override = $this->calendarCache[$key]) {
            return $override instanceof DayType ? $override : DayType::from($override);
        }

        $weekendNights = array_map('intval', (array) Setting::get('weekend_nights', [5, 6]));

        return in_array($night->dayOfWeek, $weekendNights, true) ? DayType::Weekend : DayType::Weekday;
    }

    /** Label malam weekend dari Pengaturan Umum, mis. "malam Jumat & Sabtu". */
    public function weekendLabel(): string
    {
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $nights = collect((array) Setting::get('weekend_nights', [5, 6]))->map(fn ($d) => (int) $d)->filter(fn ($d) => isset($dayNames[$d]))->sort();

        return $nights->isEmpty() ? 'tidak ada malam weekend' : 'malam '.$nights->map(fn ($d) => $dayNames[$d])->implode(' & ');
    }

    /**
     * Harga satu unit (atau satu orang, untuk tipe per orang) pada satu malam.
     * Musim ramai tanpa tarif khusus memakai tarif weekend.
     */
    public function nightlyRate(UnitType $unitType, CarbonImmutable $night): array
    {
        $dayType = $this->dayTypeFor($night);

        $candidates = $dayType === DayType::HighSeason
            ? [DayType::HighSeason, DayType::Weekend]
            : [$dayType];

        foreach ($candidates as $candidate) {
            $price = Rate::query()
                ->where('unit_type_id', $unitType->id)
                ->where('day_type', $candidate)
                ->whereDate('valid_from', '<=', $night)
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $night))
                ->orderByDesc('valid_from')
                ->value('price');

            if ($price !== null) {
                return ['day_type' => $dayType, 'price' => (int) $price];
            }
        }

        throw new BookingException(
            "Harga {$unitType->marketing_name} untuk tanggal {$night->translatedFormat('d F Y')} belum diatur. Silakan hubungi kami."
        );
    }

    /**
     * Rincian harga menginap untuk satu tipe unit.
     *
     * @return array{nights: array<int, array{date: string, day_type: string, price: int}>, per_unit: int, qty: int, subtotal: int, average: int}
     */
    public function quoteStay(UnitType $unitType, CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $qty): array
    {
        $nights = [];

        foreach ($this->nights($checkIn, $checkOut) as $night) {
            $rate = $this->nightlyRate($unitType, $night);
            $nights[] = [
                'date' => $night->toDateString(),
                'day_type' => $rate['day_type']->value,
                'price' => $rate['price'],
            ];
        }

        $perUnit = array_sum(array_column($nights, 'price'));

        return [
            'nights' => $nights,
            'per_unit' => $perUnit,
            'qty' => $qty,
            'subtotal' => $perUnit * $qty,
            'average' => count($nights) ? intdiv($perUnit, count($nights)) : 0,
        ];
    }

    /**
     * Total satu pemesanan (beberapa tipe unit) + promo + DP.
     *
     * @param  array<int, int>  $lines  [unit_type_id => qty]
     */
    public function quoteBooking(array $lines, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?string $promoCode = null): array
    {
        $unitTypes = UnitType::query()->whereIn('id', array_keys($lines))->get()->keyBy('id');
        $items = [];

        foreach ($lines as $unitTypeId => $qty) {
            $unitType = $unitTypes->get($unitTypeId) ?? throw new BookingException('Tipe akomodasi tidak ditemukan.');
            $items[] = ['unit_type' => $unitType] + $this->quoteStay($unitType, $checkIn, $checkOut, (int) $qty);
        }

        $subtotal = array_sum(array_column($items, 'subtotal'));
        $promo = $promoCode ? $this->findPromo($promoCode, $subtotal, $checkIn) : null;
        $discount = $promo ? $this->discountFor($promo, $subtotal) : 0;
        $total = $subtotal - $discount;

        return [
            'items' => $items,
            'nights' => $this->nights($checkIn, $checkOut)->count(),
            'subtotal' => $subtotal,
            'promo' => $promo,
            'discount' => $discount,
            'total' => $total,
            'dp_amount' => $this->dpAmount($total),
        ];
    }

    public function dpAmount(int $total): int
    {
        $percent = (int) Setting::get('dp_percent', 25);

        return (int) ceil($total * $percent / 100);
    }

    /** Validasi kode promo. Melempar BookingException dengan pesan ramah bila tidak berlaku. */
    public function findPromo(string $code, int $subtotal, CarbonImmutable $stayDate): Promo
    {
        $promo = Promo::query()->where('code', strtoupper(trim($code)))->first();

        if (! $promo || ! $promo->is_active) {
            throw new BookingException('Kode promo tidak ditemukan atau sudah tidak aktif.');
        }

        $today = CarbonImmutable::today();
        if (($promo->valid_from && $today->lt($promo->valid_from)) || ($promo->valid_to && $today->gt($promo->valid_to))) {
            throw new BookingException('Kode promo sedang tidak berlaku.');
        }

        if ($promo->quota !== null && $promo->used >= $promo->quota) {
            throw new BookingException('Kuota kode promo sudah habis.');
        }

        if ($subtotal < $promo->min_total) {
            throw new BookingException('Kode promo berlaku untuk minimal transaksi Rp'.number_format($promo->min_total, 0, ',', '.').'.');
        }

        return $promo;
    }

    public function discountFor(Promo $promo, int $subtotal): int
    {
        $discount = $promo->type === 'percent'
            ? (int) floor($subtotal * min(100, $promo->value) / 100)
            : (int) $promo->value;

        if ($promo->max_discount) {
            $discount = min($discount, (int) $promo->max_discount);
        }

        return min($discount, $subtotal);
    }

    /**
     * Harga per malam yang berlaku hari ini per kategori hari, untuk tampilan "mulai dari".
     *
     * @return array{weekday: ?int, weekend: ?int, from: ?int}
     */
    public function displayPrices(UnitType $unitType): array
    {
        $today = CarbonImmutable::today();
        $rates = $unitType->relationLoaded('rates') ? $unitType->rates : $unitType->rates()->get();

        $current = fn (DayType $type) => $rates
            ->filter(fn (Rate $rate) => $rate->day_type === $type && $rate->valid_from->lte($today) && (! $rate->valid_to || $rate->valid_to->gte($today)))
            ->sortByDesc('valid_from')
            ->first()?->price;

        $weekday = $current(DayType::Weekday);
        $weekend = $current(DayType::Weekend);

        return ['weekday' => $weekday, 'weekend' => $weekend, 'from' => collect([$weekday, $weekend])->filter()->min()];
    }

    /** Daftar malam menginap: check-in s.d. sehari sebelum check-out. */
    public function nights(CarbonImmutable $checkIn, CarbonImmutable $checkOut): Collection
    {
        return collect(CarbonPeriod::create($checkIn, $checkOut->subDay()))
            ->map(fn ($date) => CarbonImmutable::parse($date)->startOfDay());
    }
}
