<?php

namespace App\Console\Commands;

use App\Mail\SettlementReminderMail;
use App\Services\BookingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('bookings:send-reminders')]
#[Description('Kirim email pengingat pelunasan untuk booking DP (H-7 dan H-4)')]
class SendSettlementReminders extends Command
{
    public function handle(BookingService $bookings): int
    {
        $count = 0;

        foreach ($bookings->bookingsDueForReminder() as $booking) {
            if (filled($booking->customer->email)) {
                Mail::to($booking->customer->email)->queue(new SettlementReminderMail($booking));
                $count++;
            }
        }

        $this->info("{$count} pengingat pelunasan dikirim.");

        return self::SUCCESS;
    }
}
