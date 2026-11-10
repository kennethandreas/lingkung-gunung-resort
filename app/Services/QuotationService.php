<?php

namespace App\Services;

use App\Enums\DayType;
use App\Enums\QuotationStatus;
use App\Exceptions\BookingException;
use App\Mail\ProposalMail;
use App\Models\Activity;
use App\Models\AdditionalItem;
use App\Models\Booking;
use App\Models\GroupPackageVariant;
use App\Models\MenuOption;
use App\Models\Quotation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Package Builder B2B: estimasi harga, aturan paket, nomor penawaran, proposal PDF,
 * dan konversi penawaran menjadi booking.
 *
 * Asumsi (docs/DATA-TODO.md): harga paket fullboard = per orang per MALAM
 * (isi paket "Meals 3x" = makan satu hari), tarif WD/WE mengikuti malamnya.
 */
class QuotationService
{
    public const MAX_NIGHTS = 7;

    public function __construct(
        private PricingService $pricing,
        private BookingService $bookings,
    ) {}

    /**
     * Estimasi harga + daftar pelanggaran aturan (untuk ditampilkan langsung di builder).
     *
     * @param  array{variant_id: int, event_date: string, nights: int, pax: int, menu_plan?: array, activities?: array<int, int>, items?: array<int, int>}  $input
     * @return array{variant: ?GroupPackageVariant, lines: array<int, array>, nights: array, subtotal: int, total: int, dp_amount: int, errors: array<int, string>, valid: bool}
     */
    public function estimate(array $input): array
    {
        $errors = [];
        $lines = [];
        $nightly = [];

        $variant = GroupPackageVariant::query()->with(['groupPackage', 'menuSet.options'])->find($input['variant_id'] ?? null);
        $pax = (int) ($input['pax'] ?? 0);
        $nights = (int) ($input['nights'] ?? 1);

        try {
            $date = CarbonImmutable::parse($input['event_date'] ?? '')->startOfDay();
        } catch (\Throwable) {
            $date = null;
        }

        if (! $variant || ! $variant->is_active) {
            $errors[] = 'Pilih paket terlebih dahulu.';
        }
        if (! $date) {
            $errors[] = 'Pilih tanggal kedatangan.';
        } elseif ($date->lt(CarbonImmutable::today())) {
            $errors[] = 'Tanggal kedatangan tidak boleh di masa lalu.';
        }
        if ($nights < 1 || $nights > self::MAX_NIGHTS) {
            $errors[] = 'Lama acara 1 sampai '.self::MAX_NIGHTS.' malam. Untuk acara lebih lama, hubungi tim sales.';
            $nights = max(1, min(self::MAX_NIGHTS, $nights));
        }

        if ($variant && $date) {
            if ($pax < $variant->min_pax) {
                $errors[] = "{$variant->name} minimal {$variant->min_pax} peserta.";
            }

            foreach (range(0, $nights - 1) as $i) {
                $night = $date->addDays($i);
                $dayType = $this->pricing->dayTypeFor($night);
                $nightly[] = [
                    'date' => $night->toDateString(),
                    'day_type' => $dayType->value,
                    'price' => $dayType === DayType::Weekday ? $variant->price_weekday : $variant->price_weekend,
                ];
            }

            if ($variant->weekday_only && collect($nightly)->contains(fn ($n) => $n['day_type'] !== DayType::Weekday->value)) {
                $errors[] = "{$variant->name} hanya tersedia untuk malam weekday (bukan malam weekend atau hari libur).";
            }

            $perPax = array_sum(array_column($nightly, 'price'));
            $lines[] = [
                'item_type' => 'package',
                'item_id' => $variant->id,
                'description' => "{$variant->name}, {$nights} malam",
                'qty' => $pax,
                'unit_price' => $perPax,
                'subtotal' => $perPax * max(0, $pax),
                'meta' => ['nights' => $nightly, 'unit' => 'pax'],
            ];

            $errors = [...$errors, ...$this->menuErrors($variant, $input['menu_plan'] ?? [], $nights)];
        }

        // Aktivitas
        $activityQty = collect($input['activities'] ?? [])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);
        $activities = Activity::query()->whereIn('id', $activityQty->keys())->where('is_active', true)->get()->keyBy('id');
        foreach ($activityQty as $id => $qty) {
            // Aktivitas dinonaktifkan admin saat klien sedang memilih: tampilkan pesan, jangan crash
            if (! $activity = $activities->get($id)) {
                $errors[] = 'Salah satu aktivitas yang dipilih sudah tidak tersedia. Hapus lalu pilih ulang.';

                continue;
            }

            if ($activity->price_unit === 'pax' && $qty < $activity->min_pax) {
                $errors[] = "{$activity->name} minimal {$activity->min_pax} peserta.";
            }
            if ($activity->price_unit !== 'pax' && $pax < $activity->min_pax) {
                $errors[] = "{$activity->name} untuk rombongan minimal {$activity->min_pax} orang.";
            }

            $lines[] = [
                'item_type' => 'activity',
                'item_id' => $activity->id,
                'description' => $activity->name,
                'qty' => $qty,
                'unit_price' => $activity->price,
                'subtotal' => $activity->price * $qty,
                'meta' => ['unit' => $activity->price_unit],
            ];
        }

