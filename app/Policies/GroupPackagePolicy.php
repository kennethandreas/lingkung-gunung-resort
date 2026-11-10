<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GroupPackage;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class GroupPackagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GroupPackage');
    }

    public function view(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('View:GroupPackage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GroupPackage');
    }

    public function update(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('Update:GroupPackage');
    }

    public function delete(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('Delete:GroupPackage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GroupPackage');
    }

    public function restore(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('Restore:GroupPackage');
    }

    public function forceDelete(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('ForceDelete:GroupPackage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GroupPackage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GroupPackage');
    }

    public function replicate(AuthUser $authUser, GroupPackage $groupPackage): bool
    {
        return $authUser->can('Replicate:GroupPackage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GroupPackage');
    }
}
