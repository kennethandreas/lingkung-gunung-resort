<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Ulasan tamu di admin panel. Tidak ada buat/ubah/hapus dari admin:
 * isi ulasan milik pelanggan; staf hanya memoderasi (izin khusus Moderate:Review, default admin & owner).
 */
class ReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Review');
    }

    public function view(AuthUser $authUser, Review $review): bool
    {
        return $authUser->can('View:Review');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, Review $review): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, Review $review): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    /** Setujui, tolak, sembunyikan. */
    public function moderate(AuthUser $authUser, Review $review): bool
    {
        return $authUser->can('Moderate:Review');
    }
}
