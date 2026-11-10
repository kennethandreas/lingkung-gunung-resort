<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/** Konten Play / Learn / Gather (aktivitas, program edukasi, segmen rombongan, games). */
#[Fillable(['section', 'group', 'slug', 'name', 'summary', 'description', 'is_featured', 'is_active', 'sort_order'])]
class Program extends Model implements HasMedia
{
    use HasWebpImages, LogsChanges;

    public const SECTIONS = [
        'play' => 'Play · Petualangan & budaya',
        'learn' => 'Learn · Program edukasi',
        'gather' => 'Gather · Rombongan',
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
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function scopeVisible(Builder $query, ?string $section = null): void
    {
        $query->where('is_active', true)
            ->when($section, fn ($q) => $q->where('section', $section))
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
