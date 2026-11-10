<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\UnitType;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inventory:generate {--days= : Jumlah hari ke depan (default: booking_window_days)}')]
#[Description('Siapkan baris stok harian (daily_inventory) untuk semua tipe unit')]
class GenerateInventory extends Command
{
    public function handle(BookingService $bookings): int
    {
        $days = (int) ($this->option('days') ?: Setting::get('booking_window_days', 365));
        $from = CarbonImmutable::today();

        $bookings->ensureInventory(UnitType::query()->pluck('id')->all(), $from, $from->addDays($days));
        $this->info("Stok harian siap sampai {$from->addDays($days)->toDateString()}.");

        return self::SUCCESS;
    }
}
