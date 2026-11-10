{{-- Pembungkus field form: label, petunjuk, dan pesan error yang ramah. --}}
@props(['label' => null, 'for' => null, 'hint' => null, 'error' => null, 'required' => false])

<div {{ $attributes->class('space-y-1.5') }}>
    @if ($label)
        <label for="{{ $for }}" class="block text-sm font-semibold text-kabut-800">
            {{ $label }}
            @if ($required)<span class="text-bahaya-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        <p id="{{ $for }}-error" class="flex items-start gap-1.5 text-sm text-bahaya-600" role="alert">
            <x-heroicon-m-exclamation-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            {{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $for }}-hint" class="text-sm text-kabut-600">{{ $hint }}</p>
    @endif
</div>
