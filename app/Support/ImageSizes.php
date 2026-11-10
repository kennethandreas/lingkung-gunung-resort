<?php

namespace App\Support;

/** Ukuran konversi WebP untuk foto unggahan (nama konversi => lebar px). */
class ImageSizes
{
    public const WIDTHS = ['sm' => 480, 'md' => 960, 'lg' => 1600];

    /** Jenis file gambar yang boleh diunggah (tanpa SVG/GIF: SVG bisa berisi script). */
    public const UPLOAD_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
}
