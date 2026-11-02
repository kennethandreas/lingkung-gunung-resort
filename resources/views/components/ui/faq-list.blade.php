{{-- Daftar FAQ berbentuk akordeon (bisa dinavigasi keyboard, aria-expanded). --}}
@props(['faqs'])

<div {{ $attributes->class('divide-y divide-kabut-200 rounded-kartu bg-white px-5 shadow-lembut ring-1 ring-kabut-200 sm:px-7') }} x-data="{ open: null }">
    @foreach ($faqs as $faq)
        <div wire:key="faq-{{ $faq->id }}">
            <h3>
                <button type="button" class="flex w-full items-start justify-between gap-4 py-5 text-left font-semibold text-hutan-900"
                        x-on:click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                        x-bind:aria-expanded="open === {{ $faq->id }}" aria-controls="faq-{{ $faq->id }}">
                    <span>{{ $faq->question }}</span>
                    <x-heroicon-m-plus class="mt-0.5 size-5 shrink-0 text-emas-600 transition duration-300" x-bind:class="open === {{ $faq->id }} && 'rotate-45'" aria-hidden="true" />
                </button>
            </h3>
            <div id="faq-{{ $faq->id }}" x-show="open === {{ $faq->id }}" x-collapse x-cloak>
                <p class="pb-5 leading-relaxed text-kabut-700">{{ $faq->answer }}</p>
            </div>
        </div>
    @endforeach
</div>
