<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'question', 'answer', 'is_active', 'sort_order'])]
class Faq extends Model
{
    use LogsChanges;

    public const CATEGORIES = [
        'umum' => 'Umum',
        'booking' => 'Pemesanan',
        'pembayaran' => 'Pembayaran',
        'rombongan' => 'Rombongan & MICE',
        'akses' => 'Akses & Lokasi',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
