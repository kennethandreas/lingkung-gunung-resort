<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Promo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PromoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Promo');
    }

    public function view(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('View:Promo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Promo');
    }

    public function update(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('Update:Promo');
    }

    public function delete(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('Delete:Promo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Promo');
    }

    public function restore(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('Restore:Promo');
    }

    public function forceDelete(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('ForceDelete:Promo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Promo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Promo');
    }

    public function replicate(AuthUser $authUser, Promo $promo): bool
    {
        return $authUser->can('Replicate:Promo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Promo');
    }
}