        // Item tambahan
        $itemQty = collect($input['items'] ?? [])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);
        $items = AdditionalItem::query()->whereIn('id', $itemQty->keys())->where('is_active', true)->get()->keyBy('id');
        foreach ($itemQty as $id => $qty) {
            if (! $item = $items->get($id)) {
                $errors[] = 'Salah satu item tambahan yang dipilih sudah tidak tersedia. Hapus lalu pilih ulang.';

                continue;
            }

            if ($qty < $item->min_qty) {
                $errors[] = "{$item->name} minimal {$item->min_qty} {$item->unit}.";
            }

            $lines[] = [
                'item_type' => 'additional_item',
                'item_id' => $item->id,
                'description' => $item->name.($item->unit_label ? " ({$item->unit_label})" : ''),
                'qty' => $qty,
                'unit_price' => $item->price,
                'subtotal' => $item->price * $qty,
                'meta' => ['unit' => $item->unit],
            ];
        }

        $total = array_sum(array_column($lines, 'subtotal'));

        return [
            'variant' => $variant,
            'lines' => $lines,
            'nights' => $nightly,
            'subtotal' => $total,
            'total' => $total,
            'dp_amount' => $this->pricing->dpAmount($total),
            'errors' => array_values(array_unique($errors)),
            'valid' => $errors === [] && $variant !== null,
        ];
    }

    /**
     * Rencana menu default: opsi A untuk setiap waktu makan, coffee break dari pilihan pertama.
     *
     * @return array<int, array{day: int, breakfast: ?string, lunch: ?string, dinner: ?string, coffee_breaks: array<int, array<int, string>>}>
     */
    public function defaultMenuPlan(GroupPackageVariant $variant, int $nights): array
    {
        $options = $this->menuOptions($variant);
        $firstCode = fn (string $meal) => $options->get($meal)?->first()?->option_code;
        $coffeeItems = $this->coffeeItems($variant);
        $choices = (int) ($variant->menuSet?->coffee_break_choices ?? 2);

        return collect(range(1, max(1, $nights)))->map(fn (int $day) => [
            'day' => $day,
            'breakfast' => $firstCode('breakfast'),
            'lunch' => $firstCode('lunch'),
            'dinner' => $firstCode('dinner'),
            'coffee_breaks' => array_fill(0, $variant->coffee_breaks, array_slice($coffeeItems, 0, $choices)),
        ])->all();
    }

    /** @return Collection<string, Collection<int, MenuOption>> opsi per waktu makan */
    public function menuOptions(GroupPackageVariant $variant): Collection
    {
        return ($variant->menuSet?->options ?? collect())->groupBy('meal');
    }

    /** @return array<int, string> semua pilihan item coffee break pada set menu varian */
    public function coffeeItems(GroupPackageVariant $variant): array
    {
        return $this->menuOptions($variant)->get('coffee_break', collect())->flatMap(fn ($o) => $o->items)->unique()->values()->all();
    }

    /**
     * Simpan penawaran dari Package Builder.
     *
     * @param  array{customer: array, notes?: ?string}  $input  + field estimate()
     */
    public function create(array $input): Quotation
    {
        $estimate = $this->estimate($input);

        if (! $estimate['valid']) {
            throw new BookingException($estimate['errors'][0] ?? 'Data paket belum lengkap.');
        }

        $nights = (int) $input['nights'];

        $quotation = DB::transaction(function () use ($input, $estimate, $nights) {
            $customer = $this->bookings->findOrCreateCustomer($input['customer']);

            $quotation = $this->insertWithNumber([
                'code' => $this->generateCode(),
                'customer_id' => $customer->id,
                'group_package_variant_id' => $estimate['variant']->id,
                'event_date' => $input['event_date'],
                'nights' => $nights,
                'days' => $nights + 1,
                'pax' => (int) $input['pax'],
                'menu_plan' => $this->normalizeMenuPlan($input['menu_plan'] ?? [], $nights),
                'status' => QuotationStatus::Baru,
                'subtotal' => $estimate['subtotal'],
                'total' => $estimate['total'],
                'notes' => $input['notes'] ?? null,
            ]);

            foreach ($estimate['lines'] as $line) {
                $quotation->items()->create($line);
            }

            return $quotation;
        });

        $this->storePdf($quotation);

        if (filled($quotation->customer->email)) {
            Mail::to($quotation->customer->email)->queue(new ProposalMail($quotation));
        }

        return $quotation->refresh();
    }

    /** Hitung ulang total dari item (setelah sales mengubah item di admin) lalu buat ulang PDF. */
    public function recalculate(Quotation $quotation): Quotation
    {
        $quotation->items->each(function ($item) {
            $item->update(['subtotal' => $item->qty * $item->unit_price]);
        });

        $total = (int) $quotation->items()->sum('subtotal');
        $quotation->update(['subtotal' => $total, 'total' => $total]);
        $this->storePdf($quotation);

        return $quotation->refresh();
    }

    public function updateStatus(Quotation $quotation, QuotationStatus $status, ?User $by = null): void
    {
        if ($quotation->booking_id && $status !== QuotationStatus::Deal) {
            throw new BookingException('Penawaran ini sudah dikonversi menjadi booking. Batalkan booking-nya terlebih dahulu bila perlu.');
        }

        $quotation->update(array_filter([
            'status' => $status,
            'handled_by' => $by?->id ?? $quotation->handled_by,
            'contacted_at' => $status === QuotationStatus::Dihubungi && ! $quotation->contacted_at ? now() : null,
        ], fn ($v) => $v !== null));
    }

    /**
     * Konversi penawaran (Deal) menjadi booking. Stok unit dikunci dengan aturan yang sama seperti B2C.
     *
     * @param  array<int, int>  $lines  [unit_type_id => qty] unit yang dialokasikan untuk rombongan (boleh kosong)
     */
    public function convertToBooking(Quotation $quotation, array $lines, User $by, ?int $holdMinutes = null): Booking
    {
        if ($quotation->booking_id) {
            throw new BookingException('Penawaran ini sudah memiliki booking.');
        }

        if ($quotation->status === QuotationStatus::Batal) {
            throw new BookingException('Penawaran yang dibatalkan tidak bisa dikonversi.');
        }

        $booking = $this->bookings->createFromQuotation($quotation, $lines, $by->id, $holdMinutes);

        $quotation->update(['booking_id' => $booking->id, 'status' => QuotationStatus::Deal, 'handled_by' => $by->id]);

        return $booking;
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(Quotation $quotation): string
    {
        $quotation->loadMissing(['customer', 'items', 'variant.groupPackage', 'variant.menuSet.options']);

        return Pdf::loadView('pdf.proposal', [
            'quotation' => $quotation,
            'menuOptions' => $quotation->variant ? $this->menuOptions($quotation->variant) : collect(),
            'dpAmount' => $this->pricing->dpAmount($quotation->total),
        ])->setOption('isFontSubsettingEnabled', true)->setPaper('a4')->output();
    }

    public function storePdf(Quotation $quotation): string
    {
        $path = "proposal/{$quotation->code}.pdf";
        Storage::disk('local')->put($path, $this->pdf($quotation));
        $quotation->update(['pdf_path' => $path]);

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    private function menuErrors(GroupPackageVariant $variant, array $plan, int $nights): array
    {
        $options = $this->menuOptions($variant);
        $coffeeItems = $this->coffeeItems($variant);
        $choices = (int) ($variant->menuSet?->coffee_break_choices ?? 2);
        $errors = [];

        foreach (range(1, $nights) as $day) {
            $row = collect($plan)->firstWhere('day', $day) ?? [];

            foreach (['breakfast' => 'sarapan', 'lunch' => 'makan siang', 'dinner' => 'makan malam'] as $meal => $label) {
                if (($options->get($meal)?->isNotEmpty()) && ! $options->get($meal)->contains('option_code', $row[$meal] ?? null)) {
                    $errors[] = "Pilih menu {$label} untuk hari ke-{$day}.";
                }
            }

            foreach (array_slice($row['coffee_breaks'] ?? [], 0, $variant->coffee_breaks) as $i => $picks) {
                $picks = array_values(array_unique((array) $picks));
                if ($coffeeItems !== [] && (count($picks) !== min($choices, count($coffeeItems)) || array_diff($picks, $coffeeItems))) {
                    $errors[] = 'Coffee break ke-'.($i + 1)." hari ke-{$day}: pilih {$choices} macam menu.";
                }
            }
        }

        return $errors;
    }

    private function normalizeMenuPlan(array $plan, int $nights): array
    {
        return collect(range(1, $nights))->map(function (int $day) use ($plan) {
            $row = collect($plan)->firstWhere('day', $day) ?? [];

            return [
                'day' => $day,
                'breakfast' => $row['breakfast'] ?? null,
                'lunch' => $row['lunch'] ?? null,
                'dinner' => $row['dinner'] ?? null,
                'coffee_breaks' => array_values(array_map(fn ($p) => array_values((array) $p), $row['coffee_breaks'] ?? [])),
            ];
        })->all();
    }

    /** Nomor penawaran berurutan per bulan (LG-Q-YYMM-0001); diulang bila bentrok. */
    private function insertWithNumber(array $attributes): Quotation
    {
        $prefix = 'LG-Q-'.now()->format('ym').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $last = Quotation::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
            $next = $last ? ((int) Str::afterLast($last, '-')) + 1 + $attempt : 1 + $attempt;

            try {
                return Quotation::query()->create($attributes + ['number' => $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT)]);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
            }
        }

        throw new BookingException('Gagal membuat nomor penawaran. Silakan coba lagi.');
    }

    private function generateCode(): string
    {
        do {
            $code = 'Q'.Str::upper(Str::random(10));
        } while (Quotation::query()->where('code', $code)->exists());

        return $code;
    }
}
