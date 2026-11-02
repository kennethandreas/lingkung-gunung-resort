{{--
    Ringkasan harga yang selalu terlihat saat booking.
    lines: [['label' => 'Glamping × 2 malam', 'detail' => 'Jum (WE), Sab (WE)', 'amount' => 1630000], ...]
    Angka di sini hanya tampilan; total final selalu dihitung ulang di server.
--}}
@props(['title' => 'Ringkasan Pesanan', 'lines' => [], 'discount' => 0, 'total' => 0, 'dp' => null, 'dpPercent' => 25, 'meta' => null])

<aside {{ $attributes->class('border border-kabut-300 bg-white p-5 sm:p-6') }} aria-label="{{ $title }}">
    <h2 class="label-kecil text-kabut-700">{{ $title }}</h2>
    @if ($meta)
        <p class="mt-1 text-sm text-kabut-600">{{ $meta }}</p>
    @endif

    <dl class="mt-5 space-y-3 text-sm">
        @forelse ($lines as $line)
            <div class="flex justify-between gap-4">
                <dt class="text-kabut-700">
                    {{ $line['label'] }}
                    @if (! empty($line['detail']))
                        <span class="block text-xs text-kabut-600">{{ $line['detail'] }}</span>
                    @endif
                </dt>
                <dd class="shrink-0 font-medium tabular-nums text-kabut-900">Rp{{ number_format($line['amount'], 0, ',', '.') }}</dd>
            </div>
        @empty
            <p class="text-kabut-600">Pilih tanggal dan akomodasi untuk melihat harga.</p>
        @endforelse

        @if ($discount > 0)
            <div class="flex justify-between gap-4 text-sukses-800">
                <dt>Diskon promo</dt>
                <dd class="font-medium tabular-nums">−Rp{{ number_format($discount, 0, ',', '.') }}</dd>
            </div>
        @endif
    </dl>

    @if ($lines)
        <div class="mt-5 border-t border-kabut-300 pt-4">
            <div class="flex items-baseline justify-between gap-4">
                <span class="font-semibold text-kabut-800">Total</span>
                <span class="text-2xl font-semibold tabular-nums text-hutan-950">Rp{{ number_format($total, 0, ',', '.') }}</span>
            </div>
            @if ($dp)
                <div class="mt-2 flex justify-between gap-4 border-l-2 border-emas-500 bg-emas-50 px-3 py-2 text-sm text-emas-900">
                    <span>Bayar DP {{ $dpPercent }}% sekarang</span>
                    <span class="font-semibold tabular-nums">Rp{{ number_format($dp, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</aside>
