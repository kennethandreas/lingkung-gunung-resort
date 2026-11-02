{{--
    Logo: simbol resmi Lingkung Gunung (diambil dari brosur 2026, latar dibuang tanpa mengubah bentuk; public/images/brand)
    + wordmark teks. light = versi terang untuk latar gelap/foto.
--}}
@props(['light' => false])

<span {{ $attributes->class(['inline-flex items-center gap-2.5', 'text-white' => $light, 'text-hutan-900' => ! $light]) }}>
    <img src="{{ asset($light ? 'images/brand/logo-mark-terang.png' : 'images/brand/logo-mark.png') }}" alt="" width="40" height="72" class="h-10 w-auto shrink-0" decoding="async">
    <span class="leading-none">
        <span class="block font-display text-lg font-semibold tracking-tight">Lingkung Gunung</span>
        <span @class(['block text-[0.625rem] font-semibold uppercase tracking-[0.22em]', 'text-white/70' => $light, 'text-emas-700' => ! $light])>Adventure Camp Bogor</span>
    </span>
</span>
