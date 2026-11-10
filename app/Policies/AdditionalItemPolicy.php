<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdditionalItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AdditionalItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdditionalItem');
    }

    public function view(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('View:AdditionalItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdditionalItem');
    }

    public function update(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('Update:AdditionalItem');
    }

    public function delete(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('Delete:AdditionalItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdditionalItem');
    }

    public function restore(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('Restore:AdditionalItem');
    }

    public function forceDelete(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('ForceDelete:AdditionalItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdditionalItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdditionalItem');
    }

    public function replicate(AuthUser $authUser, AdditionalItem $additionalItem): bool
    {
        return $authUser->can('Replicate:AdditionalItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdditionalItem');
    }
}
