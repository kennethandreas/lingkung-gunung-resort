{{-- Kotak "Cek Ketersediaan": form GET biasa ke /pesan (tanpa JS tambahan). --}}
@props(['unit' => null, 'compact' => false])

@php
    $in = today()->addDays(7);
    // default ke Jumat terdekat minggu depan agar contoh harga weekend langsung terlihat
    $in = $in->next(\Carbon\Carbon::FRIDAY);
@endphp

<form action="{{ route('pesan') }}" method="get" id="cek-ketersediaan"
      x-data="{ checkIn: '{{ $in->toDateString() }}', checkOut: '{{ $in->copy()->addDays(2)->toDateString() }}' }"
      {{ $attributes->class(['grid gap-4 rounded-none border border-kabut-300 bg-white p-4 sm:grid-cols-2 lg:p-6', 'lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end lg:gap-6' => ! $compact]) }}>
    @if ($unit)
        <input type="hidden" name="unit" value="{{ $unit }}">
    @endif
    <x-ui.field label="Check-in" for="sb-in-{{ $unit ?? 'all' }}">
        <x-ui.input id="sb-in-{{ $unit ?? 'all' }}" type="date" name="check_in" x-model="checkIn" :min="today()->toDateString()" icon="heroicon-o-calendar-days" required
            x-on:change="if (checkOut <= checkIn) { const d = new Date(checkIn); d.setDate(d.getDate() + 1); checkOut = d.toISOString().slice(0, 10) }" />
    </x-ui.field>
    <x-ui.field label="Check-out" for="sb-out-{{ $unit ?? 'all' }}">
        <x-ui.input id="sb-out-{{ $unit ?? 'all' }}" type="date" name="check_out" x-model="checkOut" x-bind:min="checkIn" icon="heroicon-o-calendar-days" required />
    </x-ui.field>
    <x-ui.field label="Tamu">
        <div x-data="{ guests: 2 }" class="contents">
            <x-ui.stepper x-model="guests" :value="2" :max="200" label="Jumlah tamu" :class="\Illuminate\Support\Arr::toCssClasses(['w-full justify-between', 'sm:w-auto' => ! $compact])" />
            <input type="hidden" name="guests" x-bind:value="guests">
        </div>
    </x-ui.field>
    <x-ui.button type="submit" variant="primary" size="lg" icon="heroicon-m-magnifying-glass" :class="\Illuminate\Support\Arr::toCssClasses(['w-full', 'lg:w-auto' => ! $compact, 'sm:col-span-2' => $compact])">Cek Ketersediaan</x-ui.button>
</form>
