{{--
    Modal/dialog yang aksesibel: fokus terkunci di dalam, Esc menutup, klik latar menutup.
    Buka dengan: x-on:click="$dispatch('buka-modal', 'nama')"
    Di HP tampil sebagai lembar dari bawah (bottom sheet).
--}}
@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg'])

<div
    x-data="{ open: false }"
    x-on:buka-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:tutup-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="modal-{{ $name }}-title" @endif
>
    <div x-show="open" x-transition.opacity.duration.300ms class="absolute inset-0 bg-hutan-950/60 backdrop-blur-sm" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-trap.noscroll.inert="open"
        x-transition:enter="transition duration-400 ease-alam"
        x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-6"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0 sm:translate-y-6"
        {{ $attributes->class('relative w-full '.$maxWidth.' max-h-[90dvh] overflow-y-auto rounded-t-sm bg-white p-6 shadow-angkat sm:rounded-sm sm:p-8') }}
    >
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-none bg-kabut-200 sm:hidden" aria-hidden="true"></div>
        <div class="flex items-start justify-between gap-4">
            @if ($title)
                <h2 id="modal-{{ $name }}-title" class="font-display text-2xl font-semibold text-hutan-900">{{ $title }}</h2>
            @endif
            <button type="button" class="-mr-2 -mt-1 ml-auto flex size-10 items-center justify-center rounded-none text-kabut-600 transition hover:bg-kabut-100 hover:text-kabut-800" x-on:click="open = false" aria-label="Tutup">
                <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
            </button>
        </div>
        <div class="mt-4">{{ $slot }}</div>
        @isset($footer)
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">{{ $footer }}</div>
        @endisset
    </div>
</div>
