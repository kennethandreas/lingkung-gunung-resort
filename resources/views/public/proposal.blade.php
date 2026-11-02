@use('App\Services\PaymentService')

<x-layouts.public :title="'Proposal '.$quotation->number" description="Proposal paket rombongan Lingkung Gunung." :mobile-bar="false">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="bg-kabut-50 pb-16 pt-8 sm:pt-12">
        <div class="container-lg max-w-4xl">
            @if (session('proposal_baru'))
                <x-ui.alert variant="sukses" title="Proposal berhasil dibuat" class="mb-6">
                    Salinan proposal juga dikirim ke {{ $quotation->customer->email }}. Klik tombol WhatsApp di bawah agar tim sales segera memproses penawaran Anda.
                </x-ui.alert>
            @endif

            <div class="rounded-kartu bg-white p-6 shadow-lembut ring-1 ring-kabut-200 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-kabut-600">Nomor penawaran</p>
                        <h1 class="font-display text-display-md font-semibold text-hutan-900">{{ $quotation->number }}</h1>
                        <p class="mt-1 text-sm text-kabut-600">{{ $quotation->customer->organization ?: $quotation->customer->name }}</p>
                    </div>
                    <x-ui.badge :variant="$quotation->status->value === 'batal' ? 'bahaya' : ($quotation->status->value === 'deal' ? 'sukses' : 'info')" class="!px-3 !py-1.5 !text-sm">{{ $quotation->status->getLabel() }}</x-ui.badge>
                </div>

                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-kabut-600">Paket</dt><dd class="font-semibold text-kabut-900">{{ $quotation->variant?->name }}</dd></div>
                    <div><dt class="text-kabut-600">Tanggal</dt><dd class="font-semibold text-kabut-900">{{ $quotation->event_date->translatedFormat('d M Y') }} · {{ $quotation->nights }} malam</dd></div>
                    <div><dt class="text-kabut-600">Peserta</dt><dd class="font-semibold text-kabut-900">{{ $quotation->pax }} orang</dd></div>
                </dl>

                <ul class="mt-6 divide-y divide-kabut-200 border-y border-kabut-200 text-sm">
                    @foreach ($quotation->items as $item)
                        <li class="flex justify-between gap-4 py-3">
                            <span>{{ $item->description }} <span class="text-kabut-600">× {{ $item->qty }}</span></span>
                            <span class="shrink-0 font-medium tabular-nums">{{ PaymentService::rupiah($item->subtotal) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex items-baseline justify-between">
                    <span class="font-semibold">Estimasi total</span>
                    <span class="text-2xl font-bold tabular-nums text-hutan-900">{{ PaymentService::rupiah($quotation->total) }}</span>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-ui.button variant="whatsapp" size="lg" :href="$whatsappUrl" target="_blank" rel="noopener">Kirim ke tim sales via WhatsApp</x-ui.button>
                    <x-ui.button variant="outline" size="lg" :href="route('proposal.pdf', $quotation)" target="_blank" icon="heroicon-m-arrow-down-tray">Unduh proposal PDF</x-ui.button>
                </div>
                <p class="mt-4 text-xs text-kabut-600">Harga merupakan estimasi dan akan difinalisasi oleh tim sales. Setelah sepakat, Anda menerima link pembayaran DP 25%.</p>
            </div>

            <p class="mt-6 text-center text-sm text-kabut-600">Simpan halaman ini untuk melihat proposal Anda kembali. <a href="{{ route('rancang-paket') }}" class="font-semibold text-hutan-700 underline underline-offset-2">Rancang paket lain</a></p>
        </div>
    </section>
</x-layouts.public>
