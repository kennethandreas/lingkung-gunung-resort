<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Paket group fullboard: LDKS, Camp, Room Glamping. */
#[Fillable(['slug', 'name', 'accommodation', 'description', 'is_active', 'sort_order'])]
class GroupPackage extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(GroupPackageVariant::class)->orderBy('code');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
