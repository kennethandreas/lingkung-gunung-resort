<?php

namespace App\Models;

use App\Enums\PricingUnit;
use App\Models\Concerns\HasWebpImages;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'slug', 'name', 'marketing_name', 'tagline', 'description', 'bed_configuration',
    'capacity_pax', 'breakfast_pax', 'total_units', 'pricing_unit', 'includes',
    'extra_pax_price', 'is_active', 'is_bookable_online', 'sort_order',
])]
class UnitType extends Model implements HasMedia
{
    use HasWebpImages, LogsChanges;

    /** Foto pertama di koleksi "photos" menjadi sampul kartu & hero halaman unit. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'pricing_unit' => PricingUnit::class,
            'includes' => 'array',
            'is_active' => 'boolean',
            'is_bookable_online' => 'boolean',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(DailyInventory::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Harga dihitung per orang (Camp Site, Campervan), bukan per unit. */
    public function isPricedPerPerson(): bool
    {
        return $this->pricing_unit === PricingUnit::Person;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
