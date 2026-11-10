{{--
    Wizard pemesanan (tahap ux-booking-akun, docs/AUDIT-UX.md bagian 3).
    Binding Livewire tidak berubah; harga & stok tetap dihitung server (BookingWizard + PricingService).
--}}
<div x-data
     x-on:langkah-berubah.window="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
     x-init="if (window.Livewire?.hook) Livewire.hook('commit', ({ component, succeed }) => { if (component.id !== $wire.$id) return; succeed(() => $nextTick(() => { const bad = $el.querySelector('[aria-invalid=true]'); if (bad) { bad.focus({ preventScroll: true }); bad.scrollIntoView({ block: 'center' }) } })) })">
    @php
        $quote = $this->quote;
        $hasQuote = $quote && ! isset($quote['error']);
        $summaryLines = $hasQuote
            ? collect($quote['items'])->map(fn ($item) => [
                'label' => $item['unit_type']->marketing_name.' × '.$item['qty'],
                'detail' => collect($item['nights'])->map(fn ($n) => \Carbon\Carbon::parse($n['date'])->translatedFormat('D d/m').' '.($n['day_type'] === 'weekday' ? 'weekday' : 'weekend'))->implode(' · '),
                'amount' => $item['subtotal'],
            ])->all()
            : [];
        $nights = $this->nights();
        $meta = $nights
            ? \Carbon\Carbon::parse($checkIn)->translatedFormat('D, d M').' – '.\Carbon\Carbon::parse($checkOut)->translatedFormat('D, d M Y').' · '.$nights.' malam · '.$guests.' tamu'
            : null;
        $payNow = $hasQuote ? ($paymentOption === 'dp' && $this->dpAllowed ? $quote['dp_amount'] : $quote['total']) : null;
        $weekendLabel = app(\App\Services\PricingService::class)->weekendLabel();
        $isGuest = ! auth('customer')->check();
    @endphp

    <div class="container-lg pb-36 pt-8 sm:pt-12 lg:pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-kabut-300/70 pb-6">
            <div>
                <p class="label-kecil text-emas-700">Pemesanan langsung</p>
                <h1 class="mt-2 font-display text-display-lg text-hutan-950">Pesan penginapan</h1>
            </div>
            <p class="max-w-sm text-sm text-kabut-700">Harga langsung dari resort, tanpa komisi pihak ketiga. Butuh bantuan? <a href="{{ \App\Support\WhatsApp::link('Halo, saya butuh bantuan memesan penginapan.') }}" target="_blank" rel="noopener" class="font-semibold text-hutan-800 underline underline-offset-4">WhatsApp kami</a>.</p>
        </div>

        <x-ui.steps class="mt-6" :steps="$steps" :current="$step" />

        {{-- Satu pemberitahuan akun, sejak langkah pertama (bukan kejutan di langkah 3) --}}
        @if ($step < 3 && $isGuest)
            <div class="mt-6 flex flex-col gap-3 border-l-2 border-hutan-700 bg-hutan-50 px-5 py-4 text-sm text-hutan-900 sm:flex-row sm:items-center sm:justify-between">
                <p><strong>Pilih tanggal dan akomodasi tanpa akun.</strong> Untuk mengisi data dan membayar, Anda akan diminta masuk atau daftar (gratis, sekitar 1 menit). Pilihan Anda tidak hilang.</p>
                <a href="{{ route('akun.masuk') }}" class="tautan-panah shrink-0 text-hutan-900">Sudah punya akun? Masuk</a>
            </div>
        @endif

        @if ($errorMessage)
            <div wire:key="err-{{ md5($errorMessage) }}" x-data x-init="$el.scrollIntoView({ block: 'center', behavior: 'auto' })">
                <x-ui.alert variant="bahaya" class="mt-6" title="Belum bisa dilanjutkan">{{ $errorMessage }}</x-ui.alert>
            </div>
        @endif

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                {{-- ===================== LANGKAH 1: TANGGAL & TAMU ===================== --}}
                @if ($step === 1)
                    <form wire:submit="toAccommodation" class="border border-kabut-300 bg-white p-5 sm:p-8">
                        <h2 class="font-display text-2xl text-hutan-950">Kapan Anda datang?</h2>
                        <p class="mt-1 text-sm text-kabut-600">Maksimal 30 malam per pemesanan. Tarif weekend berlaku untuk {{ $weekendLabel }} dan hari libur yang ditandai resort.</p>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <x-ui.field label="Check-in" for="checkIn" :error="$errors->first('checkIn')" required>
                                <x-ui.input id="checkIn" type="date" wire:model="checkIn" :min="today()->toDateString()" icon="heroicon-o-calendar-days" :invalid="$errors->has('checkIn')" />
                            </x-ui.field>
                            <x-ui.field label="Check-out" for="checkOut" :error="$errors->first('checkOut')" required>
                                <x-ui.input id="checkOut" type="date" wire:model="checkOut" :min="today()->addDay()->toDateString()" icon="heroicon-o-calendar-days" :invalid="$errors->has('checkOut')" />
                            </x-ui.field>
                            <x-ui.field label="Jumlah tamu" :error="$errors->first('guests')" hint="Termasuk anak-anak. Untuk rombongan besar, lihat paket rombongan.">
                                <x-ui.stepper wire:model="guests" :value="$guests" :min="1" :max="200" label="Jumlah tamu" />
                            </x-ui.field>
                        </div>
                        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-kabut-200 pt-6">
                            <a href="{{ route('gather') }}" class="text-sm text-kabut-700 underline underline-offset-4 hover:text-hutan-800">Memesan untuk rombongan 30+ orang?</a>
                            <x-ui.button type="submit" variant="primary" size="lg" icon-right="heroicon-m-arrow-right" wire:loading.attr="disabled" wire:target="toAccommodation">
                                <span wire:loading.remove wire:target="toAccommodation">Lihat akomodasi tersedia</span>
                                <span wire:loading wire:target="toAccommodation">Memeriksa ketersediaan…</span>
                            </x-ui.button>
                        </div>
                    </form>
                @endif

                {{-- ===================== LANGKAH 2: AKOMODASI ===================== --}}
                @if ($step === 2)
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="font-display text-2xl text-hutan-950">Pilih akomodasi</h2>
                            <p class="mt-1 text-sm text-kabut-700">{{ $meta }}</p>
                        </div>
                        <button type="button" wire:click="back" class="tautan-panah text-hutan-900"><x-heroicon-m-pencil-square class="size-3.5" aria-hidden="true" /> Ubah tanggal / tamu</button>
                    </div>

                    <ul class="mt-6 divide-y divide-kabut-300/70 border-y border-kabut-300/70" wire:loading.class="opacity-60" wire:target="changeQty">
                        @forelse ($this->availability as $row)
                            @php
                                $type = $row['unit_type'];
                                $available = $row['available'];
                                $selected = (int) ($qty[$type->id] ?? 0);
                                $unitWord = $type->isPricedPerPerson() ? 'orang' : ($type->pricing_unit->value === 'tent' ? 'tenda' : 'kamar');
                            @endphp
                            <li wire:key="unit-{{ $type->id }}" @class([
                                'relative grid gap-4 py-5 sm:grid-cols-[12rem_minmax(0,1fr)_auto] sm:items-center sm:gap-6',
                                'opacity-60' => $available === 0,
                            ])>
                                @if ($selected > 0)
                                    <span class="absolute inset-y-0 -left-4 w-0.5 bg-hutan-800 sm:-left-5" aria-hidden="true"></span>
                                @endif
                                <x-ui.picture :media="$type->getFirstMedia('photos')" :alt="$type->marketing_name" ratio="aspect-[3/2]" sizes="(min-width: 640px) 192px, 100vw" />
                                <div class="min-w-0">
                                    <h3 class="font-display text-xl text-hutan-950">{{ $type->marketing_name }}</h3>
                                    <p class="mt-1 text-sm text-kabut-700">
                                        {{ $type->isPricedPerPerson() ? 'Harga per orang' : 'Maks. '.$type->capacity_pax.' orang per '.$unitWord.($type->bed_configuration ? ' · '.$type->bed_configuration : '') }}
                                        @if ($type->breakfast_pax) · sarapan {{ $type->breakfast_pax }} orang @endif
                                    </p>
                                    <p class="mt-2 text-sm font-semibold {{ $available === 0 ? 'text-bahaya-700' : ($available <= 3 ? 'text-peringatan-800' : 'text-sukses-800') }}">
                                        {{ $available === 0 ? 'Penuh di tanggal ini' : ($available <= 3 ? 'Tersisa '.$available.' '.$unitWord : 'Tersedia') }}
                                    </p>
                                    <a href="{{ route('akomodasi.show', $type) }}" target="_blank" class="mt-1 inline-block text-xs text-kabut-600 underline underline-offset-4 hover:text-hutan-800">Lihat foto & fasilitas</a>
                                </div>
                                <div class="flex items-end justify-between gap-4 sm:flex-col sm:items-end">
                                    <p class="leading-tight sm:text-right">
                                        @if ($row['stay'])
                                            <span class="block text-lg font-semibold tabular-nums text-hutan-950">Rp{{ number_format($row['stay']['per_unit'], 0, ',', '.') }}</span>
                                            <span class="text-xs text-kabut-600">{{ $nights }} malam · per {{ $unitWord }}</span>
                                        @else
                                            <span class="text-sm text-kabut-600">Hubungi kami untuk harga</span>
                                        @endif
                                    </p>
                                    @if ($available > 0 && $row['stay'])
                                        <div class="inline-flex h-11 items-center border border-kabut-400 bg-white" role="group" aria-label="Jumlah {{ $type->marketing_name }}">
                                            <button type="button" class="flex size-11 items-center justify-center text-hutan-900 hover:bg-kabut-100 disabled:text-kabut-300" wire:click="changeQty({{ $type->id }}, -1)" @disabled($selected === 0) aria-label="Kurangi {{ $type->marketing_name }}">
                                                <x-heroicon-m-minus class="size-5" aria-hidden="true" />
                                            </button>
                                            <output class="w-10 text-center font-semibold tabular-nums" aria-live="polite">{{ $selected }}</output>
                                            <button type="button" class="flex size-11 items-center justify-center text-hutan-900 hover:bg-kabut-100 disabled:text-kabut-300" wire:click="changeQty({{ $type->id }}, 1)" @disabled($selected >= $available) aria-label="Tambah {{ $type->marketing_name }}">
                                                <x-heroicon-m-plus class="size-5" aria-hidden="true" />
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="py-8"><x-ui.alert variant="info" title="Belum ada akomodasi yang bisa dipesan online untuk tanggal ini">Coba tanggal lain, atau tanyakan ketersediaan lewat WhatsApp.</x-ui.alert></li>
                        @endforelse
                    </ul>

                    <p class="mt-4 text-sm text-kabut-700">
                        Camp site, campervan, dan rombongan dipesan melalui
                        <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin memesan untuk rombongan/camp site.') }}" class="font-semibold text-hutan-800 underline underline-offset-4" target="_blank" rel="noopener">tim kami</a>.
                    </p>

                    <div class="mt-8 hidden items-center justify-between gap-4 lg:flex">
                        <button type="button" wire:click="back" class="tautan-panah text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Kembali</button>
                        <x-ui.button variant="primary" size="lg" wire:click="toCustomer" wire:loading.attr="disabled" wire:target="toCustomer" icon-right="heroicon-m-arrow-right">
                            <span wire:loading.remove wire:target="toCustomer">{{ $isGuest ? 'Lanjut: masuk & isi data' : 'Lanjut isi data' }}</span>
                            <span wire:loading wire:target="toCustomer">Memeriksa…</span>
                        </x-ui.button>
                    </div>
                @endif

                {{-- ===================== LANGKAH 3: DATA PEMESAN & TINJAU ===================== --}}
                @if ($step === 3)
                    <form wire:submit="submit" class="space-y-10" novalidate>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="font-display text-2xl text-hutan-950">Data pemesan</h2>
                            <button type="button" wire:click="back" class="tautan-panah text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Ubah akomodasi</button>
                        </div>

                        <section class="border border-kabut-300 bg-white p-5 sm:p-8" aria-labelledby="judul-data">
                            <h3 id="judul-data" class="label-kecil text-kabut-700">1 · Kontak</h3>
                            @auth('customer')
                                <p class="mt-3 text-sm text-kabut-700">Memesan sebagai <strong class="text-kabut-900">{{ auth('customer')->user()->email }}</strong>. Booking ini tersimpan di akun Anda.</p>
                            @endauth
                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <x-ui.field label="Nama lengkap" for="name" :error="$errors->first('name')" required>
                                    <x-ui.input id="name" wire:model.blur="name" autocomplete="name" placeholder="mis. Siti Aminah" :invalid="$errors->has('name')" />
                                </x-ui.field>
                                <x-ui.field label="No. WhatsApp" for="phone" :error="$errors->first('phone')" hint="Untuk konfirmasi & info kedatangan." required>
                                    <x-ui.input id="phone" type="tel" inputmode="tel" wire:model.blur="phone" autocomplete="tel" placeholder="0812 3456 7890" icon="heroicon-o-phone" :invalid="$errors->has('phone')" />
                                </x-ui.field>
                                <x-ui.field label="Email" for="email" :error="$errors->first('email')" hint="E-ticket dan bukti bayar dikirim ke email ini." required>
                                    <x-ui.input id="email" type="email" inputmode="email" wire:model.blur="email" autocomplete="email" placeholder="nama@email.com" icon="heroicon-o-envelope" :invalid="$errors->has('email')" />
                                </x-ui.field>
                                <x-ui.field label="Instansi (opsional)" for="organization" :error="$errors->first('organization')">
                                    <x-ui.input id="organization" wire:model.blur="organization" autocomplete="organization" placeholder="Perusahaan / sekolah / komunitas" />
                                </x-ui.field>
                                <x-ui.field label="Permintaan khusus (opsional)" for="notes" class="sm:col-span-2" :error="$errors->first('notes')" hint="Bukan jaminan, tim kami akan mengusahakan.">
                                    <textarea id="notes" wire:model.blur="notes" rows="2" class="block w-full rounded-sm border border-kabut-300 bg-white px-4 py-3 text-[0.9375rem] placeholder:text-kabut-400 focus:border-hutan-600 focus:outline-none focus:ring-4 focus:ring-hutan-600/15" placeholder="mis. perkiraan jam tiba, kebutuhan khusus"></textarea>
                                </x-ui.field>
                            </div>
                        </section>

                        <section class="border border-kabut-300 bg-white p-5 sm:p-8" aria-labelledby="judul-bayar">
                            <h3 id="judul-bayar" class="label-kecil text-kabut-700">2 · Cara bayar</h3>
                            <fieldset class="mt-5 grid gap-3 sm:grid-cols-2">
                                <legend class="sr-only">Pilih cara bayar</legend>
                                @if ($this->dpAllowed)
                                    <label @class(['flex cursor-pointer items-start gap-3 border p-4 transition-colors', 'border-hutan-800 bg-hutan-50/60' => $paymentOption === 'dp', 'border-kabut-300 hover:border-hutan-700' => $paymentOption !== 'dp'])>
                                        <input type="radio" wire:model.live="paymentOption" value="dp" class="mt-1 size-4 accent-hutan-800">
                                        <span>
                                            <span class="block font-semibold text-hutan-950">DP {{ $dpPercent }}% sekarang</span>
                                            @if ($hasQuote)<span class="block text-lg tabular-nums text-kabut-900">Rp{{ number_format($quote['dp_amount'], 0, ',', '.') }}</span>@endif
                                            <span class="mt-1 block text-sm text-kabut-600">Sisanya dilunasi paling lambat H-{{ $settlementDays }} sebelum check-in. Kami kirim pengingat via email.</span>
                                        </span>
                                    </label>
                                @endif
                                <label @class(['flex cursor-pointer items-start gap-3 border p-4 transition-colors', 'border-hutan-800 bg-hutan-50/60' => $paymentOption === 'full' || ! $this->dpAllowed, 'border-kabut-300 hover:border-hutan-700' => $paymentOption !== 'full' && $this->dpAllowed])>
                                    <input type="radio" wire:model.live="paymentOption" value="full" class="mt-1 size-4 accent-hutan-800" @checked(! $this->dpAllowed)>
                                    <span>
                                        <span class="block font-semibold text-hutan-950">Bayar lunas</span>
                                        @if ($hasQuote)<span class="block text-lg tabular-nums text-kabut-900">Rp{{ number_format($quote['total'], 0, ',', '.') }}</span>@endif
                                        <span class="mt-1 block text-sm text-kabut-600">Sekali bayar, tanpa tagihan pelunasan.</span>
                                    </span>
                                </label>
                            </fieldset>
                            @unless ($this->dpAllowed)
                                <p class="mt-3 text-sm text-peringatan-800">Kedatangan kurang dari {{ $settlementDays }} hari lagi, jadi pembayaran harus langsung lunas.</p>
                            @endunless

                            <x-ui.field label="Kode promo (opsional)" for="promoCode" class="mt-6 max-w-xs" :error="$quote['promo_error'] ?? null">
                                <x-ui.input id="promoCode" wire:model.live.debounce.600ms="promoCode" class="uppercase" placeholder="Masukkan kode" icon="heroicon-o-ticket" :invalid="isset($quote['promo_error'])" />
                            </x-ui.field>
                            @if ($hasQuote && $quote['discount'] > 0)
                                <p class="mt-2 text-sm text-sukses-800">Kode promo dipakai: hemat Rp{{ number_format($quote['discount'], 0, ',', '.') }}.</p>
                            @endif
                        </section>

                        {{-- Tinjau sebelum mengirim --}}
                        <section class="border-2 border-hutan-900 bg-white p-5 sm:p-8" aria-labelledby="judul-tinjau">
                            <h3 id="judul-tinjau" class="label-kecil text-kabut-700">3 · Periksa pesanan Anda</h3>
                            <dl class="mt-5 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                                <div><dt class="text-kabut-600">Tanggal</dt><dd class="font-semibold text-kabut-900">{{ $meta }}</dd></div>
                                <div><dt class="text-kabut-600">Akomodasi</dt><dd class="font-semibold text-kabut-900">{{ collect($summaryLines)->pluck('label')->implode(', ') ?: '-' }}</dd></div>
                                <div><dt class="text-kabut-600">Total</dt><dd class="font-semibold tabular-nums text-kabut-900">{{ $hasQuote ? 'Rp'.number_format($quote['total'], 0, ',', '.') : '-' }}</dd></div>
                                <div><dt class="text-kabut-600">Dibayar sekarang</dt><dd class="text-lg font-semibold tabular-nums text-hutan-950">{{ $payNow !== null ? 'Rp'.number_format($payNow, 0, ',', '.') : '-' }}</dd></div>
                            </dl>
                            <ul class="mt-6 space-y-2 border-t border-kabut-200 pt-5 text-sm text-kabut-700">
                                <li class="flex gap-2"><x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-hutan-700" aria-hidden="true" /> Setelah menekan tombol di bawah, unit <strong class="text-kabut-900">kami tahan {{ $holdMinutes }} menit</strong> sambil Anda membayar. Bila tidak dibayar, pesanan batal otomatis.</li>
                                <li class="flex gap-2"><x-heroicon-o-lock-closed class="mt-0.5 size-4 shrink-0 text-hutan-700" aria-hidden="true" /> Pembayaran diproses Midtrans (QRIS, virtual account, e-wallet). Harga final dihitung ulang oleh sistem.</li>
                                <li class="flex gap-2"><x-heroicon-o-qr-code class="mt-0.5 size-4 shrink-0 text-hutan-700" aria-hidden="true" /> E-ticket terbit setelah pembayaran pertama terverifikasi.</li>
                            </ul>

                            {{-- Honeypot: disembunyikan dari manusia --}}
                            <div class="hidden" aria-hidden="true">
                                <label for="website">Website</label>
                                <input id="website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mt-6">
                                <label class="flex items-start gap-3 text-sm text-kabut-800">
                                    <input type="checkbox" wire:model="agree" class="mt-0.5 size-5 accent-hutan-800" aria-invalid="{{ $errors->has('agree') ? 'true' : 'false' }}" aria-describedby="agree-error">
                                    <span>Saya menyetujui <a href="{{ route('halaman', 'syarat-dan-ketentuan') }}" target="_blank" class="font-semibold text-hutan-800 underline underline-offset-4">syarat &amp; ketentuan</a> dan <a href="{{ route('halaman', 'kebijakan-privasi') }}" target="_blank" class="font-semibold text-hutan-800 underline underline-offset-4">kebijakan privasi</a>.</span>
                                </label>
                                @error('agree')<p id="agree-error" class="mt-1.5 text-sm text-bahaya-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            <div class="mt-8 hidden lg:block">
                                <x-ui.button type="submit" variant="primary" size="lg" icon="heroicon-m-lock-closed" class="w-full" wire:loading.attr="disabled" wire:target="submit">
                                    <span wire:loading.remove wire:target="submit">Buat pesanan &amp; lanjut ke pembayaran</span>
                                    <span wire:loading wire:target="submit">Menahan unit untuk Anda…</span>
                                </x-ui.button>
                            </div>
                        </section>
                    </form>
                @endif
            </div>

            {{-- RINGKASAN HARGA (desktop: menempel di samping) --}}
            @if ($step > 1)
                <div class="hidden lg:block">
                    <x-ui.price-summary class="sticky top-24" :meta="$meta" :lines="$summaryLines" :discount="$hasQuote ? $quote['discount'] : 0" :total="$hasQuote ? $quote['total'] : 0" :dp="$hasQuote && $paymentOption === 'dp' && $this->dpAllowed ? $quote['dp_amount'] : null" :dp-percent="$dpPercent">
                        <p class="mt-4 text-xs text-kabut-600">Rincian per malam: weekday/weekend mengikuti {{ $weekendLabel }}.</p>
                    </x-ui.price-summary>
                </div>
            @endif
        </div>
    </div>

    {{-- BAR BAWAH HP/TABLET: rincian bisa dibuka + total + tombol lanjut selalu terlihat --}}
    @if ($step > 1)
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-kabut-300 bg-kabut-50 lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)" x-data="{ rincian: false }">
            @if ($hasQuote)
                <div x-show="rincian" x-collapse x-cloak class="max-h-[50vh] overflow-y-auto border-b border-kabut-300 bg-white px-4 py-4">
                    <x-ui.price-summary class="!border-0 !p-0" :meta="$meta" :lines="$summaryLines" :discount="$quote['discount']" :total="$quote['total']" :dp="$paymentOption === 'dp' && $this->dpAllowed ? $quote['dp_amount'] : null" :dp-percent="$dpPercent" />
                </div>
            @endif
            <div class="flex items-center gap-3 px-4 py-3">
                <button type="button" class="min-w-0 flex-1 text-left leading-tight" x-on:click="rincian = ! rincian" @disabled(! $hasQuote) x-bind:aria-expanded="rincian">
                    @if ($hasQuote)
                        <span class="flex items-center gap-1 text-xs text-kabut-700">{{ $step === 3 && $payNow !== $quote['total'] ? 'Bayar sekarang (DP)' : 'Total '.$nights.' malam' }} <x-heroicon-m-chevron-up class="size-3.5 transition" x-bind:class="rincian || 'rotate-180'" aria-hidden="true" /></span>
                        <span class="text-lg font-semibold tabular-nums text-hutan-950">Rp{{ number_format($step === 3 ? $payNow : $quote['total'], 0, ',', '.') }}</span>
                        <span class="sr-only">Lihat rincian harga</span>
                    @else
                        <span class="text-sm text-kabut-700">Pilih akomodasi dulu</span>
                    @endif
                </button>
                @if ($step === 2)
                    <x-ui.button variant="primary" wire:click="toCustomer" wire:loading.attr="disabled" wire:target="toCustomer" icon-right="heroicon-m-arrow-right">Lanjut</x-ui.button>
                @else
                    <x-ui.button variant="primary" wire:click="submit" wire:loading.attr="disabled" wire:target="submit" icon="heroicon-m-lock-closed">
                        <span wire:loading.remove wire:target="submit">Lanjut bayar</span>
                        <span wire:loading wire:target="submit">Memproses…</span>
                    </x-ui.button>
                @endif
            </div>
        </div>
    @endif
</div>
