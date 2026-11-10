<?php

namespace App\Livewire;

use App\Enums\BookingChannel;
use App\Exceptions\BookingException;
use App\Models\CustomerAccount;
use App\Models\Setting;
use App\Models\UnitType;
use App\Services\BookingService;
use App\Services\CustomerAccountService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Alur booking B2C: tanggal & tamu → akomodasi → data pemesan → pembayaran.
 * Komponen ini hanya mengumpulkan input; stok & harga diputuskan oleh service di server.
 */
class BookingWizard extends Component
{
    public int $step = 1;

    public string $checkIn = '';

    public string $checkOut = '';

    public int $guests = 2;

    /** @var array<int|string, int> [unit_type_id => jumlah] */
    public array $qty = [];

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $organization = '';

    public string $notes = '';

    public string $paymentOption = 'dp';

    public string $promoCode = '';

    public bool $agree = false;

    /** Honeypot anti-bot: harus tetap kosong. */
    public string $website = '';

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $request = request();
        $this->checkIn = (string) $request->query('check_in', '');
        $this->checkOut = (string) $request->query('check_out', '');
        $this->guests = max(1, min(200, (int) $request->query('guests', 2)));

        if ($slug = $request->query('unit')) {
            if ($type = UnitType::query()->where('slug', $slug)->value('id')) {
                $this->qty[$type] = 1;
            }
        }

        // Pilihan yang dibawa kembali setelah masuk/daftar (?pilih[id]=jumlah&promo=...)
        foreach ((array) $request->query('pilih', []) as $id => $qty) {
            if (ctype_digit((string) $id) && (int) $qty > 0) {
                $this->qty[(int) $id] = min(50, (int) $qty);
            }
        }
        $this->promoCode = mb_substr((string) $request->query('promo', ''), 0, 30);

        // Pelanggan yang login: isi otomatis data pemesan (tetap bisa diubah)
        if ($account = Auth::guard('customer')->user()) {
            $this->name = $account->name;
            $this->email = $account->email;
            $this->phone = (string) $account->phone;
        }

