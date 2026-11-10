@php
    $labels = [1 => 'Buruk', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik'];
    $displayName = \App\Models\Review::displayNameFor(auth('customer')->user()->name);
    $photoCount = $existingPhotos->whereNotIn('id', $removeMediaIds)->count() + count($photos);
    $photoErrors = collect($errors->get('upload'))
        ->merge(collect($errors->get('upload.*'))->flatten())
        ->merge($errors->get('photos'))
        ->merge(collect($errors->get('photos.*'))->flatten())
        ->unique();
@endphp

<form wire:submit="save" class="mt-8 space-y-8 border-t border-kabut-200 pt-8" novalidate>
    @if ($errorMessage)
        <x-ui.alert variant="bahaya">{{ $errorMessage }}</x-ui.alert>
    @endif

    @if ($review && $review->status === \App\Enums\ReviewStatus::Published)
        <x-ui.alert variant="info">Ulasan ini sedang tayang. Bila diubah, ulasan disembunyikan dulu sampai tim kami menyetujui versi barunya.</x-ui.alert>
    @endif

    {{-- Penilaian bintang: radio asli (bisa dipakai keyboard: Tab lalu panah kiri/kanan), bintang sebagai tampilan --}}
    <fieldset x-data="{ r: $wire.entangle('rating'), h: 0 }">
        <legend class="font-semibold text-kabut-900">Penilaian Anda <span class="text-bahaya-700" aria-hidden="true">*</span><span class="sr-only">(wajib)</span></legend>
        <div class="mt-3 flex items-center gap-1" x-on:mouseleave="h = 0">
            @foreach ($labels as $value => $label)
                <span class="relative">
                    <input type="radio" id="rating-{{ $value }}" name="rating" value="{{ $value }}" wire:model="rating" class="peer sr-only" @if ($errors->has('rating')) aria-invalid="true" aria-describedby="rating-error" @endif>
                    <label for="rating-{{ $value }}" x-on:mouseenter="h = {{ $value }}"
                           class="block cursor-pointer p-1 transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-1 peer-focus-visible:outline-hutan-700"
                           x-bind:class="(h || r) >= {{ $value }} ? 'text-emas-500' : 'text-kabut-300'">
                        <x-heroicon-s-star class="size-9" aria-hidden="true" />
                        <span class="sr-only">{{ $value }} bintang, {{ $label }}</span>
                    </label>
                </span>
            @endforeach
        </div>
        <p class="mt-2 text-sm text-kabut-700" aria-live="polite">
            <span x-show="r" x-cloak><span x-text="r"></span> dari 5 · <span x-text="@js($labels)[r]"></span></span>
            <span x-show="! r">Pilih 1 sampai 5 bintang.</span>
        </p>
        @error('rating')
            <p id="rating-error" class="mt-2 text-sm font-medium text-bahaya-700">{{ $message }}</p>
        @enderror
    </fieldset>

    {{-- Komentar (opsional) --}}
    <div x-data="{ n: {{ mb_strlen($comment) }} }">
        <label for="comment" class="font-semibold text-kabut-900">Ceritakan pengalaman Anda <span class="font-normal text-kabut-600">(opsional)</span></label>
        <textarea id="comment" wire:model="comment" rows="6" maxlength="{{ $commentMax }}" x-on:input="n = $event.target.value.length"
                  class="mt-2 block w-full rounded-none border border-kabut-300 bg-white px-4 py-3 text-kabut-900 placeholder:text-kabut-400 focus:border-hutan-700 focus:outline-none focus:ring-1 focus:ring-hutan-700"
                  placeholder="Mis. kenyamanan kamar, pelayanan staf, makanan, kebersihan, suasana." @error('comment') aria-invalid="true" aria-describedby="comment-error" @enderror></textarea>
        <p class="mt-1 text-right text-xs tabular-nums text-kabut-600"><span x-text="n"></span> / {{ $commentMax }}</p>
        @error('comment')
            <p id="comment-error" class="text-sm font-medium text-bahaya-700">{{ $message }}</p>
        @enderror
    </div>

    {{-- Foto (opsional) --}}
    <div x-data="{ uploading: false, progress: 0 }"
         x-on:livewire-upload-start="uploading = true; progress = 0"
         x-on:livewire-upload-finish="uploading = false"
         x-on:livewire-upload-cancel="uploading = false"
         x-on:livewire-upload-error="uploading = false"
         x-on:livewire-upload-progress="progress = $event.detail.progress">
        <p class="font-semibold text-kabut-900">Foto <span class="font-normal text-kabut-600">(opsional, maks. {{ $photoLimit }} foto, JPG/PNG/WebP, maks. {{ $photoMaxMb }} MB per foto)</span></p>

        @if ($existingPhotos->isNotEmpty() || count($photos) > 0)
            <ul class="mt-3 flex flex-wrap gap-3">
                @foreach ($existingPhotos as $media)
                    <li class="w-24" wire:key="foto-lama-{{ $media->id }}">
                        @if (in_array($media->id, $removeMediaIds, true))
                            <img src="{{ route('ulasan.foto', [$media->uuid, 'sm']) }}" alt="Foto ulasan Anda (akan dihapus)" class="size-24 border border-kabut-300 object-cover opacity-30">
                            <button type="button" wire:click="toggleExisting({{ $media->id }})" class="mt-1 text-xs font-semibold text-hutan-800 underline underline-offset-2">Batal hapus</button>
                        @else
                            <img src="{{ route('ulasan.foto', [$media->uuid, 'sm']) }}" alt="Foto ulasan Anda" class="size-24 border border-kabut-300 object-cover">
                            <button type="button" wire:click="toggleExisting({{ $media->id }})" class="mt-1 text-xs font-semibold text-bahaya-700 underline underline-offset-2">Hapus</button>
                        @endif
                    </li>
                @endforeach
                @foreach ($photos as $i => $photo)
                    <li class="w-24" wire:key="foto-baru-{{ $i }}-{{ $photo->getFilename() }}">
                        @if ($photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" alt="Foto baru {{ $i + 1 }}" class="size-24 border border-kabut-300 object-cover">
                        @else
                            <span class="flex size-24 items-center justify-center border border-kabut-300 text-xs text-kabut-600">Foto {{ $i + 1 }}</span>
                        @endif
                        <button type="button" wire:click="removeUpload({{ $i }})" class="mt-1 text-xs font-semibold text-bahaya-700 underline underline-offset-2">Hapus</button>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($photoCount < $photoLimit)
            <label for="upload" class="mt-3 inline-flex h-11 cursor-pointer items-center gap-2 border border-hutan-900 px-5 text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-hutan-900 transition-colors hover:bg-hutan-900 hover:text-kabut-50 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-hutan-700">
                <x-heroicon-m-photo class="size-[1.125rem]" aria-hidden="true" /> Tambah foto
                <input type="file" id="upload" wire:model="upload" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
        @endif

        <div x-show="uploading" x-cloak class="mt-3 max-w-xs" role="status">
            <div class="h-1.5 bg-kabut-200"><div class="h-full bg-hutan-800 transition-[width]" x-bind:style="`width: ${progress}%`"></div></div>
            <p class="mt-1 text-xs text-kabut-700">Mengunggah… <span x-text="progress"></span>%</p>
        </div>

        @foreach ($photoErrors as $message)
            <p class="mt-2 text-sm font-medium text-bahaya-700" role="alert">{{ $message }}</p>
        @endforeach
        <p class="mt-2 text-xs text-kabut-600">Foto diperiksa bersama ulasan dan tampil dengan label "Foto dari tamu". Data lokasi di dalam file foto dihapus otomatis.</p>
    </div>

    <div class="border-t border-kabut-200 pt-6">
        <p class="text-sm leading-relaxed text-kabut-700">Ulasan diperiksa tim kami sebelum tayang di website. Nama yang tampil: <strong class="text-kabut-900">{{ $displayName }}</strong></p>
        <div class="mt-5 flex flex-wrap items-center gap-3">
            <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,upload">
                <span wire:loading.remove wire:target="save">{{ $review ? 'Kirim perubahan' : 'Kirim ulasan' }}</span>
                <span wire:loading wire:target="save">Mengirim…</span>
            </x-ui.button>
            <x-ui.button variant="ghost" :href="route('akun.booking.show', $booking->code).'#ulasan'">Batal</x-ui.button>
            @if ($review)
                <button type="button" wire:click="deleteReview" wire:confirm="Hapus ulasan ini beserta fotonya? Tindakan ini tidak bisa dibatalkan." class="ml-auto text-sm font-semibold text-bahaya-700 underline underline-offset-4 hover:text-bahaya-800">Hapus ulasan</button>
            @endif
        </div>
    </div>
</form>
