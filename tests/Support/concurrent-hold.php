<?php

/**
 * Dipanggil oleh tests/Feature/ConcurrentBookingTest.php sebagai proses PHP terpisah.
 * Setiap proses menunggu sampai detik yang sama lalu mencoba memesan unit terakhir.
 *
 * Pemakaian: php concurrent-hold.php {unit_type_id} {check_in} {check_out} {start_at_microtime} {nama}
 */

use App\Enums\BookingChannel;
use App\Exceptions\BookingException;
use App\Services\BookingService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $unitTypeId, $checkIn, $checkOut, $startAt, $name] = $argv;

// Barrier: semua proses mulai bersamaan
while (microtime(true) < (float) $startAt) {
    usleep(500);
}

try {
    $booking = app(BookingService::class)->createHold([
        'customer' => ['name' => $name, 'phone' => '0812'.random_int(10000000, 99999999)],
        'lines' => [(int) $unitTypeId => 1],
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => 2,
        'payment_option' => 'full',
        'channel' => BookingChannel::Web,
    ]);

    echo "BERHASIL {$booking->code}";
} catch (BookingException $e) {
    echo 'DITOLAK '.$e->getMessage();
} catch (Throwable $e) {
    // Error teknis (mis. deadlock tak tertangani) = test gagal
    echo 'ERROR '.get_class($e).': '.$e->getMessage();
    exit(1);
}
