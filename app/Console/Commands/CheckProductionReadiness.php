<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Daftar periksa keamanan & kesiapan sebelum go-live (dokumen teknis bab 7 & 10.2).
 * Keluar dengan kode gagal (1) bila ada butir GAGAL, supaya bisa dipakai di skrip deploy.
 */
#[Signature('resort:cek-produksi')]
#[Description('Periksa konfigurasi keamanan & kesiapan produksi sebelum go-live')]
class CheckProductionReadiness extends Command
{
    private array $results = [];

    public function handle(): int
    {
        $this->info('Pemeriksaan kesiapan produksi Lingkung Gunung');
        $this->newLine();

        $this->checkApp();
        $this->checkAdmin();
        $this->checkDatabase();
        $this->checkMidtrans();
        $this->checkMail();
        $this->checkBackup();
        $this->checkStorage();
        $this->checkContent();

        $this->table(['Status', 'Butir', 'Keterangan'], array_map(fn ($r) => [
            match ($r[0]) {
                'ok' => '<fg=green>OK</>', 'warn' => '<fg=yellow>PERINGATAN</>', default => '<fg=red>GAGAL</>'
            },
            $r[1],
            $r[2],
        ], $this->results));

        $fail = count(array_filter($this->results, fn ($r) => $r[0] === 'fail'));
        $warn = count(array_filter($this->results, fn ($r) => $r[0] === 'warn'));

        $this->newLine();
        if ($fail > 0) {
            $this->error("{$fail} butir GAGAL dan {$warn} peringatan. Perbaiki sebelum go-live.");

            return self::FAILURE;
        }

        $warn > 0
            ? $this->warn("Tidak ada yang gagal, tetapi ada {$warn} peringatan untuk ditinjau.")
            : $this->info('Semua pemeriksaan lolos.');

        return self::SUCCESS;
    }

    private function check(bool $ok, string $item, string $okText, string $badText, bool $critical = true): void
    {
        $this->results[] = [$ok ? 'ok' : ($critical ? 'fail' : 'warn'), $item, $ok ? $okText : $badText];
    }

    private function checkApp(): void
    {
        $this->check(app()->isProduction(), 'APP_ENV', 'production', 'Set APP_ENV=production');
        $this->check(! config('app.debug'), 'APP_DEBUG', 'false (pesan error umum)', 'Set APP_DEBUG=false agar detail error tidak terlihat publik');
        $this->check(filled(config('app.key')), 'APP_KEY', 'terisi', 'Jalankan php artisan key:generate');
        $this->check(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL', (string) config('app.url'), 'APP_URL harus diawali https://');
        $this->check(config('session.secure') === true, 'SESSION_SECURE_COOKIE', 'true', 'Set SESSION_SECURE_COOKIE=true (cookie hanya lewat HTTPS)');
        $level = (string) config('logging.channels.single.level', 'debug');
        $this->check($level !== 'debug', 'LOG_LEVEL', $level, 'Set LOG_LEVEL=error atau warning di produksi', critical: false);
        $this->check(config('queue.default') === 'database' && config('cache.default') === 'database', 'Queue & cache', 'driver database (sesuai anggaran nol)', 'QUEUE_CONNECTION & CACHE_STORE sebaiknya database', critical: false);
        $this->check(filled(config('resort.trusted_proxies')), 'TRUSTED_PROXIES', (string) config('resort.trusted_proxies'), 'Isi TRUSTED_PROXIES="*" bila server di belakang Cloudflare/proxy hosting', critical: false);
    }

    private function checkAdmin(): void
    {
        $this->check(config('resort.admin_path') !== 'admin', 'ADMIN_PATH', '/'.config('resort.admin_path'), 'Jangan pakai /admin');
        $this->check(config('resort.admin_requires_mfa') === true, '2FA admin', 'wajib', 'Set ADMIN_REQUIRE_MFA=true');
        $this->check(! filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOLEAN), 'expose_php', 'mati', 'Set expose_php=Off di php.ini server (sembunyikan versi PHP)', critical: false);
        $this->check(config('resort.customer_email_verification') === true, 'Verifikasi email pelanggan', 'wajib', 'Set CUSTOMER_EMAIL_VERIFICATION=true (pastikan MAIL_* sudah berfungsi)');
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->check(false, 'Koneksi database', '', 'Tidak bisa terhubung: '.$e->getMessage());

            return;
        }

        $user = (string) config('database.connections.'.config('database.default').'.username');
        $this->check($user !== 'root', 'User database', $user, 'Jangan memakai user root untuk aplikasi');