        if ($this->checkIn && $this->checkOut && $this->datesValid()) {
            $this->step = 2;
            $this->clampQtyToAvailability();

            // Kembali dari halaman masuk: langsung ke langkah data pemesan
            if ($request->boolean('lanjut') && $this->qty !== [] && $account?->hasVerifiedEmail()) {
                $this->toCustomer();
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Navigasi langkah
    |--------------------------------------------------------------------------
    */

    public function toAccommodation(): void
    {
        $this->validate($this->dateRules(), $this->messages());
        $this->errorMessage = null;

        if (! $this->datesValid()) {
            return;
        }

        $this->step = 2;
        $this->dispatch('langkah-berubah');
    }

    public function toCustomer()
    {
        $this->errorMessage = null;
        $quote = $this->quote;

        if (! $quote) {
            $this->errorMessage = 'Pilih minimal satu akomodasi untuk melanjutkan.';

            return null;
        }

        if (isset($quote['error'])) {
            $this->errorMessage = $quote['error'];

            return null;
        }

        if ($this->guests > $this->selectedCapacity) {
            $this->errorMessage = "Kapasitas akomodasi yang dipilih ({$this->selectedCapacity} orang) belum cukup untuk {$this->guests} tamu. Tambah jumlah unit atau pilih akomodasi lain.";

            return null;
        }

        // Pemesanan online wajib akun: minta masuk/daftar, pilihan dibawa kembali lewat URL
        if ($this->redirectedToLogin()) {
            return null;
        }

        if (! $this->dpAllowed) {
            $this->paymentOption = 'full';
        }

        $this->step = 3;
        $this->dispatch('langkah-berubah');

        return null;
    }

    public function back(): void
    {
        $this->errorMessage = null;
        $this->step = max(1, $this->step - 1);
        $this->dispatch('langkah-berubah');
    }

    public function changeQty(int $unitTypeId, int $delta): void
    {
        $available = (int) ($this->availability->firstWhere('unit_type.id', $unitTypeId)['available'] ?? 0);
        $new = max(0, min($available, (int) ($this->qty[$unitTypeId] ?? 0) + $delta));

        if ($new === 0) {
            unset($this->qty[$unitTypeId]);
        } else {
            $this->qty[$unitTypeId] = $new;
        }

        $this->errorMessage = null;
        unset($this->quote);
    }

    public function updatedGuests(): void
    {
        $this->guests = max(1, min(200, $this->guests));
    }

    public function updatedPromoCode(): void
    {
        unset($this->quote);
    }

    /*
    |--------------------------------------------------------------------------
    | Kirim pemesanan
    |--------------------------------------------------------------------------
    */

    public function submit(BookingService $bookings)
    {
        $this->errorMessage = null;

        // Dicek ulang di server: sesi bisa saja sudah berakhir, atau langkah dimanipulasi dari browser
        if ($this->redirectedToLogin()) {
            return null;
        }

        $this->phone = preg_replace('/[\s\-().]/', '', $this->phone);
        $this->validate($this->customerRules(), $this->messages());

        if ($this->website !== '') {
            return null; // bot
        }

        $key = 'booking:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->errorMessage = 'Terlalu banyak percobaan. Silakan tunggu '.RateLimiter::availableIn($key).' detik lalu coba lagi.';

            return null;
        }
        RateLimiter::hit($key, 60);

        // Pemilik booking = akun yang login (dari sesi server, bukan dari input tamu)
        $account = Auth::guard('customer')->user();
        $lines = array_filter(array_map('intval', $this->qty));

        // Satu pengiriman per akun dalam satu waktu (klik ganda / dua tab tidak membuat dua booking)
        $lock = Cache::lock('pesan:akun:'.$account->id, 30);
        if (! $lock->get()) {
            $this->errorMessage = 'Pemesanan Anda sedang diproses. Mohon tunggu sebentar.';

            return null;
        }

        try {
            if ($existing = app(CustomerAccountService::class)->guardNewBooking($account, $this->checkIn, $this->checkOut, $lines)) {
                return $this->redirectRoute('booking.show', ['booking' => $existing, 'bayar' => 1]);
            }

            $booking = $bookings->createHold([
                'customer_account_id' => $account->id,
                'customer' => [
                    'name' => trim($this->name),
                    'phone' => $this->phone,
                    'email' => trim($this->email),
                    'organization' => trim($this->organization) ?: null,
                ],
                'lines' => $lines,
                'check_in' => $this->checkIn,
                'check_out' => $this->checkOut,
                'guests' => $this->guests,
                'payment_option' => $this->dpAllowed ? $this->paymentOption : 'full',
                'promo_code' => trim($this->promoCode) ?: null,
                'notes' => trim($this->notes) ?: null,
                'channel' => BookingChannel::Web,
            ]);
        } catch (BookingException $e) {
            $this->errorMessage = $e->getMessage();
            unset($this->availability, $this->quote);

            return null;
        } finally {
            $lock->release();
        }

        return $this->redirectRoute('booking.show', ['booking' => $booking, 'bayar' => 1]);
    }

    /*
    |--------------------------------------------------------------------------
    | Data turunan (dihitung server)
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function availability()
    {
        if (! $this->datesValid(silent: true)) {
            return collect();
        }

        try {
            $pricing = app(PricingService::class);

            return app(BookingService::class)
                ->availability(CarbonImmutable::parse($this->checkIn), CarbonImmutable::parse($this->checkOut), onlineOnly: true)
                ->map(function (array $row) use ($pricing) {
                    try {
                        $row['stay'] = $pricing->quoteStay($row['unit_type'], CarbonImmutable::parse($this->checkIn), CarbonImmutable::parse($this->checkOut), 1);
                    } catch (BookingException) {
                        $row['stay'] = null;
                    }
                    $row['unit_type']->loadMissing('media');

                    return $row;
                });
        } catch (BookingException $e) {
            $this->errorMessage = $e->getMessage();

            return collect();
        }
    }

    #[Computed]
    public function quote(): ?array
    {
        $lines = array_filter(array_map('intval', $this->qty));

        if ($lines === [] || ! $this->datesValid(silent: true)) {
            return null;
        }

        try {
            return app(PricingService::class)->quoteBooking(
                $lines,
                CarbonImmutable::parse($this->checkIn),
                CarbonImmutable::parse($this->checkOut),
                trim($this->promoCode) ?: null,
            );
        } catch (BookingException $e) {
            if (trim($this->promoCode) !== '') {
                $withoutPromo = $this->promoCode;
                $this->promoCode = '';
                $quote = $this->quote();
                $this->promoCode = $withoutPromo;

                return $quote ? $quote + ['promo_error' => $e->getMessage()] : ['error' => $e->getMessage()];
            }

            return ['error' => $e->getMessage()];
        }
    }

    #[Computed]
    public function selectedCapacity(): int
    {
        return (int) UnitType::query()->whereIn('id', array_keys(array_filter($this->qty)))->get()
            ->sum(fn (UnitType $type) => ($type->isPricedPerPerson() ? 1 : $type->capacity_pax) * (int) $this->qty[$type->id]);
    }

    #[Computed]
    public function dpAllowed(): bool
    {
        if (! $this->checkIn) {
            return true;
        }

        return CarbonImmutable::today()->diffInDays(CarbonImmutable::parse($this->checkIn), false) >= (int) Setting::get('settlement_days_before', 3);
    }

    public function nights(): int
    {
        return $this->datesValid(silent: true) ? (int) CarbonImmutable::parse($this->checkIn)->diffInDays(CarbonImmutable::parse($this->checkOut)) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi
    |--------------------------------------------------------------------------
    */

    private function datesValid(bool $silent = false): bool
    {
        try {
            $in = CarbonImmutable::parse($this->checkIn)->startOfDay();
            $out = CarbonImmutable::parse($this->checkOut)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        $message = match (true) {
            $in->lt(CarbonImmutable::today()) => 'Tanggal check-in tidak boleh di masa lalu.',
            $out->lte($in) => 'Tanggal check-out harus setelah tanggal check-in.',
            $in->diffInDays($out) > 30 => 'Lama menginap maksimal 30 malam.',
            default => null,
        };

        if ($message && ! $silent) {
            $this->errorMessage = $message;
        }

        return $message === null;
    }

    /**
     * Langkah data pemesan & pembayaran butuh akun (email terverifikasi bila diwajibkan).
     * Tamu diarahkan ke halaman masuk; setelah masuk kembali ke wizard dengan pilihan yang sama.
     *
     * @return bool true = belum boleh lanjut (sudah diarahkan ke halaman masuk/verifikasi)
     */
    private function redirectedToLogin(): bool
    {
        /** @var CustomerAccount|null $account */
        $account = Auth::guard('customer')->user();

        if ($account?->hasVerifiedEmail()) {
            return false;
        }

        redirect()->setIntendedUrl($this->resumeUrl());
        session()->flash('notice', $account
            ? 'Verifikasi email Anda untuk melanjutkan pemesanan. Tanggal dan akomodasi pilihan Anda tersimpan.'
            : 'Masuk atau daftar akun (gratis) untuk melanjutkan pemesanan. Tanggal dan akomodasi pilihan Anda tersimpan.');

        $this->redirectRoute($account ? 'akun.verifikasi.notice' : 'akun.masuk');

        return true;
    }

    /** URL wizard berisi pilihan non-sensitif (tanggal, tamu, unit, kode promo) untuk dilanjutkan setelah masuk. */
    private function resumeUrl(): string
    {
        return route('pesan', array_filter([
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'guests' => $this->guests,
            'pilih' => array_filter(array_map('intval', $this->qty)),
            'promo' => trim($this->promoCode) ?: null,
            'lanjut' => 1,
        ]));
    }

    /** Jumlah unit dari URL tidak boleh melebihi stok yang tersedia (dihitung server). */
    private function clampQtyToAvailability(): void
    {
        foreach ($this->qty as $id => $qty) {
            $available = (int) ($this->availability->firstWhere('unit_type.id', (int) $id)['available'] ?? 0);
            $qty = min((int) $qty, $available);

            if ($qty > 0) {
                $this->qty[$id] = $qty;
            } else {
                unset($this->qty[$id]);
            }
        }

        unset($this->quote);
    }

    private function dateRules(): array
    {
        return [
            'checkIn' => ['required', 'date', 'after_or_equal:today'],
            'checkOut' => ['required', 'date', 'after:checkIn'],
            'guests' => ['required', 'integer', 'min:1', 'max:200'],
        ];
    }

    private function customerRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8\d{7,12}$/'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'paymentOption' => ['required', 'in:dp,full'],
            'promoCode' => ['nullable', 'string', 'max:30'],
            'agree' => ['accepted'],
        ];
    }

