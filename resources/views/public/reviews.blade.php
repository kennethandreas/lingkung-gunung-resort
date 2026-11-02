@use('App\Enums\ReviewTargetType')

@php
    $name = match ($type) {
        ReviewTargetType::Resort => 'Lingkung Gunung',
        ReviewTargetType::UnitType => $target->marketing_name,
        default => $target->name,
    };
    $back = match ($type) {
        ReviewTargetType::Resort => null,
        ReviewTargetType::UnitType => [route('akomodasi.show', $target), 'Kembali ke '.$target->marketing_name],
        ReviewTargetType::Activity => [route('play').'#aktivitas', 'Kembali ke daftar aktivitas'],
        ReviewTargetType::GroupPackage => [route('gather').'#paket', 'Kembali ke paket rombongan'],
    };
    $title = $type === ReviewTargetType::Resort ? 'Ulasan tamu' : 'Ulasan '.$name;
@endphp

<x-layouts.public :title="$title" :description="'Ulasan dan penilaian dari tamu yang sudah menginap di '.($type === ReviewTargetType::Resort ? 'Lingkung Gunung Adventure Camp Resort Bogor' : $name.' · Lingkung Gunung').'.'">
    <section class="section bg-kabut-50">
        <div class="container-lg">
            @if ($back)
                <a href="{{ $back[0] }}" class="tautan-panah text-kabut-700"><x-heroicon-m-arrow-left class="size-3.5" aria-hidden="true" /> {{ $back[1] }}</a>
            @endif

            <x-ui.section-heading level="h1" :class="$back ? 'mt-6' : ''"
                :eyebrow="$type === ReviewTargetType::Resort ? 'Ulasan tamu' : $type->getLabel()"
                :title="$type === ReviewTargetType::Resort ? 'Apa kata tamu tentang Lingkung Gunung' : 'Ulasan tamu untuk '.$name"
                lead="Ditulis oleh tamu setelah masa menginap mereka selesai, dan diperiksa tim kami sebelum tayang." />

            <div class="mt-12 grid gap-10 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start">
                <aside class="lg:sticky lg:top-24">
                    <x-ulasan.ringkasan :summary="$summary" />
                    @if ($type === ReviewTargetType::Resort)
                        <p class="mt-4 text-sm leading-relaxed text-kabut-700">Pernah menginap di sini? Buka <a href="{{ route('akun.booking.index') }}" class="font-semibold text-hutan-800 underline underline-offset-4">Booking Saya</a> di akun Anda untuk menulis ulasan.</p>
                    @endif
                </aside>

                <x-ulasan.daftar :reviews="$reviews" :summary="$summary" :sort="$sort" :stars="$stars" class="min-w-0" />
            </div>
        </div>
    </section>
</x-layouts.public>
