<?php

namespace App\Livewire;

use App\Exceptions\BookingException;
use App\Models\Activity;
use App\Models\AdditionalItem;
use App\Models\GroupPackage;
use App\Models\GroupPackageVariant;
use App\Services\QuotationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Package Builder B2B: paket & peserta → menu → aktivitas & tambahan → data instansi.
 * Harga & aturan diputuskan QuotationService di server; komponen ini hanya mengumpulkan pilihan.
 */
class PackageBuilder extends Component
{
    public int $step = 1;

    public ?int $variantId = null;

    public string $eventDate = '';

    public int $nights = 1;

    public int $pax = 30;

    /** @var array<int, array{day: int, breakfast: ?string, lunch: ?string, dinner: ?string, coffee_breaks: array}> */
    public array $menuPlan = [];

    /** @var array<int|string, int> [activity_id => qty] */
    public array $activities = [];

    /** @var array<int|string, int> [additional_item_id => qty] */
    public array $items = [];

    public string $name = '';

    public string $organization = '';

    public string $phone = '';

    public string $email = '';

    public string $notes = '';

    public bool $agree = false;

    public string $website = '';

    public array $stepErrors = [];

    public function mount(): void
    {
        $this->eventDate = today()->addDays(14)->toDateString();

        if (request('paket') && ($variant = GroupPackageVariant::query()->find(request('paket')))) {
            $this->selectVariant($variant->id);
        }

        // Template "Contoh 2 Days Outbound" dari brosur: 2 hari 1 malam + team building, rafting, paintball
        if (request('template') === '2-days-outbound') {
            $this->nights = 1;
            $this->pax = max($this->pax, 30);
            foreach (['team-building', 'rafting', 'paintball'] as $slug) {
                if ($activity = Activity::query()->where('slug', $slug)->where('is_active', true)->first()) {
                    $this->activities[$activity->id] = max($this->pax, $activity->min_pax);
                }
            }
        }
    }

    /** Setiap permintaan: buang aktivitas/item yang sudah dinonaktifkan admin (tidak tampil, jadi tidak bisa dihapus klien). */
    public function hydrate(): void
    {
        if ($this->activities !== []) {
            $active = Activity::query()->whereIn('id', array_keys($this->activities))->where('is_active', true)->pluck('id')->all();
            $this->activities = array_intersect_key($this->activities, array_flip($active));
        }

        if ($this->items !== []) {
            $active = AdditionalItem::query()->whereIn('id', array_keys($this->items))->where('is_active', true)->pluck('id')->all();
            $this->items = array_intersect_key($this->items, array_flip($active));
        }
    }

    public function selectVariant(int $variantId): void
    {
        $variant = GroupPackageVariant::query()->where('is_active', true)->find($variantId);

        if (! $variant) {
            return;
        }

        $this->variantId = $variant->id;
        $this->pax = max($this->pax, $variant->min_pax);
        $this->resetMenuPlan();
    }

    public function updatedNights(): void
    {
        $this->nights = max(1, min(QuotationService::MAX_NIGHTS, $this->nights));
        $this->resetMenuPlan(keepExisting: true);
    }

    public function updatedPax(): void
    {
        $this->pax = max(1, min(2000, (int) $this->pax));
    }

    public function toggleActivity(int $id): void
    {
        if (isset($this->activities[$id])) {
            unset($this->activities[$id]);

            return;
        }

        $activity = Activity::query()->find($id);
        if ($activity) {
            $this->activities[$id] = $activity->price_unit === 'pax' ? max($this->pax, $activity->min_pax) : 1;
        }
    }

    public function toggleItem(int $id): void
    {
        if (isset($this->items[$id])) {
            unset($this->items[$id]);

            return;
        }

        $item = AdditionalItem::query()->find($id);
        if ($item) {
            $this->items[$id] = $item->unit === 'pax' ? max($this->pax, $item->min_qty) : $item->min_qty;
        }
    }

    public function next(): void
    {
        $errors = $this->errorsForStep($this->step);

        if ($errors !== []) {
            $this->stepErrors = $errors;

            return;
        }

        $this->stepErrors = [];
        $this->step = min(4, $this->step + 1);
        $this->dispatch('langkah-berubah');
    }

