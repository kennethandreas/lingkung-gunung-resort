<?php

namespace Tests\Concerns;

use App\Enums\BookingChannel;
use App\Models\Booking;
use App\Models\UnitType;
use App\Services\BookingService;
use Illuminate\Support\Str;

trait CreatesResortData
{
    protected function makeUnitType(int $weekday = 500000, int $weekend = 600000, array $attributes = []): UnitType
    {
        $type = UnitType::query()->create($attributes + [
            'slug' => 'tipe-'.Str::lower(Str::random(6)),
            'name' => 'Tipe Uji',
            'marketing_name' => 'Glamping Uji',
            'capacity_pax' => 4,
            'breakfast_pax' => 2,
            'total_units' => 2,
            'pricing_unit' => 'room',
        ]);

        $type->rates()->create(['day_type' => 'weekday', 'price' => $weekday, 'valid_from' => '2026-01-01']);
        $type->rates()->create(['day_type' => 'weekend', 'price' => $weekend, 'valid_from' => '2026-01-01']);

        return $type;
    }

    protected function holdBooking(UnitType $type, string $checkIn, string $checkOut, int $qty = 1, array $overrides = []): Booking
    {
        return app(BookingService::class)->createHold($overrides + [
            'customer' => ['name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@example.com'],
            'lines' => [$type->id => $qty],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
            'payment_option' => 'dp',
            'channel' => BookingChannel::Web,
        ]);
    }
}