        try {
            $grants = collect(DB::select('SHOW GRANTS FOR CURRENT_USER()'))->map(fn ($row) => strtoupper((string) array_values((array) $row)[0]))->implode(' | ');
            $dangerous = str_contains($grants, 'ALL PRIVILEGES') || str_contains($grants, 'GRANT OPTION') || preg_match('/\bDROP\b/', $grants);
            $this->check(! $dangerous, 'Hak user database', 'tanpa DROP / GRANT', 'User aplikasi masih punya hak DROP/GRANT/ALL. Lihat docs/DEPLOY.md bagian user database');
        } catch (Throwable) {
            $this->check(false, 'Hak user database', '', 'Tidak bisa membaca hak akses (SHOW GRANTS)', critical: false);
        }
    }

    private function checkMidtrans(): void
    {
        $server = (string) config('services.midtrans.server_key');
        $client = (string) config('services.midtrans.client_key');
        $production = (bool) config('services.midtrans.is_production');

        $this->check($server !== '' && $client !== '', 'Kunci Midtrans', 'terisi', 'Isi MIDTRANS_SERVER_KEY & MIDTRANS_CLIENT_KEY');
        $this->check($production, 'Mode Midtrans', 'produksi', 'MIDTRANS_IS_PRODUCTION masih false (sandbox, uang tidak sungguhan)');
        // Kunci sandbox lama berawalan "SB-"; akun baru (sandbox & produksi) sama-sama "Mid-server-",
        // jadi hanya kombinasi "SB-" + mode produksi yang pasti salah.
        if ($server !== '' && $production) {
            $this->check(! str_starts_with($server, 'SB-'), 'Kecocokan kunci Midtrans', 'bukan kunci sandbox lama', 'Kunci sandbox (SB-...) dipakai di mode produksi');
        }
    }

    private function checkMail(): void
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        $this->check(! in_array($mailer, ['log', 'array'], true), 'Pengiriman email', $mailer, "MAIL_MAILER={$mailer}: email e-ticket tidak benar-benar terkirim");
        $this->check($from !== '' && ! str_contains($from, 'example') && ! str_ends_with($from, '.test'), 'Alamat pengirim email', $from, 'Set MAIL_FROM_ADDRESS ke alamat domain resort (SPF/DKIM)');
    }

    private function checkBackup(): void
    {
        $disks = (array) config('backup.backup.destination.disks');

        $this->check(filled(config('backup.backup.password')), 'Enkripsi backup', 'password terisi', 'Isi BACKUP_ARCHIVE_PASSWORD (simpan juga di tempat aman)');
        $this->check($disks !== ['backups'] && $disks !== ['local'], 'Lokasi backup', implode(', ', $disks), 'Backup masih di server yang sama. Tambahkan disk terpisah (mis. Cloudflare R2) lewat BACKUP_DISKS', critical: false);
        $notify = (string) config('backup.notifications.mail.to');
        $this->check($notify !== '' && $notify !== 'backup@example.com', 'Email notifikasi backup', $notify, 'Isi BACKUP_NOTIFICATION_EMAIL agar tahu bila backup gagal', critical: false);

        try {
            $latest = collect(Storage::disk($disks[0] ?? 'backups')->allFiles(config('backup.backup.name')))
                ->filter(fn ($f) => str_ends_with($f, '.zip'))
                ->map(fn ($f) => Storage::disk($disks[0])->lastModified($f))
                ->max();
            $this->check($latest && now()->timestamp - $latest < 2 * 86400, 'Backup terakhir', $latest ? date('d/m/Y H:i', $latest) : '-', 'Belum ada backup dalam 2 hari terakhir. Pastikan cron schedule:run berjalan', critical: false);
        } catch (Throwable) {
            $this->check(false, 'Backup terakhir', '', 'Disk backup tidak bisa dibaca', critical: false);
        }
    }

    private function checkStorage(): void
    {
        $link = public_path('storage');
        // readlink() juga mengenali junction Windows (is_link/is_dir bernilai false untuk junction)
        $this->check(is_link($link) || is_dir($link) || @readlink($link) !== false, 'Tautan storage', 'public/storage ada', 'Jalankan php artisan storage:link');
        $this->check(is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'Folder bisa ditulis', 'storage & bootstrap/cache', 'Atur izin tulis untuk storage/ dan bootstrap/cache/');
        $this->check(app()->configurationIsCached(), 'Cache konfigurasi', 'aktif', 'Jalankan php artisan optimize untuk performa', critical: false);
    }

    private function checkContent(): void
    {
        try {
            $this->check(filled(Setting::get('contact_email')), 'Email resmi resort', (string) Setting::get('contact_email'), 'Isi di admin › Pengaturan Umum', critical: false);
        } catch (Throwable) {
            // tabel settings belum ada
        }
    }
}
