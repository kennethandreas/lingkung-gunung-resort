<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/** Halaman teks yang bisa diedit admin (kebijakan privasi, mekanisme pembayaran, dll). */
#[Fillable(['slug', 'title', 'meta_description', 'body', 'is_published'])]
class Page extends Model implements HasMedia
{
    use HasWebpImages, LogsChanges;

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
