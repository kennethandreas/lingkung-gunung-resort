<x-layouts.akun title="Akomodasi Tersimpan" heading="Tersimpan" lead="Akomodasi yang Anda simpan untuk direncanakan nanti. Menyimpan tidak memesan dan tidak menahan kamar.">
    @if ($rows->isEmpty())
        <x-akun.empty-state icon="heroicon-o-heart" title="Belum ada akomodasi tersimpan" text="Ketuk ikon hati pada akomodasi yang Anda sukai, lalu temukan kembali di sini kapan saja.">
            <x-ui.button variant="primary" :href="route('akomodasi.index')">Jelajahi akomodasi</x-ui.button>
        </x-akun.empty-state>
    @else
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($rows as $row)
                <div class="flex flex-col gap-3">
                    <x-ui.card-akomodasi
                        class="h-full"
                        :name="$row['model']->marketing_name"
                        :tagline="$row['model']->tagline"
                        :media="$row['cover']"
                        :href="route('akomodasi.show', $row['model'])"
                        :price="$row['prices']['from']"
                        :price-unit="$row['unit']"
                        :capacity="$row['model']->isPricedPerPerson() ? null : $row['model']->capacity_pax"
                        :badge="$row['model']->is_bookable_online ? null : 'Pesan via WhatsApp'"
                    />
                    <div class="flex gap-2">
                        @if ($row['model']->is_bookable_online)
                            <x-ui.button variant="primary" size="sm" class="flex-1" :href="route('pesan', ['unit' => $row['model']->slug])">Cek ketersediaan</x-ui.button>
                        @endif
                        <form method="POST" action="{{ route('akun.tersimpan.toggle', $row['model']) }}" class="flex-1">
                            @csrf
                            <x-ui.button type="submit" variant="outline" size="sm" class="w-full" icon="heroicon-o-trash">Hapus</x-ui.button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.akun>
