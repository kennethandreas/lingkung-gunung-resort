<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MenuSet;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MenuSetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MenuSet');
    }

    public function view(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('View:MenuSet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MenuSet');
    }

    public function update(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('Update:MenuSet');
    }

    public function delete(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('Delete:MenuSet');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MenuSet');
    }

    public function restore(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('Restore:MenuSet');
    }

    public function forceDelete(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('ForceDelete:MenuSet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MenuSet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MenuSet');
    }

    public function replicate(AuthUser $authUser, MenuSet $menuSet): bool
    {
        return $authUser->can('Replicate:MenuSet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MenuSet');
    }
}