    protected function messages(): array
    {
        return [
            'checkIn.required' => 'Pilih tanggal check-in.',
            'checkIn.after_or_equal' => 'Tanggal check-in tidak boleh di masa lalu.',
            'checkOut.required' => 'Pilih tanggal check-out.',
            'checkOut.after' => 'Tanggal check-out harus setelah tanggal check-in.',
            'guests.min' => 'Jumlah tamu minimal 1 orang.',
            'guests.max' => 'Untuk rombongan lebih dari 200 orang, silakan hubungi tim sales kami.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama sepertinya terlalu pendek.',
            'phone.required' => 'Nomor WhatsApp wajib diisi agar kami bisa menghubungi Anda.',
            'phone.regex' => 'Nomor WhatsApp sepertinya belum benar. Contoh: 0812 3456 7890.',
            'email.required' => 'Email wajib diisi. E-ticket dikirim ke email ini.',
            'email.email' => 'Format email belum benar. Contoh: nama@email.com.',
            'agree.accepted' => 'Mohon setujui syarat & ketentuan untuk melanjutkan.',
        ];
    }

    public function render(): View
    {
        return view('livewire.booking-wizard', [
            'steps' => ['Tanggal & tamu', 'Akomodasi', 'Data pemesan', 'Pembayaran'],
            'dpPercent' => (int) Setting::get('dp_percent', 25),
            'holdMinutes' => (int) Setting::get('hold_minutes', 60),
            'settlementDays' => (int) Setting::get('settlement_days_before', 3),
        ]);
    }
}
