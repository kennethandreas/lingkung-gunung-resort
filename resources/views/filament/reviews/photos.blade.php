{{-- Foto ulasan di admin (dikirim lewat rute ulasan.foto yang memeriksa izin staf). --}}
@php
    $photos = $getRecord()->getMedia('photos');
@endphp

@if ($photos->isEmpty())
    <p class="text-sm text-gray-500">Tidak ada foto.</p>
@else
    <div class="flex flex-wrap gap-3">
        @foreach ($photos as $photo)
            <a href="{{ route('ulasan.foto', [$photo->uuid, 'md']) }}" target="_blank" rel="noopener" class="block">
                <img src="{{ route('ulasan.foto', [$photo->uuid, 'sm']) }}" alt="Foto ulasan {{ $loop->iteration }}" class="h-32 w-32 object-cover ring-1 ring-gray-950/10" loading="lazy">
            </a>
        @endforeach
    </div>
@endif
