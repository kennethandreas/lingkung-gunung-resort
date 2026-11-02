<?php

namespace App\Services;

use App\Enums\BookingChannel;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\DailyInventory;
use App\Models\Promo;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\UnitType;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ketersediaan, hold, konfirmasi, expire, batal, check-in, dan blokir tanggal.
 * Dipakai oleh website, input manual admin, dan konversi B2B (aturan sama di semua kanal).
 *
 * Aturan anti double-booking (dokumen teknis 5.2):
 * baris daily_inventory untuk semua tanggal menginap dikunci dengan lockForUpdate()
 * di dalam satu transaksi; bila satu tanggal saja penuh, seluruh pemesanan dibatalkan.
 */
class BookingService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private PricingService $pricing) {}

    /**
     * Ketersediaan semua tipe unit aktif untuk rentang menginap (tanpa mengunci).
     *
     * @return Collection<int, array{unit_type: UnitType, available: int}>
     */
    public function availability(CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $onlineOnly = false): Collection
    {
        $this->assertValidDates($checkIn, $checkOut, allowPast: ! $onlineOnly);

        $unitTypes = UnitType::query()->active()->ordered()
            ->when($onlineOnly, fn ($q) => $q->where('is_bookable_online', true))
            ->get();

        $this->ensureInventory($unitTypes->pluck('id')->all(), $checkIn, $checkOut->subDay());

        $rows = DailyInventory::query()
            ->whereIn('unit_type_id', $unitTypes->pluck('id'))
            ->whereBetween('date', [$checkIn->toDateString(), $checkOut->subDay()->toDateString()])
            ->get()
            ->groupBy('unit_type_id');

        return $unitTypes->map(fn (UnitType $type) => [
            'unit_type' => $type,
            'available' => (int) ($rows->get($type->id)?->min(fn (DailyInventory $row) => $row->available()) ?? 0),
        ]);
    }

    /**
     * Buat booking berstatus Pending dan tahan stok selama hold_minutes.
     *
     * @param  array{
     *     customer: array{name: string, phone: string, email?: ?string, organization?: ?string},
     *     lines: array<int, int>,
     *     check_in: string|\DateTimeInterface,
     *     check_out: string|\DateTimeInterface,
     *     guests: int,
     *     payment_option?: string,
     *     channel?: BookingChannel|string,
     *     promo_code?: ?string,
     *     notes?: ?string,
     *     internal_notes?: ?string,
     *     created_by?: ?int,
     *     customer_account_id?: ?int,
     * }  $data  customer_account_id diisi server dari sesi login (bukan dari input tamu)
     */
    public function createHold(array $data): Booking
    {
        $checkIn = CarbonImmutable::parse($data['check_in'])->startOfDay();
        $checkOut = CarbonImmutable::parse($data['check_out'])->startOfDay();
        $channel = $data['channel'] instanceof BookingChannel ? $data['channel'] : BookingChannel::from($data['channel'] ?? 'web');
        $lines = collect($data['lines'] ?? [])->map(fn ($qty) => (int) $qty)->filter(fn ($qty) => $qty > 0)->all();

        $this->assertValidDates($checkIn, $checkOut, allowPast: $channel !== BookingChannel::Web);

        if ($lines === []) {
            throw new BookingException('Pilih minimal satu akomodasi.');
        }

        $unitTypes = UnitType::query()->whereIn('id', array_keys($lines))->get()->keyBy('id');
        $this->assertUnitTypesBookable($unitTypes, $lines, $channel);
        $this->assertGuestsFit($unitTypes, $lines, (int) $data['guests']);

        $paymentOption = $this->resolvePaymentOption($data['payment_option'] ?? 'dp', $checkIn);

        // Harga dihitung ulang di server, sebelum transaksi (tidak butuh kunci).
        $quote = $this->pricing->quoteBooking($lines, $checkIn, $checkOut, $data['promo_code'] ?? null);
        $lastNight = $checkOut->subDay();

        $this->ensureInventory(array_keys($lines), $checkIn, $lastNight);

        return DB::transaction(function () use ($data, $lines, $unitTypes, $checkIn, $checkOut, $lastNight, $channel, $paymentOption, $quote) {
            $this->holdStock($lines, $unitTypes, $checkIn, $lastNight);

            if ($quote['promo']) {
                $this->claimPromo($quote['promo']);
            }

            $booking = Booking::create([
                'code' => $this->generateCode(),
                'customer_id' => $this->findOrCreateCustomer($data['customer'])->id,
                'customer_account_id' => $data['customer_account_id'] ?? null,
                'channel' => $channel,
                'status' => BookingStatus::Pending,
                'payment_option' => $paymentOption,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $quote['nights'],
                'guests' => (int) $data['guests'],
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'total' => $quote['total'],
                'dp_amount' => $paymentOption === 'dp' ? $quote['dp_amount'] : $quote['total'],
                'promo_id' => $quote['promo']?->id,
                'due_date' => $paymentOption === 'dp' ? $checkIn->subDays((int) Setting::get('settlement_days_before', 3)) : null,
                'hold_expires_at' => now()->addMinutes((int) Setting::get('hold_minutes', 60)),
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($quote['items'] as $item) {
                /** @var UnitType $type */
                $type = $item['unit_type'];
                $booking->items()->create([
                    'item_type' => BookingItem::TYPE_UNIT_TYPE,
                    'item_id' => $type->id,
                    'description' => $type->marketing_name,
                    'qty' => $item['qty'],
                    'pax' => $type->isPricedPerPerson() ? $item['qty'] : $type->capacity_pax * $item['qty'],
                    'unit_price' => $item['average'],
                    'subtotal' => $item['subtotal'],
                    'breakdown' => $item['nights'],
                ]);
            }

            return $booking->load('items', 'customer');
        }, attempts: 3); // transaksi diulang otomatis bila MySQL melaporkan deadlock
    }

    /**
     * Booking dari penawaran B2B yang sudah Deal. Harga = total penawaran (dihitung QuotationService).
     * Unit yang dialokasikan untuk rombongan ditahan dengan aturan stok yang sama seperti B2C.
     *
     * @param  array<int, int>  $lines  [unit_type_id => qty]; boleh kosong bila paket tidak memakai stok unit
     */
    public function createFromQuotation(Quotation $quotation, array $lines, ?int $createdBy = null, ?int $holdMinutes = null): Booking
    {
        $quotation->loadMissing(['customer', 'variant']);
        $checkIn = CarbonImmutable::parse($quotation->event_date)->startOfDay();
        $checkOut = $checkIn->addDays($quotation->nights);
        $lastNight = $checkOut->subDay();
        $lines = collect($lines)->map(fn ($qty) => (int) $qty)->filter(fn ($qty) => $qty > 0)->all();

        $this->assertValidDates($checkIn, $checkOut);

        $unitTypes = UnitType::query()->whereIn('id', array_keys($lines))->get()->keyBy('id');
        $this->assertUnitTypesBookable($unitTypes, $lines, BookingChannel::B2b);

        $paymentOption = CarbonImmutable::today()->diffInDays($checkIn, false) < (int) Setting::get('settlement_days_before', 3) ? 'full' : 'dp';
        $total = (int) $quotation->total;
        $holdMinutes = max(15, min(7 * 24 * 60, $holdMinutes ?? (int) Setting::get('hold_minutes', 60)));

        $this->ensureInventory(array_keys($lines), $checkIn, $lastNight);

        return DB::transaction(function () use ($quotation, $lines, $unitTypes, $checkIn, $checkOut, $lastNight, $paymentOption, $total, $holdMinutes, $createdBy) {
            if ($lines !== []) {
                $this->holdStock($lines, $unitTypes, $checkIn, $lastNight);
            }

            $booking = Booking::create([
                'code' => $this->generateCode(),
                'customer_id' => $quotation->customer_id,
                'channel' => BookingChannel::B2b,
                'status' => BookingStatus::Pending,
                'payment_option' => $paymentOption,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $quotation->nights,
                'guests' => $quotation->pax,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'dp_amount' => $paymentOption === 'dp' ? $this->pricing->dpAmount($total) : $total,
                'due_date' => $paymentOption === 'dp' ? $checkIn->subDays((int) Setting::get('settlement_days_before', 3)) : null,
                'hold_expires_at' => now()->addMinutes($holdMinutes),
                'internal_notes' => "Dari penawaran {$quotation->number}",
                'created_by' => $createdBy,
            ]);

            $booking->items()->create([
                'item_type' => BookingItem::TYPE_PACKAGE,
                'item_id' => $quotation->id,
                'description' => ($quotation->variant?->name ?? 'Paket rombongan')." (penawaran {$quotation->number})",
                'qty' => 1,
                'pax' => $quotation->pax,
                'unit_price' => $total,
                'subtotal' => $total,
            ]);

            // Unit yang dialokasikan: hanya untuk stok & penugasan check-in (harga sudah di paket)
            foreach ($lines as $unitTypeId => $qty) {
                $type = $unitTypes[$unitTypeId];
                $booking->items()->create([
                    'item_type' => BookingItem::TYPE_UNIT_TYPE,
                    'item_id' => $type->id,
                    'description' => $type->marketing_name.' (alokasi rombongan)',
                    'qty' => $qty,
                    'pax' => $type->isPricedPerPerson() ? $qty : $type->capacity_pax * $qty,
                    'unit_price' => 0,
                    'subtotal' => 0,
                ]);
            }

            return $booking->load('items', 'customer');
        }, attempts: 3);
    }

    /**
     * Kunci baris stok semua tanggal, pastikan cukup, lalu tambahkan ke kolom held.
     * Satu tanggal penuh = seluruh pemesanan gagal. Harus dipanggil di dalam transaksi.
     */
    private function holdStock(array $lines, Collection $unitTypes, CarbonImmutable $checkIn, CarbonImmutable $lastNight): void
    {
        $rows = $this->lockInventory(array_keys($lines), $checkIn, $lastNight);

        foreach ($rows as $row) {
            $qty = $lines[$row->unit_type_id];

            if ($row->is_blocked || $row->booked + $row->held + $qty > $row->capacity) {
                $name = $unitTypes[$row->unit_type_id]->marketing_name;
                throw new BookingException(
                    "Maaf, {$name} tidak tersedia untuk tanggal {$row->date->translatedFormat('d F Y')}. Silakan pilih tanggal atau akomodasi lain."
                );
            }
        }

        foreach ($rows as $row) {
            $row->increment('held', $lines[$row->unit_type_id]);
        }
    }

    /**
     * Pindahkan stok dari held ke booked setelah pembayaran pertama terverifikasi.
     * Dipanggil oleh PaymentService di dalam transaksinya.
     */
    public function confirm(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status->isConfirmed()) {
                return; // sudah dikonfirmasi sebelumnya (idempoten)
            }

            if ($booking->status !== BookingStatus::Pending) {
                throw new BookingException('Booking ini sudah '.strtolower($booking->status->getLabel()).' sehingga tidak bisa dikonfirmasi.');
            }

            foreach ($this->lockBookingInventory($booking) as [$row, $qty]) {
                $row->decrement('held', min($qty, $row->held));
                $row->increment('booked', $qty);
            }

            $booking->update(['confirmed_at' => now(), 'hold_expires_at' => null]);
        });
    }

    /**
     * Pembayaran online masuk setelah hold kedaluwarsa: tahan ulang stok bila masih tersedia.
     * Booking kembali Pending (stok di held) lalu PaymentService melanjutkan konfirmasi seperti biasa.
     * Melempar BookingException bila stok sudah diambil tamu lain (perlu refund manual).
     */
    public function reinstate(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Expired) {
                return;
            }

            $rows = $this->lockBookingInventory($booking);

            foreach ($rows as [$row, $qty]) {
                if ($row->is_blocked || $row->booked + $row->held + $qty > $row->capacity) {
                    throw new BookingException('Stok untuk booking ini sudah terjual ke tamu lain setelah waktu pembayaran habis.');
                }
            }

            foreach ($rows as [$row, $qty]) {
                $row->increment('held', $qty);
            }

            if ($booking->promo_id) {
                Promo::query()->whereKey($booking->promo_id)->increment('used');
            }

            $booking->update(['status' => BookingStatus::Pending, 'expired_at' => null, 'hold_expires_at' => null]);
        });
    }

    /**
     * Booking DP yang perlu diingatkan pelunasannya hari ini (H-7 dan H-4 sebelum kedatangan).
     *
     * @return Collection<int, Booking>
     */
    public function bookingsDueForReminder(?CarbonImmutable $today = null): Collection
    {
        $today ??= CarbonImmutable::today();

        return Booking::query()
            ->with('customer')
            ->where('status', BookingStatus::DpPaid)
            ->whereIn('check_in', [$today->addDays(7)->toDateString(), $today->addDays(4)->toDateString()])
            ->whereColumn('paid_amount', '<', 'total')
            ->get();
    }

    /** Lepas semua hold yang sudah lewat waktunya. Dijalankan scheduler tiap menit. */
    public function expireOverdueHolds(): int
    {
        $count = 0;

        Booking::query()
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->pluck('id')
            ->each(function (int $id) use (&$count) {
                $count += $this->expire(Booking::find($id)) ? 1 : 0;
            });

        return $count;
    }

    public function expire(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::Pending) {
                return false;
            }

            $this->releaseStock($booking, 'held');
            $this->releasePromo($booking);
            $booking->update(['status' => BookingStatus::Expired, 'expired_at' => now(), 'hold_expires_at' => null]);

            return true;
        });
    }

    /** Pembatalan manual oleh admin; stok langsung dikembalikan. */
    public function cancel(Booking $booking, string $reason): void
    {
        DB::transaction(function () use ($booking, $reason) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status->isFinal()) {
                throw new BookingException('Booking yang sudah '.strtolower($booking->status->getLabel()).' tidak bisa dibatalkan.');
            }

            $this->releaseStock($booking, $booking->status->isHolding() ? 'held' : 'booked');
            $this->releasePromo($booking);
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'hold_expires_at' => null,
            ]);
        });
    }

    /**
     * Check-in di front office. Hanya booking lunas.
     *
     * @param  array<int, array<int, int>>  $assignments  [booking_item_id => [unit_id, ...]]
     */
    public function checkIn(Booking $booking, array $assignments = []): void
    {
        DB::transaction(function () use ($booking, $assignments) {
            $booking = Booking::query()->with('items')->lockForUpdate()->findOrFail($booking->id);

            if ($booking->status !== BookingStatus::FullyPaid) {
                throw new BookingException('Check-in hanya bisa untuk booking yang sudah lunas.');
            }

            foreach ($booking->items->filter->isUnitType() as $item) {
                $unitIds = array_values(array_filter($assignments[$item->id] ?? []));

                if ($unitIds === []) {
                    continue;
                }

                $valid = Unit::query()->whereIn('id', $unitIds)->where('unit_type_id', $item->item_id)->where('is_active', true)->count();
                if ($valid !== count($unitIds)) {
                    throw new BookingException("Unit yang dipilih untuk {$item->description} tidak sesuai tipenya.");
                }

                if (count($unitIds) > $item->qty) {
                    throw new BookingException("{$item->description} hanya dipesan {$item->qty} unit.");
                }

                $this->assertUnitsFree($unitIds, $booking);
                $item->units()->sync($unitIds);
            }

            $booking->update(['status' => BookingStatus::CheckedIn, 'checked_in_at' => now()]);
        });
    }

    /** Blokir / buka blokir tanggal untuk perawatan atau stok yang terjual di OTA. */
    public function setBlocked(UnitType $unitType, CarbonImmutable $from, CarbonImmutable $to, bool $blocked, ?string $note = null): int
    {
        if ($to->lt($from)) {
            throw new BookingException('Tanggal akhir tidak boleh sebelum tanggal mulai.');
        }

        $this->ensureInventory([$unitType->id], $from, $to);

        return DailyInventory::query()
            ->where('unit_type_id', $unitType->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->update(['is_blocked' => $blocked, 'block_note' => $blocked ? $note : null, 'updated_at' => now()]);
    }

    /** Samakan kapasitas tanggal mendatang setelah jumlah unit diubah di admin. */
    public function syncCapacity(UnitType $unitType): int
    {
        return DailyInventory::query()
            ->where('unit_type_id', $unitType->id)
            ->where('date', '>=', today()->toDateString())
            ->update(['capacity' => $unitType->total_units, 'updated_at' => now()]);
    }

    /**
     * Pastikan baris stok ada untuk setiap tipe unit & tanggal (dibuat bila belum ada).
     *
     * @param  array<int, int>  $unitTypeIds
     */
    public function ensureInventory(array $unitTypeIds, CarbonImmutable $from, CarbonImmutable $to): void
    {
        if ($to->lt($from) || $unitTypeIds === []) {
            return;
        }

        $existing = DailyInventory::query()
            ->whereIn('unit_type_id', $unitTypeIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['unit_type_id', 'date'])
            ->map(fn (DailyInventory $row) => $row->unit_type_id.'|'.$row->date->toDateString())
            ->flip();

        $capacities = UnitType::query()->whereIn('id', $unitTypeIds)->pluck('total_units', 'id');
        $now = now();
        $rows = [];

        foreach ($this->pricing->nights($from, $to->addDay()) as $date) {
            foreach ($capacities as $id => $capacity) {
                if (! $existing->has($id.'|'.$date->toDateString())) {
                    $rows[] = [
                        'unit_type_id' => $id,
                        'date' => $date->toDateString(),
                        'capacity' => $capacity,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Dua pemesan bisa membuat baris yang sama bersamaan; MySQL lalu bisa melaporkan
        // deadlock. Baris yang "kalah" cukup dicoba ulang (insert ignore aman diulang).
        foreach (array_chunk($rows, 500) as $chunk) {
            retry(5, fn () => DailyInventory::query()->insertOrIgnore($chunk), 50, fn ($e) => $this->isDeadlock($e));
        }
    }

    private function isDeadlock(Throwable $e): bool
    {
        return $e instanceof QueryException
            && in_array($e->errorInfo[1] ?? null, [1213, 1205], true); // deadlock / lock wait timeout
    }

    /**
     * Kunci baris stok (urutan tetap supaya tidak deadlock) dan pastikan lengkap.
     *
     * @return Collection<int, DailyInventory>
     */
    private function lockInventory(array $unitTypeIds, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $rows = DailyInventory::query()
            ->whereIn('unit_type_id', $unitTypeIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('unit_type_id')
            ->orderBy('date')
            ->lockForUpdate()
            ->get();

        $expected = count($unitTypeIds) * ($from->diffInDays($to) + 1);
        if ($rows->count() !== (int) $expected) {
            throw new BookingException('Data ketersediaan belum lengkap. Silakan coba lagi.');
        }

        return $rows;
    }

    /** @return array<int, array{0: DailyInventory, 1: int}> */
    private function lockBookingInventory(Booking $booking): array
    {
        $lines = $booking->items()
            ->where('item_type', BookingItem::TYPE_UNIT_TYPE)
            ->get()
            ->groupBy('item_id')
            ->map(fn ($items) => (int) $items->sum('qty'));

        if ($lines->isEmpty()) {
            return [];
        }

        $rows = $this->lockInventory($lines->keys()->all(), $booking->check_in->toImmutable(), $booking->check_out->toImmutable()->subDay());

        return $rows->map(fn (DailyInventory $row) => [$row, $lines[$row->unit_type_id]])->all();
    }

    private function releaseStock(Booking $booking, string $column): void
    {
        foreach ($this->lockBookingInventory($booking) as [$row, $qty]) {
            $row->decrement($column, min($qty, $row->{$column}));
        }
    }

    private function claimPromo(Promo $promo): void
    {
        $promo = Promo::query()->lockForUpdate()->find($promo->id);

        if ($promo->quota !== null && $promo->used >= $promo->quota) {
            throw new BookingException('Kuota kode promo sudah habis.');
        }

        $promo->increment('used');
    }

    private function releasePromo(Booking $booking): void
    {
        if ($booking->promo_id) {
            Promo::query()->whereKey($booking->promo_id)->where('used', '>', 0)->decrement('used');
        }
    }

    private function assertValidDates(CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $allowPast = false): void
    {
        if ($checkOut->lte($checkIn)) {
            throw new BookingException('Tanggal check-out harus setelah tanggal check-in.');
        }

        if (! $allowPast && $checkIn->lt(CarbonImmutable::today())) {
            throw new BookingException('Tanggal check-in tidak boleh di masa lalu.');
        }

        if ($checkIn->diffInDays($checkOut) > 30) {
            throw new BookingException('Lama menginap maksimal 30 malam. Untuk masa inap lebih lama, silakan hubungi kami.');
        }

        $window = (int) Setting::get('booking_window_days', 365);
        if ($checkIn->gt(CarbonImmutable::today()->addDays($window))) {
            throw new BookingException("Pemesanan hanya bisa dilakukan maksimal {$window} hari ke depan.");
        }
    }

    private function assertUnitTypesBookable(Collection $unitTypes, array $lines, BookingChannel $channel): void
    {
        foreach (array_keys($lines) as $id) {
            $type = $unitTypes->get($id);

            if (! $type || ! $type->is_active || ($channel === BookingChannel::Web && ! $type->is_bookable_online)) {
                throw new BookingException('Salah satu akomodasi yang dipilih sedang tidak bisa dipesan.');
            }
        }
    }

    private function assertGuestsFit(Collection $unitTypes, array $lines, int $guests): void
    {
        if ($guests < 1) {
            throw new BookingException('Jumlah tamu minimal 1 orang.');
        }

        $capacity = collect($lines)->sum(function (int $qty, int $id) use ($unitTypes) {
            $type = $unitTypes[$id];

            return $type->isPricedPerPerson() ? $qty : $type->capacity_pax * $qty;
        });

        if ($guests > $capacity) {
            throw new BookingException("Jumlah tamu ({$guests} orang) melebihi kapasitas akomodasi yang dipilih ({$capacity} orang). Tambah unit atau pilih akomodasi lain.");
        }
    }

    /** Booking kurang dari H-3 hanya bisa dibayar lunas (dokumen teknis bab 6). */
    private function resolvePaymentOption(string $option, CarbonImmutable $checkIn): string
    {
        $option = $option === 'full' ? 'full' : 'dp';
        $daysBefore = (int) Setting::get('settlement_days_before', 3);

        if ($option === 'dp' && CarbonImmutable::today()->diffInDays($checkIn, false) < $daysBefore) {
            throw new BookingException("Pemesanan kurang dari {$daysBefore} hari sebelum kedatangan harus dibayar lunas.");
        }

        return $option;
    }

    private function assertUnitsFree(array $unitIds, Booking $booking): void
    {
        $taken = DB::table('booking_item_unit')
            ->join('booking_items', 'booking_items.id', '=', 'booking_item_unit.booking_item_id')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->join('units', 'units.id', '=', 'booking_item_unit.unit_id')
            ->whereIn('booking_item_unit.unit_id', $unitIds)
            ->where('bookings.id', '!=', $booking->id)
            ->where('bookings.status', BookingStatus::CheckedIn->value)
            ->where('bookings.check_in', '<', $booking->check_out->toDateString())
            ->where('bookings.check_out', '>', $booking->check_in->toDateString())
            ->pluck('units.code');

        if ($taken->isNotEmpty()) {
            throw new BookingException('Unit '.$taken->implode(', ').' masih ditempati tamu lain pada tanggal tersebut.');
        }
    }

    public function findOrCreateCustomer(array $data): Customer
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $data['phone']);

        $customer = Customer::query()->firstOrNew(['phone' => $phone]);
        $customer->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? $customer->email,
            'organization' => $data['organization'] ?? $customer->organization,
        ])->save();

        return $customer;
    }

    private function generateCode(): string
    {
        do {
            $code = 'LG'.collect(range(1, 8))->map(fn () => self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)])->implode('');
        } while (Booking::query()->where('code', $code)->exists());

        return $code;
    }
}
