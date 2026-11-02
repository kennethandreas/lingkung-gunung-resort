<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/** Artikel, kabar, dan tips dari resort. Tampil di /artikel bila is_published dan published_at sudah lewat. */
#[Fillable(['slug', 'title', 'category', 'excerpt', 'body', 'source_url', 'is_published', 'published_at', 'author_id'])]
class Article extends Model implements HasMedia
{
    use HasWebpImages, LogsChanges;

    public const CATEGORIES = [
        'kabar' => 'Kabar Resort',
        'tips' => 'Tips & Panduan',
        'akomodasi' => 'Akomodasi',
        'cerita' => 'Cerita Tamu & Acara',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Hanya artikel yang sudah terbit (tanggal terbit tidak di masa depan). */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? Str::headline($this->category);
    }

    /** Perkiraan waktu baca (200 kata/menit). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }
}
