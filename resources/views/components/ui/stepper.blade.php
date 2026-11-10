{{--
    Penghitung +/- (jumlah tamu, jumlah unit). Ramah jari di HP (tombol 44px).
    Pakai x-model / wire:model pada komponen ini, mis. <x-ui.stepper wire:model.live="guests" />
--}}
@props(['min' => 1, 'max' => 99, 'label' => 'Jumlah', 'value' => 1])

<div
    x-data="{ value: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }} }"
    x-modelable="value"
    {{ $attributes->whereStartsWith(['x-model', 'wire:model']) }}
    {{ $attributes->whereDoesntStartWith(['x-model', 'wire:model'])->class('inline-flex h-12 items-center rounded-sm border border-kabut-300 bg-white shadow-sm') }}
    role="group"
    aria-label="{{ $label }}"
>
    <button type="button" class="flex size-11 items-center justify-center rounded-l-sm text-hutan-800 transition hover:bg-hutan-50 disabled:text-kabut-300"
        x-on:click="value = Math.max(min, value - 1)" x-bind:disabled="value <= min" aria-label="Kurangi {{ strtolower($label) }}">
        <x-heroicon-m-minus class="size-5" aria-hidden="true" />
    </button>
    <output class="w-12 text-center text-base font-semibold tabular-nums text-kabut-900" x-text="value" aria-live="polite">{{ (int) $value }}</output>
    <button type="button" class="flex size-11 items-center justify-center rounded-r-sm text-hutan-800 transition hover:bg-hutan-50 disabled:text-kabut-300"
        x-on:click="value = Math.min(max, value + 1)" x-bind:disabled="value >= max" aria-label="Tambah {{ strtolower($label) }}">
        <x-heroicon-m-plus class="size-5" aria-hidden="true" />
    </button>
</div>
