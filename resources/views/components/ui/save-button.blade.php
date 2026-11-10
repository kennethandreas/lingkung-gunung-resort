{{--
    Tombol simpan/batal simpan akomodasi (wishlist). Tanpa JS tetap berfungsi (form POST biasa);
    dengan JS, status diperbarui tanpa memuat ulang halaman. Tamu yang belum login diarahkan ke halaman masuk.
    look: icon (bulat di atas foto kartu) | button (tombol bertulisan)
--}}
@props(['unitType', 'look' => 'icon'])

@use('App\Support\SavedRooms')

@php
    $saved = SavedRooms::has($unitType->id);
    $name = $unitType->marketing_name;
@endphp

<form method="POST" action="{{ route('akun.tersimpan.toggle', $unitType) }}"
      {{ $attributes->class('relative z-10') }}
      x-data="{
          saved: @js($saved),
          busy: false,
          async toggle() {
              if (this.busy) return;
              this.busy = true;
              try {
                  const res = await fetch(this.$el.action, { method: 'POST', body: new FormData(this.$el), headers: { 'Accept': 'application/json' } });
                  if (! res.ok) { this.$el.submit(); return; }
                  this.saved = (await res.json()).saved;
              } catch (e) { this.$el.submit(); } finally { this.busy = false; }
          },
      }"
      x-on:submit.prevent="toggle()">
    @csrf
    @if ($look === 'button')
        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-none border border-hutan-800/25 px-6 text-[0.9375rem] font-semibold text-hutan-800 transition duration-300 hover:border-hutan-800 hover:bg-hutan-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hutan-700 disabled:opacity-50"
                x-bind:disabled="busy">
            <x-heroicon-s-heart class="size-5 text-bahaya-600" aria-hidden="true" x-show="saved" style="{{ $saved ? '' : 'display: none' }}" />
            <x-heroicon-o-heart class="size-5" aria-hidden="true" x-show="! saved" style="{{ $saved ? 'display: none' : '' }}" />
            <span x-text="saved ? 'Tersimpan di akun Anda' : 'Simpan akomodasi ini'">{{ $saved ? 'Tersimpan di akun Anda' : 'Simpan akomodasi ini' }}</span>
        </button>
    @else
        <button type="submit" class="flex size-10 items-center justify-center rounded-none bg-white/90 text-hutan-900 shadow-lembut backdrop-blur-md transition duration-300 hover:scale-105 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:opacity-60"
                aria-pressed="{{ $saved ? 'true' : 'false' }}" x-bind:aria-pressed="saved.toString()" x-bind:disabled="busy"
                aria-label="Simpan {{ $name }}">
            <x-heroicon-s-heart class="size-5 text-bahaya-600" aria-hidden="true" x-show="saved" style="{{ $saved ? '' : 'display: none' }}" />
            <x-heroicon-o-heart class="size-5" aria-hidden="true" x-show="! saved" style="{{ $saved ? 'display: none' : '' }}" />
        </button>
    @endif
</form>
