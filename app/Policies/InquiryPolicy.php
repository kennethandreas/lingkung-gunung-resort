<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inquiry;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InquiryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Inquiry');
    }

    public function view(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('View:Inquiry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Inquiry');
    }

    public function update(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('Update:Inquiry');
    }

    public function delete(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('Delete:Inquiry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Inquiry');
    }

    public function restore(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('Restore:Inquiry');
    }

    public function forceDelete(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('ForceDelete:Inquiry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Inquiry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Inquiry');
    }

    public function replicate(AuthUser $authUser, Inquiry $inquiry): bool
    {
        return $authUser->can('Replicate:Inquiry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Inquiry');
    }
}
