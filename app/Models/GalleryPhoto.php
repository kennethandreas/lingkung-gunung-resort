<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

#[Fillable(['title', 'category', 'is_featured', 'is_active', 'sort_order'])]
class GalleryPhoto extends Model implements HasMedia
{
    use HasWebpImages, LogsChanges;

    public const CATEGORIES = [
        'akomodasi' => 'Akomodasi',
        'aktivitas' => 'Aktivitas',
        'kuliner' => 'Kuliner',
        'fasilitas' => 'Fasilitas',
        'alam' => 'Alam',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('media')->orderBy('sort_order')->orderBy('id');
    }
}
