<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:expire-holds')]
#[Description('Lepas stok booking Pending yang melewati batas waktu hold')]
class ExpireHolds extends Command
{
    public function handle(BookingService $bookings): int
    {
        $count = $bookings->expireOverdueHolds();
        $this->info("{$count} booking ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
