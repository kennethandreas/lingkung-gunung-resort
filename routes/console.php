<?php

use Illuminate\Support\Facades\Schedule;

/*
| Jadwal otomatis. Di server produksi cukup satu cron tiap menit:
| * * * * * php /path/ke/proyek/artisan schedule:run
| Saat development: php artisan schedule:work
*/

// Lepas hold booking yang tidak dibayar dalam 60 menit
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();

// Cadangan webhook: cek ulang ke Midtrans transaksi online yang masih menunggu
Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping();

// Pengingat pelunasan H-7 dan H-4 (email berisi link pembayaran)
Schedule::command('bookings:send-reminders')->dailyAt('08:00');

// Siapkan stok harian setahun ke depan
Schedule::command('inventory:generate')->dailyAt('00:10');

// Backup harian terenkripsi (database + file unggahan), bersihkan backup lama, periksa kesehatannya
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('07:00');

// Hosting tanpa queue worker permanen (shared hosting): proses antrean email lewat cron tiap menit
if (config('resort.queue_via_scheduler')) {
    Schedule::command('queue:work', ['--stop-when-empty', '--max-time' => 50, '--tries' => 3])
        ->everyMinute()
        ->withoutOverlapping();
}

// Pembersihan data lama (data booking & pembayaran TIDAK dihapus)
Schedule::command('activitylog:clean')->weekly();                   // log aktivitas > 365 hari
Schedule::command('model:prune')->dailyAt('02:30');                 // log webhook Midtrans > 1 tahun
Schedule::command('queue:prune-failed', ['--hours' => 720])->weekly(); // job gagal > 30 hari
