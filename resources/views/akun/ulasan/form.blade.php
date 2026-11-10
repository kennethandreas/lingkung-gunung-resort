<x-layouts.akun :title="($review ? 'Ubah ulasan' : 'Tulis ulasan').' · '.$booking->code" :heading="$review ? 'Ubah ulasan' : 'Tulis ulasan'">
    <a href="{{ route('akun.booking.show', $booking->code) }}#ulasan" class="tautan-panah text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> Kembali ke booking {{ $booking->code }}</a>

    <div class="mt-6 border border-kabut-300 bg-white p-6 sm:p-8">
        <p class="label-kecil text-kabut-600">{{ $target->type->getLabel() }}</p>
        <p class="mt-1 font-display text-2xl text-hutan-950">{{ $target->name() }}</p>
        <p class="mt-1 text-sm text-kabut-600">Menginap {{ $booking->check_in->translatedFormat('d M Y') }} – {{ $booking->check_out->translatedFormat('d M Y') }}</p>

        <livewire:review-form :booking-code="$booking->code" :target-key="$target->key()" />
    </div>
</x-layouts.akun>
