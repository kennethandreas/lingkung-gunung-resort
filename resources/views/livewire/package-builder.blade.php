<div x-data x-on:langkah-berubah.window="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })">
    @php
        $estimate = $this->estimate;
        $variant = $this->variant;
        $menuOptions = $variant ? app(\App\Services\QuotationService::class)->menuOptions($variant) : collect();
        $coffeeItems = $variant ? app(\App\Services\QuotationService::class)->coffeeItems($variant) : [];
        $coffeeChoices = (int) ($variant?->menuSet?->coffee_break_choices ?? 2);
        $meals = ['breakfast' => 'Sarapan', 'lunch' => 'Makan siang', 'dinner' => 'Makan malam'];
        $categories = \App\Models\AdditionalItem::CATEGORIES;
        $summaryLines = collect($estimate['lines'])->map(fn ($l) => ['label' => $l['description'].' × '.$l['qty'], 'detail' => null, 'amount' => $l['subtotal']])->all();
        $meta = $variant ? \Carbon\Carbon::parse($eventDate)->translatedFormat('d M Y').' · '.$nights.' malam · '.$pax.' peserta' : null;
    @endphp

    <div class="container-lg pb-32 pt-8 sm:pt-12 lg:pb-20">
        <p class="label-kecil text-emas-700">Acara &amp; Rombongan</p>
        <h1 class="font-display text-display-lg font-semibold text-hutan-900">Rancang paket rombongan</h1>
        <p class="mt-2 max-w-2xl text-kabut-600">Susun paket fullboard, menu, dan aktivitas. Estimasi harga langsung muncul, proposal PDF dikirim ke email Anda, dan tim sales kami menindaklanjuti via WhatsApp.</p>

        <x-ui.steps class="mt-8" :steps="$steps" :current="$step" />

        @if ($stepErrors)
            <x-ui.alert variant="bahaya" title="Mohon periksa kembali" class="mt-6">
                <ul class="list-disc space-y-0.5 pl-4">
                    @foreach ($stepErrors as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0">
                {{-- LANGKAH 1: PAKET & PESERTA --}}
                @if ($step === 1)
                    <div class="space-y-6">
                        <div class="grid gap-5 rounded-kartu bg-white p-5 shadow-lembut ring-1 ring-kabut-200 sm:grid-cols-3 sm:p-6">
                            <x-ui.field label="Tanggal kedatangan" for="eventDate" required>
                                <x-ui.input id="eventDate" type="date" wire:model.live="eventDate" :min="today()->toDateString()" icon="heroicon-o-calendar-days" />
                            </x-ui.field>
                            <x-ui.field label="Lama acara" hint="{{ $nights + 1 }} hari {{ $nights }} malam">
                                <x-ui.stepper wire:model.live="nights" :value="$nights" :min="1" :max="\App\Services\QuotationService::MAX_NIGHTS" label="Jumlah malam" />
                            </x-ui.field>
                            <x-ui.field label="Jumlah peserta" for="pax" required>
                                <x-ui.input id="pax" type="number" inputmode="numeric" min="1" wire:model.live.debounce.500ms="pax" icon="heroicon-o-user-group" />
                            </x-ui.field>
                        </div>

                        @foreach ($packages as $package)
                            <div>
                                <h2 class="font-display text-2xl font-semibold text-hutan-900">{{ $package->name }}</h2>
                                <p class="text-sm text-kabut-600">{{ $package->accommodation }}</p>
                                <div class="mt-3 grid gap-3 md:grid-cols-3">
                                    @foreach ($package->variants as $option)
                                        <button type="button" wire:click="selectVariant({{ $option->id }})" wire:key="v-{{ $option->id }}"
                                            @class([
                                                'flex h-full flex-col rounded-kartu bg-white p-5 text-left shadow-lembut ring-1 transition',
                                                'ring-2 ring-hutan-600' => $variantId === $option->id,
                                                'ring-kabut-200' => $variantId !== $option->id,
                                            ])
                                            aria-pressed="{{ $variantId === $option->id ? 'true' : 'false' }}">
                                            <span class="flex items-center justify-between gap-2">
                                                <span class="font-display text-xl font-semibold text-hutan-900">Varian {{ $option->code }}</span>
                                                @if ($variantId === $option->id)
                                                    <x-heroicon-s-check-circle class="size-6 text-hutan-600" aria-hidden="true" />
                                                @endif
                                            </span>
                                            <span class="mt-1 text-sm text-kabut-600">Min. {{ $option->min_pax }} peserta @if ($option->menuSet)· {{ $option->menuSet->name }}@endif</span>
                                            @if ($option->weekday_only)
                                                <span class="mt-2"><x-ui.badge variant="peringatan">Hanya weekday</x-ui.badge></span>
                                            @endif
                                            <span class="mt-3 grid grid-cols-2 gap-2">
                                                <span class="rounded-lg bg-kabut-50 px-2.5 py-1.5">
                                                    <span class="block text-[0.6875rem] text-kabut-600">Weekday</span>
                                                    <span class="block text-sm font-bold tabular-nums text-hutan-900">Rp{{ number_format($option->price_weekday, 0, ',', '.') }}</span>
                                                </span>
                                                <span class="rounded-lg bg-kabut-50 px-2.5 py-1.5">
                                                    <span class="block text-[0.6875rem] text-kabut-600">Weekend</span>
                                                    <span class="block text-sm font-bold tabular-nums text-hutan-900">{{ $option->weekday_only ? '-' : 'Rp'.number_format($option->price_weekend, 0, ',', '.') }}</span>
                                                </span>
                                            </span>
                                            <span class="mt-1 text-xs text-kabut-600">per peserta per malam</span>
                                            <span class="mt-3 text-xs leading-relaxed text-kabut-600">{{ implode(' · ', $option->includes ?? []) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- LANGKAH 2: MENU --}}
                @if ($step === 2 && $variant)
                    <div class="space-y-5">
                        <div>
                            <h2 class="font-display text-2xl font-semibold text-hutan-900">Pilih menu ({{ $variant->menuSet?->name }})</h2>
                            <p class="mt-1 text-sm text-kabut-600">Pilih opsi A–E untuk setiap waktu makan. Arahkan atau ketuk opsi untuk melihat isinya.</p>
                        </div>
                        @foreach ($menuPlan as $index => $day)
                            <div class="rounded-kartu bg-white p-5 shadow-lembut ring-1 ring-kabut-200 sm:p-6" wire:key="day-{{ $day['day'] }}">
                                <h3 class="font-display text-xl font-semibold text-hutan-900">Hari ke-{{ $day['day'] }}</h3>
                                <div class="mt-4 space-y-5">
                                    @foreach ($meals as $meal => $label)
                                        @if ($menuOptions->get($meal)?->isNotEmpty())
                                            <fieldset>
                                                <legend class="text-sm font-semibold text-kabut-800">{{ $label }}</legend>
                                                <div class="mt-2 grid gap-2 sm:grid-cols-5">
                                                    @foreach ($menuOptions->get($meal) as $option)
                                                        <label @class([
                                                            'cursor-pointer rounded-sm border p-3 text-xs transition',
                                                            'border-hutan-600 bg-hutan-50 ring-2 ring-hutan-600/15' => ($day[$meal] ?? null) === $option->option_code,
                                                            'border-kabut-200 hover:border-hutan-400' => ($day[$meal] ?? null) !== $option->option_code,
                                                        ])>
                                                            <input type="radio" class="sr-only" wire:model.live="menuPlan.{{ $index }}.{{ $meal }}" value="{{ $option->option_code }}">
                                                            <span class="block text-sm font-bold text-hutan-900">Opsi {{ $option->option_code }}</span>
                                                            <span class="mt-1 block capitalize leading-snug text-kabut-600">{{ implode(', ', array_slice($option->items, 0, 3)) }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </fieldset>
                                        @endif
                                    @endforeach

                                    @if ($variant->coffee_breaks > 0)
                                        @foreach (range(0, $variant->coffee_breaks - 1) as $cb)
                                            <fieldset>
                                                <legend class="text-sm font-semibold text-kabut-800">Coffee break {{ $cb + 1 }} <span class="font-normal text-kabut-600">(pilih {{ $coffeeChoices }} macam)</span></legend>
                                                @if ($coffeeItems)
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($coffeeItems as $snack)
                                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-none border border-kabut-200 px-3 py-1.5 text-sm capitalize has-checked:border-emas-500 has-checked:bg-emas-50">
                                                                <input type="checkbox" class="size-4 accent-hutan-700" wire:model.live="menuPlan.{{ $index }}.coffee_breaks.{{ $cb }}" value="{{ $snack }}">
                                                                {{ $snack }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="mt-1 text-sm text-kabut-600">Pilihan coffee break untuk set menu ini akan dikonfirmasi tim sales.</p>
                                                @endif
                                            </fieldset>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- LANGKAH 3: AKTIVITAS & TAMBAHAN --}}
                @if ($step === 3)
                    <div class="space-y-8">
                        <div>
                            <h2 class="font-display text-2xl font-semibold text-hutan-900">Aktivitas</h2>
                            <p class="mt-1 text-sm text-kabut-600">Opsional. Jumlah awal mengikuti jumlah peserta.</p>
                            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($activityList as $activity)
                                    <li wire:key="a-{{ $activity->id }}" @class(['rounded-kartu bg-white p-4 shadow-lembut ring-1 transition', 'ring-2 ring-hutan-600' => isset($activities[$activity->id]), 'ring-kabut-200' => ! isset($activities[$activity->id])])>
                                        <label class="flex cursor-pointer items-start gap-3">
                                            <input type="checkbox" class="mt-1 size-5 accent-hutan-700" @checked(isset($activities[$activity->id])) wire:click="toggleActivity({{ $activity->id }})">
                                            <span class="flex-1">
                                                <span class="block font-semibold text-hutan-900">{{ $activity->name }}</span>
                                                <span class="block text-xs text-kabut-600">{{ $activity->includes }}</span>
                                                <span class="mt-1 block text-sm text-kabut-600"><span class="font-bold text-hutan-900">Rp{{ number_format($activity->price, 0, ',', '.') }}</span> / {{ $activity->price_unit }} · min. {{ $activity->min_pax }} org</span>
                                            </span>
                                        </label>
                                        @if (isset($activities[$activity->id]))
                                            <div class="mt-3 flex items-center gap-2 pl-8 text-sm">
                                                <label for="aq-{{ $activity->id }}" class="text-kabut-600">Jumlah ({{ $activity->price_unit }})</label>
                                                <input id="aq-{{ $activity->id }}" type="number" min="1" class="h-10 w-24 rounded-lg border border-kabut-300 px-3" wire:model.live.debounce.500ms="activities.{{ $activity->id }}">
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h2 class="font-display text-2xl font-semibold text-hutan-900">Item tambahan</h2>
                            <p class="mt-1 text-sm text-kabut-600">Aula, blocking lapangan, perlengkapan, konsumsi, dan hiburan.</p>
                            <div class="mt-4 space-y-5">
                                @foreach ($itemList as $category => $categoryItems)
                                    <div>
                                        <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-emas-700">{{ $categories[$category] ?? $category }}</h3>
                                        <ul class="mt-2 divide-y divide-kabut-200 rounded-kartu bg-white shadow-lembut ring-1 ring-kabut-200">
                                            @foreach ($categoryItems as $item)
                                                <li class="flex flex-wrap items-center gap-3 p-4" wire:key="i-{{ $item->id }}">
                                                    <label class="flex flex-1 cursor-pointer items-start gap-3">
                                                        <input type="checkbox" class="mt-1 size-5 accent-hutan-700" @checked(isset($items[$item->id])) wire:click="toggleItem({{ $item->id }})">
                                                        <span>
                                                            <span class="block font-semibold text-hutan-900">{{ $item->name }}</span>
                                                            <span class="block text-xs text-kabut-600">{{ $item->unit_label }} · {{ $item->includes }}</span>
                                                        </span>
                                                    </label>
                                                    <span class="text-sm font-semibold tabular-nums text-hutan-900">Rp{{ number_format($item->price, 0, ',', '.') }}</span>
                                                    @if (isset($items[$item->id]))
                                                        <input type="number" min="1" aria-label="Jumlah {{ $item->name }}" class="h-10 w-20 rounded-lg border border-kabut-300 px-3 text-sm" wire:model.live.debounce.500ms="items.{{ $item->id }}">
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- LANGKAH 4: DATA INSTANSI --}}
                @if ($step === 4)
                    <form wire:submit="submit" class="space-y-6 rounded-kartu bg-white p-5 shadow-lembut ring-1 ring-kabut-200 sm:p-8" novalidate>
                        <h2 class="font-display text-2xl font-semibold text-hutan-900">Data instansi</h2>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.field label="Nama instansi / perusahaan / sekolah" for="organization" :error="$errors->first('organization')" required>
                                <x-ui.input id="organization" wire:model.blur="organization" autocomplete="organization" :invalid="$errors->has('organization')" />
                            </x-ui.field>
                            <x-ui.field label="Nama penanggung jawab" for="name" :error="$errors->first('name')" required>
                                <x-ui.input id="name" wire:model.blur="name" autocomplete="name" :invalid="$errors->has('name')" />
                            </x-ui.field>
                            <x-ui.field label="No. WhatsApp" for="phone" :error="$errors->first('phone')" required>
                                <x-ui.input id="phone" type="tel" inputmode="tel" wire:model.blur="phone" autocomplete="tel" placeholder="0812 3456 7890" icon="heroicon-o-phone" :invalid="$errors->has('phone')" />
                            </x-ui.field>
                            <x-ui.field label="Email" for="email" :error="$errors->first('email')" hint="Proposal PDF dikirim ke email ini." required>
                                <x-ui.input id="email" type="email" inputmode="email" wire:model.blur="email" autocomplete="email" icon="heroicon-o-envelope" :invalid="$errors->has('email')" />
                            </x-ui.field>
                            <x-ui.field label="Catatan / kebutuhan khusus (opsional)" for="notes" class="sm:col-span-2">
                                <textarea id="notes" wire:model.blur="notes" rows="3" class="block w-full rounded-sm border border-kabut-300 bg-white px-4 py-3 text-[0.9375rem] shadow-sm focus:border-hutan-600 focus:outline-none focus:ring-4 focus:ring-hutan-600/15" placeholder="mis. tema acara, kebutuhan sound system, jam kedatangan bus"></textarea>
                            </x-ui.field>
                        </div>
                        <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
                        <div>
                            <label class="flex items-start gap-3 text-sm text-kabut-700">
                                <input type="checkbox" wire:model="agree" class="mt-0.5 size-5 rounded accent-hutan-700">
                                <span>Saya menyetujui <a href="{{ route('halaman', 'kebijakan-privasi') }}" target="_blank" class="font-semibold text-hutan-700 underline underline-offset-2">kebijakan privasi</a> dan bersedia dihubungi tim sales.</span>
                            </label>
                            @error('agree')<p class="mt-1.5 text-sm text-bahaya-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="hidden justify-end lg:flex">
                            <x-ui.button type="submit" variant="primary" size="lg" icon="heroicon-m-document-text" wire:loading.attr="disabled" wire:target="submit">
                                <span wire:loading.remove wire:target="submit">Buat proposal</span>
                                <span wire:loading wire:target="submit">Menyusun proposal…</span>
                            </x-ui.button>
                        </div>
                    </form>
                @endif

                {{-- Navigasi desktop --}}
                <div class="mt-8 hidden items-center justify-between lg:flex">
                    @if ($step > 1)
                        <x-ui.button variant="ghost" wire:click="back" icon="heroicon-m-arrow-left">Kembali</x-ui.button>
                    @else
                        <span></span>
                    @endif
                    @if ($step < 4)
                        <x-ui.button variant="primary" size="lg" wire:click="next" icon-right="heroicon-m-arrow-right" :disabled="! $variantId">Lanjut</x-ui.button>
                    @endif
                </div>
            </div>

            {{-- Estimasi (desktop) --}}
            <div class="hidden lg:block">
                <x-ui.price-summary class="sticky top-24" title="Estimasi Biaya" :meta="$meta" :lines="$variant ? $summaryLines : []" :total="$estimate['total']" :dp="$variant ? $estimate['dp_amount'] : null">
                    @if ($variant && $estimate['errors'] && $step >= 3)
                        <ul class="mt-4 space-y-1 text-xs text-peringatan-800">
                            @foreach ($estimate['errors'] as $warning)
                                <li class="flex gap-1.5"><x-heroicon-m-exclamation-triangle class="size-4 shrink-0" aria-hidden="true" /> {{ $warning }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-4 text-xs text-kabut-600">Estimasi dihitung dari daftar harga resmi. Harga final dikonfirmasi tim sales.</p>
                </x-ui.price-summary>
            </div>
        </div>
    </div>

    {{-- Bar bawah HP --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-kabut-200 bg-white/95 px-4 pt-3 shadow-mengambang backdrop-blur-md lg:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
        <div class="flex items-center gap-2">
            @if ($step > 1)
                <button type="button" wire:click="back" class="flex size-11 shrink-0 items-center justify-center rounded-none text-hutan-800 ring-1 ring-kabut-300" aria-label="Kembali"><x-heroicon-m-arrow-left class="size-5" /></button>
            @endif
            <div class="min-w-0 flex-1 leading-tight">
                <span class="block text-xs text-kabut-600">Estimasi</span>
                <span class="text-lg font-bold tabular-nums text-hutan-900">Rp{{ number_format($estimate['total'], 0, ',', '.') }}</span>
            </div>
            @if ($step < 4)
                <x-ui.button variant="primary" wire:click="next" icon-right="heroicon-m-arrow-right" :disabled="! $variantId">Lanjut</x-ui.button>
            @else
                <x-ui.button variant="primary" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">Buat proposal</x-ui.button>
            @endif
        </div>
    </div>
</div>
