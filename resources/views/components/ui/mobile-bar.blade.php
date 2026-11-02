{{-- Bar bawah khusus HP: "Cek Ketersediaan" + WhatsApp selalu dalam jangkauan jempol. --}}
@props(['bookingUrl' => '#cek-ketersediaan', 'whatsappMessage' => 'Halo Lingkung Gunung, saya ingin bertanya tentang menginap.'])

<div {{ $attributes->class('fixed inset-x-0 bottom-0 z-40 border-t border-kabut-300 bg-kabut-50 px-4 pt-3 md:hidden') }}
     style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
    <div class="flex items-center gap-3">
        <x-ui.button variant="whatsapp" size="md" :href="\App\Support\WhatsApp::link($whatsappMessage)" target="_blank" rel="noopener" class="!px-0 size-11 shrink-0" aria-label="Chat WhatsApp">
            <span class="sr-only">WhatsApp</span>
        </x-ui.button>
        <x-ui.button variant="primary" size="md" :href="$bookingUrl" class="flex-1" icon-right="heroicon-m-arrow-right">
            Pesan Sekarang
        </x-ui.button>
    </div>
</div>
