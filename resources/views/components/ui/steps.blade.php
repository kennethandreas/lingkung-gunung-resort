{{-- Indikator progres langkah booking. steps: ['Tanggal', 'Akomodasi', ...], current: indeks mulai 1 --}}
@props(['steps' => [], 'current' => 1])

<nav {{ $attributes->class('w-full') }} aria-label="Langkah pemesanan">
    {{-- HP: ringkas --}}
    <div class="sm:hidden">
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-hutan-800">Langkah {{ $current }} dari {{ count($steps) }}</span>
            <span class="text-kabut-600">{{ $steps[$current - 1] ?? '' }}</span>
        </div>
        <div class="mt-2 h-1.5 overflow-hidden rounded-none bg-kabut-200">
            <div class="h-full rounded-none bg-hutan-800 transition-all duration-700 ease-alam" style="width: {{ count($steps) ? round($current / count($steps) * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- Tablet/desktop: lengkap --}}
    <ol class="hidden items-center sm:flex">
        @foreach ($steps as $index => $step)
            @php($number = $index + 1)
            <li @class(['flex items-center', 'flex-1' => ! $loop->last]) @if ($number === $current) aria-current="step" @endif>
                <span class="flex items-center gap-2.5">
                    <span @class([
                        'flex size-9 items-center justify-center rounded-none text-sm font-bold transition duration-500',
                        'bg-hutan-800 text-white' => $number < $current,
                        'border-2 border-hutan-900 bg-white text-hutan-900' => $number === $current,
                        'bg-white text-kabut-600 ring-1 ring-kabut-300' => $number > $current,
                    ])>
                        @if ($number < $current)
                            <x-heroicon-m-check class="size-5" aria-hidden="true" />
                            <span class="sr-only">Selesai:</span>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span @class(['text-sm font-semibold', 'text-hutan-900' => $number <= $current, 'text-kabut-600' => $number > $current])>{{ $step }}</span>
                </span>
                @unless ($loop->last)
                    <span @class(['mx-4 h-px flex-1 transition-colors duration-500', 'bg-hutan-800' => $number < $current, 'bg-kabut-300' => $number >= $current]) aria-hidden="true"></span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
