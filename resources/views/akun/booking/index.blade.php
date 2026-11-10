<x-layouts.akun title="Booking Saya" lead="Semua pemesanan yang terhubung dengan akun Anda.">
    @php
        $sections = [
            ['key' => 'upcoming', 'title' => 'Akan datang', 'items' => $upcoming, 'empty' => 'Belum ada booking yang akan datang.'],
            ['key' => 'completed', 'title' => 'Selesai', 'items' => $completed, 'empty' => 'Belum ada menginap yang selesai.'],
            ['key' => 'cancelled', 'title' => 'Dibatalkan & kedaluwarsa', 'items' => $cancelled, 'empty' => 'Tidak ada booking yang dibatalkan.'],
        ];
        $total = $upcoming->count() + $completed->count() + $cancelled->count();
    @endphp

    @if ($total === 0)
        <x-akun.empty-state icon="heroicon-o-calendar-days" title="Belum ada booking" text="Booking yang Anda buat di website otomatis tersimpan di sini. Pernah memesan lewat telepon/WhatsApp atau sebelum punya akun? Tambahkan lewat formulir di bawah.">
            <x-ui.button variant="primary" :href="route('pesan')">Pesan sekarang</x-ui.button>
        </x-akun.empty-state>
    @else
        <div x-data="{ tab: 'upcoming' }">
            <div class="flex overflow-x-auto border-b border-kabut-300/70" role="tablist" aria-label="Kelompok booking">
                @foreach ($sections as $section)
                    <button type="button" role="tab" id="tab-{{ $section['key'] }}" aria-controls="panel-{{ $section['key'] }}"
                            x-on:click="tab = '{{ $section['key'] }}'"
                            x-bind:aria-selected="(tab === '{{ $section['key'] }}').toString()"
                            x-bind:class="tab === '{{ $section['key'] }}' ? 'border-hutan-900 text-hutan-950' : 'border-transparent text-kabut-600 hover:text-hutan-900'"
                            class="-mb-px shrink-0 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition-colors">
                        {{ $section['title'] }} <span class="ml-1 tabular-nums opacity-80">({{ $section['items']->count() }})</span>
                    </button>
                @endforeach
            </div>

            @foreach ($sections as $section)
                {{-- Satu kelompok per tab (Alpine) --}}
                <section id="panel-{{ $section['key'] }}" role="tabpanel" aria-labelledby="tab-{{ $section['key'] }}" class="mt-6"
                         x-show="tab === '{{ $section['key'] }}'" @if (! $loop->first) x-cloak @endif>
                    <h2 class="sr-only">{{ $section['title'] }}</h2>
                    @forelse ($section['items'] as $booking)
                        <x-akun.booking-row :booking="$booking" class="mb-3" />
                    @empty
                        <p class="border border-kabut-300 bg-white p-5 text-sm text-kabut-700">{{ $section['empty'] }}</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    @endif

    {{-- Tautkan booking lama --}}
    <div id="tautkan" class="mt-12 scroll-mt-24 border-t border-kabut-300/70 pt-10">
        <h2 class="font-display text-2xl text-hutan-950">Tambahkan booking lama</h2>
        <p class="mt-1 text-sm text-kabut-700">Pernah memesan lewat telepon/WhatsApp atau sebelum punya akun? Masukkan kode booking dan nomor WhatsApp yang Anda pakai saat memesan. Keduanya harus cocok agar booking bisa ditambahkan.</p>

        <form method="POST" action="{{ route('akun.booking.tautkan') }}" class="mt-5 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-start" novalidate>
            @csrf
            <x-ui.field label="Kode booking" for="code" :error="$errors->claim->first('code')">
                <x-ui.input id="code" name="code" :value="old('code')" placeholder="LGAB12CD34" class="uppercase" autocomplete="off" :invalid="$errors->claim->has('code')" :aria-describedby="$errors->claim->has('code') ? 'code-error' : null" />
            </x-ui.field>
            <x-ui.field label="Nomor WhatsApp saat memesan" for="phone" :error="$errors->claim->first('phone')">
                <x-ui.input id="phone" name="phone" type="tel" :value="old('phone')" placeholder="0812 3456 7890" autocomplete="tel" :invalid="$errors->claim->has('phone')" :aria-describedby="$errors->claim->has('phone') ? 'phone-error' : null" />
            </x-ui.field>
            <x-ui.button type="submit" variant="primary" class="sm:mt-7">Tambahkan</x-ui.button>
        </form>
    </div>
</x-layouts.akun>