    public function back(): void
    {
        $this->stepErrors = [];
        $this->step = max(1, $this->step - 1);
        $this->dispatch('langkah-berubah');
    }

    public function submit(QuotationService $quotations)
    {
        $this->phone = preg_replace('/[\s\-().]/', '', $this->phone);
        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'organization' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8\d{7,12}$/'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'agree' => ['accepted'],
        ], [
            'name.required' => 'Nama penanggung jawab wajib diisi.',
            'organization.required' => 'Nama instansi / perusahaan / sekolah wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi agar tim sales bisa menghubungi Anda.',
            'phone.regex' => 'Nomor WhatsApp sepertinya belum benar. Contoh: 0812 3456 7890.',
            'email.required' => 'Email wajib diisi. Proposal PDF dikirim ke email ini.',
            'email.email' => 'Format email belum benar. Contoh: nama@instansi.com.',
            'agree.accepted' => 'Mohon setujui kebijakan privasi untuk melanjutkan.',
        ]);

        if ($this->website !== '') {
            return null;
        }

        $key = 'quotation:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->stepErrors = ['Terlalu banyak percobaan. Silakan tunggu '.RateLimiter::availableIn($key).' detik.'];

            return null;
        }
        RateLimiter::hit($key, 60);

        try {
            $quotation = $quotations->create($this->input() + [
                'customer' => ['name' => trim($this->name), 'organization' => trim($this->organization), 'phone' => $this->phone, 'email' => trim($this->email)],
                'notes' => trim($this->notes) ?: null,
            ]);
        } catch (BookingException $e) {
            $this->stepErrors = [$e->getMessage()];

            return null;
        }

        session()->flash('proposal_baru', true);

        return $this->redirectRoute('proposal.show', $quotation);
    }

    #[Computed]
    public function estimate(): array
    {
        return app(QuotationService::class)->estimate($this->input());
    }

    #[Computed]
    public function variant(): ?GroupPackageVariant
    {
        return $this->variantId ? GroupPackageVariant::query()->with(['groupPackage', 'menuSet.options'])->find($this->variantId) : null;
    }

    private function input(?int $upToStep = null): array
    {
        return [
            'variant_id' => $this->variantId,
            'event_date' => $this->eventDate,
            'nights' => $this->nights,
            'pax' => $this->pax,
            'menu_plan' => $this->menuPlan,
            'activities' => $upToStep !== null && $upToStep < 3 ? [] : $this->activities,
            'items' => $upToStep !== null && $upToStep < 3 ? [] : $this->items,
        ];
    }

    private function errorsForStep(int $step): array
    {
        $estimate = app(QuotationService::class)->estimate($this->input($step));
        $errors = $estimate['errors'];

        // Langkah 1 tidak menilai menu (menu default selalu valid); menu dinilai di langkah 2
        if ($step === 1) {
            $errors = array_values(array_filter($errors, fn ($e) => ! str_starts_with($e, 'Pilih menu') && ! str_starts_with($e, 'Coffee break')));
        }

        return $errors;
    }

    private function resetMenuPlan(bool $keepExisting = false): void
    {
        $variant = $this->variantId ? GroupPackageVariant::query()->with('menuSet.options')->find($this->variantId) : null;

        if (! $variant) {
            $this->menuPlan = [];

            return;
        }

        $default = app(QuotationService::class)->defaultMenuPlan($variant, $this->nights);

        if ($keepExisting) {
            $existing = collect($this->menuPlan)->keyBy('day');
            $default = collect($default)->map(fn ($row) => $existing->get($row['day'], $row))->values()->all();
        }

        $this->menuPlan = $default;
        unset($this->estimate, $this->variant);
    }

    public function render(): View
    {
        return view('livewire.package-builder', [
            'steps' => ['Paket & peserta', 'Menu', 'Aktivitas & tambahan', 'Data instansi'],
            'packages' => GroupPackage::query()->where('is_active', true)->with(['variants' => fn ($q) => $q->where('is_active', true)->with('menuSet')])->orderBy('sort_order')->get(),
            'activityList' => Activity::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'itemList' => AdditionalItem::query()->where('is_active', true)->orderBy('sort_order')->get()->groupBy('category'),
        ]);
    }
}
