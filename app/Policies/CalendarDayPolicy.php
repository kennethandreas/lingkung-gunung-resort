<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarDay;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CalendarDayPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CalendarDay');
    }

    public function view(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('View:CalendarDay');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CalendarDay');
    }

    public function update(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('Update:CalendarDay');
    }

    public function delete(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('Delete:CalendarDay');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CalendarDay');
    }

    public function restore(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('Restore:CalendarDay');
    }

    public function forceDelete(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('ForceDelete:CalendarDay');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CalendarDay');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CalendarDay');
    }

    public function replicate(AuthUser $authUser, CalendarDay $calendarDay): bool
    {
        return $authUser->can('Replicate:CalendarDay');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CalendarDay');
    }
}
