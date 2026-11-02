{{-- Ringkasan rating: rata-rata besar, jumlah ulasan, sebaran bintang 5→1 (sebaran hanya bila ≥ 3 ulasan). --}}
@props(['summary'])

<div {{ $attributes->class('border border-kabut-300 bg-white p-6') }}>
    @if ($summary['count'] > 0)
        <div class="flex items-end gap-4">
            <p class="font-display text-5xl leading-none text-hutan-950">{{ str_replace('.', ',', number_format($summary['average'], 1)) }}<span class="text-xl text-kabut-500">/5</span></p>
            <div class="pb-1">
                <x-ulasan.bintang :value="$summary['average']" />
                <p class="mt-1 text-sm text-kabut-700">{{ $summary['count'] }} ulasan</p>
            </div>
        </div>

        @if ($summary['count'] >= 3)
            <dl class="mt-6 space-y-2 text-sm">
                @foreach ($summary['distribution'] as $star => $total)
                    <div class="grid grid-cols-[3.5rem_1fr_2rem] items-center gap-3">
                        <dt class="text-kabut-700">{{ $star }} bintang</dt>
                        <dd class="h-1.5 bg-kabut-200" aria-hidden="true"><div class="h-full bg-hutan-800" style="width: {{ round($total / $summary['count'] * 100) }}%"></div></dd>
                        <dd class="text-right tabular-nums text-kabut-700"><span class="sr-only">{{ $star }} bintang: </span>{{ $total }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
        <p class="mt-6 border-t border-kabut-200 pt-4 text-xs leading-relaxed text-kabut-600">Hanya tamu yang sudah check-in dan menyelesaikan masa menginapnya yang bisa menulis ulasan. Setiap ulasan diperiksa tim kami sebelum tayang.</p>
    @else
        <p class="font-display text-2xl text-hutan-950">Belum ada ulasan</p>
        <p class="mt-2 text-sm leading-relaxed text-kabut-700">Ulasan ditulis oleh tamu yang sudah check-in dan menyelesaikan masa menginapnya, lalu diperiksa tim kami sebelum tayang.</p>
    @endif
</div>
