<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GalleryPhoto;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class GalleryPhotoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GalleryPhoto');
    }

    public function view(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('View:GalleryPhoto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GalleryPhoto');
    }

    public function update(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('Update:GalleryPhoto');
    }

    public function delete(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('Delete:GalleryPhoto');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GalleryPhoto');
    }

    public function restore(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('Restore:GalleryPhoto');
    }

    public function forceDelete(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('ForceDelete:GalleryPhoto');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GalleryPhoto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GalleryPhoto');
    }

    public function replicate(AuthUser $authUser, GalleryPhoto $galleryPhoto): bool
    {
        return $authUser->can('Replicate:GalleryPhoto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GalleryPhoto');
    }
}
