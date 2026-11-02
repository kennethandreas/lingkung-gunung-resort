<?php

namespace App\Filament\Concerns;

/**
 * Sub-tabel (relation manager) hanya bisa diubah oleh pengguna yang boleh
 * mengubah data induknya. Contoh: Front Office bisa melihat harga tetapi
 * tidak bisa mengubahnya (dokumen teknis bab 7).
 */
trait ReadOnlyWithoutParentUpdate
{
    public function isReadOnly(): bool
    {
        return parent::isReadOnly() || ! auth()->user()?->can('update', $this->getOwnerRecord());
    }
}
