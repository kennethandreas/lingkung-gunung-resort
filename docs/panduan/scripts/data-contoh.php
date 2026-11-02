<?php

/*
 * Data CONTOH (nama fiktif) untuk tangkapan layar panduan. Dijalankan lewat tinker:
 *   PANDUAN_MODE=buat  php artisan tinker docs/panduan/scripts/data-contoh.php
 *   PANDUAN_MODE=hapus php artisan tinker docs/panduan/scripts/data-contoh.php
 * Booking akan datang dibuat lewat BookingService (stok ditahan/terpakai sungguhan) dan dilepas lagi saat dihapus.
 * Hanya untuk laptop development. Jangan dijalankan di server produksi.
 */

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryTopic;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Inquiry;
use App\Models\Review;
use App\Models\UnitType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

$mode = $_SERVER['PANDUAN_MODE'] ?? 'buat';
$accountEmail = 'contoh.budi@example.com';
$staffEmail = 'panduan.owner@example.com';
$secretFile = sys_get_temp_dir().'/panduan-2fa.txt';
$phones = ['089900000101', '089900000102', '089900000103', '089900000104'];

if ($mode === 'kode') {
    echo (new Google2FA)->getCurrentOtp(trim(file_get_contents($secretFile))), "\n";

    return;
}

if ($mode === 'buat') {
    $bookings = app(BookingService::class);
    $payments = app(PaymentService::class);

    // Akun staf sementara (Owner) untuk memotret admin panel
    $secret = (new Google2FA)->generateSecretKey();
    $staff = User::query()->updateOrCreate(['email' => $staffEmail], ['name' => 'Staf Panduan', 'password' => 'PanduanContoh123', 'is_active' => true]);
    $staff->forceFill(['app_authentication_secret' => $secret])->save();
    $staff->syncRoles(['owner']);
    file_put_contents($secretFile, $secret);

    $account = CustomerAccount::query()->updateOrCreate(['email' => $accountEmail], ['name' => 'Budi Santoso', 'password' => 'ContohTamu12345', 'phone' => $phones[0]]);
    $account->forceFill(['email_verified_at' => now()])->save();

    $customer = fn (int $i, string $name) => ['name' => $name, 'phone' => $phones[$i], 'email' => "contoh{$i}@example.com"];
    $deluxe = UnitType::query()->where('slug', 'pangrango-deluxe')->firstOrFail();
    $glamping = UnitType::query()->where('slug', 'glamourcamp')->firstOrFail();

    // 1) Sudah menginap (check-in tercatat, check-out lewat): untuk contoh ulasan. Tanggal lampau, tanpa stok.
    $past = Booking::query()->create([
        'code' => 'LGCONTOH01', 'customer_id' => Customer::query()->updateOrCreate(['phone' => $phones[0]], ['name' => 'Budi Santoso', 'email' => $accountEmail])->id,
        'customer_account_id' => $account->id, 'channel' => BookingChannel::Web, 'status' => BookingStatus::CheckedIn, 'payment_option' => 'full',
        'check_in' => '2026-10-20', 'check_out' => '2026-10-22', 'nights' => 2, 'guests' => 2, 'subtotal' => 1500000, 'discount' => 0,
        'total' => 1500000, 'dp_amount' => 1500000, 'paid_amount' => 1500000, 'confirmed_at' => '2026-10-10 09:00', 'fully_paid_at' => '2026-10-10 09:00',
        'checked_in_at' => '2026-10-20 14:10', 'internal_notes' => 'DATA CONTOH PANDUAN',
    ]);
    $past->items()->create(['item_type' => 'unit_type', 'item_id' => $deluxe->id, 'description' => $deluxe->marketing_name, 'qty' => 1, 'pax' => 2, 'unit_price' => 750000, 'subtotal' => 1500000]);

    // 2) DP sudah dibayar, menunggu pelunasan
    $dp = $bookings->createHold(['customer' => $customer(0, 'Budi Santoso'), 'customer_account_id' => $account->id, 'lines' => [$deluxe->id => 1],
        'check_in' => '2026-11-12', 'check_out' => '2026-11-14', 'guests' => 2, 'payment_option' => 'dp', 'channel' => BookingChannel::Web]);
    $payments->recordManualPayment($dp, $dp->dp_amount, $staff, note: 'Contoh: transfer DP');

    // 3) Menunggu pembayaran (unit sedang ditahan)
    $bookings->createHold(['customer' => $customer(0, 'Budi Santoso'), 'customer_account_id' => $account->id, 'lines' => [$glamping->id => 1],
        'check_in' => '2026-11-20', 'check_out' => '2026-11-21', 'guests' => 2, 'payment_option' => 'dp', 'channel' => BookingChannel::Web]);

    // 4) Lunas, tiba besok (tampil di "Kedatangan 7 hari ke depan")
    $full = $bookings->createHold(['customer' => $customer(1, 'Sari Wulandari'), 'lines' => [$glamping->id => 1],
        'check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 2, 'payment_option' => 'full', 'channel' => BookingChannel::Manual]);
    $payments->recordManualPayment($full, $full->total, $staff, note: 'Contoh: transfer lunas');

    // Ulasan: satu tayang (resort), satu menunggu (akomodasi)
    $reviews = app(ReviewService::class);
    $reviews->approve($reviews->create($account, $past, 'resort', ['rating' => 5, 'comment' => 'Contoh ulasan: udara sejuk, staf ramah, sarapan menghadap lembah. Anak-anak senang bermain di area camping.']), $staff);
    $reviews->create($account, $past, 'akomodasi-'.$deluxe->id, ['rating' => 4, 'comment' => 'Contoh ulasan: kamar bersih dan balkon luas. Air panas sempat lambat di pagi hari.']);

    Inquiry::query()->create(['name' => 'Rina Kartika (contoh)', 'phone' => $phones[2], 'email' => 'contoh2@example.com', 'topic' => InquiryTopic::cases()[0],
        'message' => 'Contoh pesan: apakah tersedia paket outbound untuk 40 karyawan pada bulan Desember?', 'status' => InquiryStatus::Baru]);

    echo "dibuat\n";

    return;
}

// ---------- hapus: lepas stok lalu hapus semua data contoh
$account = CustomerAccount::query()->where('email', $accountEmail)->first();
$customerIds = Customer::query()->whereIn('phone', $phones)->pluck('id');
$bookingList = Booking::query()->whereIn('customer_id', $customerIds)->orWhere('code', 'LGCONTOH01')->get();

Review::query()->whereIn('booking_id', $bookingList->pluck('id'))->get()->each->delete();
// Lepas stok hanya untuk booking contoh (bukan expireOverdueHolds, yang ikut menyentuh booking lain)
foreach ($bookingList as $booking) {
    app(BookingService::class)->expire($booking);
}
foreach ($bookingList->fresh() as $booking) {
    if (in_array($booking->status, [BookingStatus::DpPaid, BookingStatus::FullyPaid], true)) {
        app(BookingService::class)->cancel($booking, 'Data contoh panduan dihapus');
    }
    $booking->payments()->delete();
    $booking->items()->delete();
    $booking->delete();
    DB::table('activity_log')->where('subject_type', $booking->getMorphClass())->where('subject_id', $booking->id)->delete();
}
Inquiry::query()->whereIn('phone', $phones)->delete();
Customer::query()->whereIn('id', $customerIds)->delete();
$account?->delete();
User::query()->where('email', $staffEmail)->first()?->delete();
@unlink($secretFile);
echo "dihapus ({$bookingList->count()} booking)\n";
