{{-- Judul section: label kecil berhuruf kapital, judul serif, paragraf pembuka. level=h1 untuk judul utama halaman tanpa hero. ("script" dipertahankan agar view lama tetap jalan; kini tampil sama seperti label biasa.) --}}
@props(['eyebrow' => null, 'title', 'lead' => null, 'align' => 'left', 'dark' => false, 'script' => false, 'level' => 'h2'])

@php($tag = in_array($level, ['h1', 'h2', 'h3'], true) ? $level : 'h2')

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p @class(['label-kecil flex items-center gap-3', 'justify-center' => $align === 'center', 'text-emas-700' => ! $dark, 'text-emas-300' => $dark])>
            <span @class(['h-px w-8', 'bg-emas-600' => ! $dark, 'bg-emas-300' => $dark]) aria-hidden="true"></span>{{ $eyebrow }}
        </p>
    @endif
    <{{ $tag }} @class(['mt-4 font-display text-display-lg text-balance', 'text-hutan-950' => ! $dark, 'text-white' => $dark])>{{ $title }}</{{ $tag }}>
    @if ($lead)
        <p @class(['mt-4 text-lg leading-relaxed text-pretty', 'text-kabut-700' => ! $dark, 'text-kabut-200' => $dark])>{{ $lead }}</p>
    @endif
</div>
