{{-- Halaman error ramah. Sengaja tanpa query database (error bisa terjadi saat DB bermasalah). --}}
@php
    $wa = 'https://wa.me/'.config('resort.defaults.contact_whatsapp');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Lingkung Gunung</title>
    @if (is_file(public_path('build/manifest.json')) || is_file(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="flex min-h-dvh items-center bg-hutan-950 text-white" style="background:#0d1e15;color:#fff;font-family:system-ui,sans-serif">
    <main class="relative mx-auto w-full max-w-xl px-6 py-16 text-center" style="max-width:36rem;margin:0 auto;padding:4rem 1.5rem;text-align:center">
        <p class="font-display text-7xl font-semibold text-emas-300" style="font-size:4.5rem;color:#dfbb5f;margin:0">@yield('code')</p>
        <h1 class="mt-4 font-display text-display-md font-semibold">@yield('title')</h1>
        <p class="mt-4 text-lg leading-relaxed text-kabut-200" style="color:#ebe3d3">@yield('message')</p>
        <div class="mt-10 flex flex-wrap justify-center gap-3" style="margin-top:2.5rem">
            <a href="{{ url('/') }}" class="inline-flex h-12 items-center rounded-none bg-emas-400 px-6 font-semibold text-hutan-950" style="background:#d4a43c;color:#0d1e15;padding:.8rem 1.5rem;border-radius:999px;text-decoration:none;font-weight:600">Kembali ke beranda</a>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex h-12 items-center rounded-none border border-white/30 px-6 font-semibold text-white" style="border:1px solid rgba(255,255,255,.3);color:#fff;padding:.8rem 1.5rem;border-radius:999px;text-decoration:none;font-weight:600">Hubungi kami via WhatsApp</a>
        </div>
        <p class="mt-12 font-script text-2xl text-emas-300" style="color:#dfbb5f">Tempat Alam Bercerita</p>
    </main>
</body>
</html>
