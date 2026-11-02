<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UnitType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UnitTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UnitType');
    }

    public function view(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('View:UnitType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UnitType');
    }

    public function update(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('Update:UnitType');
    }

    public function delete(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('Delete:UnitType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UnitType');
    }

    public function restore(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('Restore:UnitType');
    }

    public function forceDelete(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('ForceDelete:UnitType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UnitType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UnitType');
    }

    public function replicate(AuthUser $authUser, UnitType $unitType): bool
    {
        return $authUser->can('Replicate:UnitType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UnitType');
    }
}
