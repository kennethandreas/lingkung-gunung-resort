<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DailyInventory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreatesResortData;
use Tests\TestCase;

/**
 * Test wajib: beberapa pemesan BERSAMAAN memperebutkan unit terakhir,
 * hanya satu yang boleh berhasil.
 *
 * Memakai proses PHP sungguhan yang berjalan paralel ke MySQL, sehingga
 * lockForUpdate() benar-benar diuji (bukan simulasi). Karena proses lain harus
 * bisa melihat data, test ini memakai DatabaseTruncation (data di-commit),
 * bukan transaksi RefreshDatabase.
 */
class ConcurrentBookingTest extends TestCase
{
    use CreatesResortData, DatabaseTruncation;

    private const RACERS = 5;

    /**
     * DatabaseTruncation membersihkan tabel di AWAL test. Data yang di-commit test ini
     * juga dibersihkan di AKHIR, supaya tidak mengganggu test lain yang berjalan sesudahnya.
     */
    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    public function test_hanya_satu_dari_banyak_pemesan_bersamaan_yang_mendapat_unit_terakhir(): void
    {
        $type = $this->makeUnitType(attributes: ['total_units' => 1]);
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(22)->toDateString();
        $startAt = microtime(true) + 4; // beri waktu semua proses selesai booting

        $php = (new PhpExecutableFinder)->find(false);
        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => config('database.default'),
            'DB_DATABASE' => config('database.connections.mysql.database'),
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
        ];

        $processes = collect(range(1, self::RACERS))->map(function (int $i) use ($php, $env, $type, $checkIn, $checkOut, $startAt) {
            $process = new Process(
                [$php, base_path('tests/Support/concurrent-hold.php'), (string) $type->id, $checkIn, $checkOut, (string) $startAt, "Pemesan {$i}"],
                base_path(),
                $env,
                timeout: 60,
            );
            $process->start();

            return $process;
        });

        $outputs = $processes->map(function (Process $process) {
            $process->wait();
            $this->assertSame(0, $process->getExitCode(), 'Proses gagal: '.$process->getErrorOutput().$process->getOutput());

            return trim($process->getOutput());
        });

        $winners = $outputs->filter(fn ($out) => str_starts_with($out, 'BERHASIL'));
        $losers = $outputs->filter(fn ($out) => str_starts_with($out, 'DITOLAK'));

        $this->assertCount(1, $winners, "Hanya satu yang boleh berhasil. Hasil:\n".$outputs->implode("\n"));
        $this->assertCount(self::RACERS - 1, $losers, "Hasil:\n".$outputs->implode("\n---\n"));
        $losers->each(fn ($out) => $this->assertStringContainsString('tidak tersedia', $out));

        // Stok di database konsisten: tertahan tepat 1 per malam, tidak pernah melebihi kapasitas
        $rows = DailyInventory::query()->where('unit_type_id', $type->id)->whereBetween('date', [$checkIn, now()->addDays(21)->toDateString()])->get();
        $this->assertCount(2, $rows);
        $rows->each(function (DailyInventory $row) {
            $this->assertSame(1, $row->held);
            $this->assertSame(0, $row->booked);
            $this->assertLessThanOrEqual($row->capacity, $row->held + $row->booked);
        });
        $this->assertSame(1, Booking::query()->count());
    }
}
