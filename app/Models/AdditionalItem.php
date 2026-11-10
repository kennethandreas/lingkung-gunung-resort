<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Item tambahan: aula, lapangan, perlengkapan, konsumsi, hiburan. */
#[Fillable([
    'name', 'category', 'unit', 'unit_label', 'capacity', 'layout',
    'includes', 'price', 'min_qty', 'is_active', 'sort_order',
])]
class AdditionalItem extends Model
{
    use LogsChanges;

    public const CATEGORIES = [
        'aula' => 'Aula / Ruang Meeting',
        'lapangan' => 'Lapangan',
        'meeting' => 'Paket Meeting',
        'perlengkapan' => 'Perlengkapan',
        'konsumsi' => 'Konsumsi',
        'hiburan' => 'Hiburan',
        'utilitas' => 'Utilitas',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
