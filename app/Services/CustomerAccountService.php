<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CustomerAccount;
use App\Models\UnitType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logika akun pelanggan website: pendaftaran, profil, riwayat booking,
 * penautan booking lama, dan akomodasi tersimpan.
 * Kepemilikan selalu ditentukan dari akun yang login, tidak pernah dari input tamu.
 */
class CustomerAccountService
{
    /** @param  array{name: string, email: string, password: string}  $data */
    public function register(array $data): CustomerAccount
    {
        $account = CustomerAccount::create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
        ]);

        $account->sendEmailVerificationNotification();

        return $account;
    }

    /**
     * Perbarui nama, nomor WhatsApp, dan email. Email baru wajib diverifikasi ulang.
     *
     * @param  array{name: string, phone?: ?string, email: string}  $data
     * @return bool true bila email berubah (verifikasi ulang dikirim)
     */
    public function updateProfile(CustomerAccount $account, array $data): bool
    {
        $email = mb_strtolower(trim($data['email']));
        // Verifikasi ulang hanya bila fitur verifikasi email sedang aktif
        $emailChanged = $email !== $account->email && CustomerAccount::verificationRequired();

        $account->fill([
            'name' => trim($data['name']),
            'phone' => filled($data['phone'] ?? null) ? self::normalizePhone($data['phone']) : null,
            'email' => $email,
        ]);

        if ($emailChanged) {
            $account->email_verified_at = null;
        }

        $account->save();

        if ($emailChanged) {
            $account->sendEmailVerificationNotification();
        }

        return $emailChanged;
    }

    public function changePassword(CustomerAccount $account, string $password): void
    {
        $account->forceFill(['password' => $password])->setRememberToken(null);
        $account->save();
    }

    /**
     * Booking milik akun, dikelompokkan: akan datang, selesai, dibatalkan.
     *
     * @return array{upcoming: Collection<int, Booking>, completed: Collection<int, Booking>, cancelled: Collection<int, Booking>}
     */
    public function groupedBookings(CustomerAccount $account): array
    {
        $bookings = $account->bookings()->with('items')->latest('check_in')->get();
        $today = today();

        $cancelled = $bookings->filter(fn (Booking $b) => in_array($b->status, [BookingStatus::Cancelled, BookingStatus::Expired], true));
        $active = $bookings->diff($cancelled);
        $completed = $active->filter(fn (Booking $b) => $b->check_out->lt($today));
        $upcoming = $active->diff($completed)->sortBy('check_in');

        return [
            'upcoming' => $upcoming->values(),
            'completed' => $completed->values(),
            'cancelled' => $cancelled->values(),
        ];
    }

    /**
     * Cegah booking ganda (klik dua kali / dua tab) dan penimbunan stok oleh satu akun.
     * Mengembalikan booking identik yang baru saja dibuat (≤ 2 menit) agar tamu diarahkan ke sana,
     * atau null bila boleh membuat booking baru. Melempar BookingException bila hold aktif sudah terlalu banyak.
     *
     * @param  array<int, int>  $lines  [unit_type_id => jumlah]
     */
    public function guardNewBooking(CustomerAccount $account, string $checkIn, string $checkOut, array $lines): ?Booking
    {
        $active = $account->bookings()
            ->where('status', BookingStatus::Pending)
            ->where('hold_expires_at', '>', now())
            ->with('items')
            ->latest('id')
            ->get();

        $wanted = collect($lines)->map(fn ($qty) => (int) $qty)->filter(fn ($qty) => $qty > 0)->sortKeys()->all();
        $duplicate = $active->first(fn (Booking $booking) => $booking->created_at->gte(now()->subMinutes(2))
            && $booking->check_in->toDateString() === CarbonImmutable::parse($checkIn)->toDateString()
            && $booking->check_out->toDateString() === CarbonImmutable::parse($checkOut)->toDateString()
            && $booking->items->where('item_type', BookingItem::TYPE_UNIT_TYPE)->mapWithKeys(fn (BookingItem $item) => [$item->item_id => (int) $item->qty])->sortKeys()->all() == $wanted);

        if ($duplicate) {
            return $duplicate;
        }

        $max = (int) config('resort.max_pending_holds_per_account', 3);
        if ($max > 0 && $active->count() >= $max) {
            throw new BookingException("Anda masih punya {$active->count()} booking yang menunggu pembayaran. Selesaikan atau tunggu hingga batas waktunya habis sebelum membuat booking baru.");
        }

        return null;
    }

    /**
     * Siapa yang boleh membuka halaman status/pembayaran/e-ticket /booking/{kode}:
     * - booking milik akun: hanya akun pemiliknya (yang sudah login);
     * - booking tanpa akun (dibuat staf: walk-in, telepon, B2B, atau booking lama): lewat link kode acak.
     */
    public function canAccessBookingPage(?CustomerAccount $account, Booking $booking): bool
    {
        return $booking->customer_account_id === null || $booking->customer_account_id === $account?->id;
    }

    /** Ambil booking milik akun berdasarkan kode. Booking orang lain = tidak ditemukan (bukan "dilarang"). */
    public function findOwnedBooking(CustomerAccount $account, string $code): ?Booking
    {
        return $account->bookings()->where('code', $code)->with(['items', 'payments' => fn ($q) => $q->latest('id')])->first();
    }

    /**
     * Tautkan booking lama (dibuat tanpa login) ke akun.
     * Bukti kepemilikan: kode booking + nomor WhatsApp yang dipakai saat memesan,
     * dan booking belum terhubung ke akun mana pun. Email saja tidak cukup.
     */
    public function claimBooking(CustomerAccount $account, string $code, string $phone): Booking
    {
        $code = strtoupper(trim($code));
        $phone = self::normalizePhone($phone);

        return DB::transaction(function () use ($account, $code, $phone) {
            $booking = Booking::query()->with('customer')->where('code', $code)->lockForUpdate()->first();

            // Pesan yang sama untuk semua kegagalan agar tidak membocorkan kode mana yang ada
            if (! $booking || $booking->customer_account_id !== null && $booking->customer_account_id !== $account->id
                || self::normalizePhone((string) $booking->customer?->phone) !== $phone) {
                throw new BookingException('Booking tidak ditemukan atau nomor WhatsApp tidak cocok. Periksa kembali kode booking dan nomor yang Anda pakai saat memesan.');
            }

            if ($booking->customer_account_id === $account->id) {
                throw new BookingException("Booking {$booking->code} sudah ada di akun Anda.");
            }

            $booking->forceFill(['customer_account_id' => $account->id])->save();

            return $booking;
        });
    }

    /** Simpan / batal simpan akomodasi. Mengembalikan status akhir (true = tersimpan). Tidak menyentuh stok. */
    public function toggleSaved(CustomerAccount $account, UnitType $unitType): bool
    {
        $exists = $account->savedUnitTypes()->whereKey($unitType->id)->exists();

        if ($exists) {
            $account->savedUnitTypes()->detach($unitType->id);

            return false;
        }

        // insertOrIgnore + unique index: klik ganda tidak membuat baris dobel
        DB::table('saved_unit_types')->insertOrIgnore([
            'customer_account_id' => $account->id,
            'unit_type_id' => $unitType->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /** @return array<int, int> id tipe unit yang disimpan akun */
    public function savedIds(?CustomerAccount $account): array
    {
        if (! $account?->hasVerifiedEmail()) {
            return [];
        }

        return $account->savedUnitTypes()->pluck('unit_types.id')->all();
    }

    /** Samakan format nomor: 0812..., 62812..., +62 812-... menjadi 0812... */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
