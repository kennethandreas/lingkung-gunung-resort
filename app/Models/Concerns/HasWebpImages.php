<?php

namespace App\Models\Concerns;

use App\Support\ImageSizes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Foto yang diunggah admin otomatis dikonversi ke WebP 480/960/1600px
 * (dipakai <x-ui.picture :media="..."> untuk srcset + lazy loading).
 */
trait HasWebpImages
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (ImageSizes::WIDTHS as $name => $width) {
            $this->addMediaConversion($name)
                ->fit(Fit::Max, $width, $width * 2)
                ->format('webp')
                ->quality(72)
                ->nonQueued();
        }
    }
}
