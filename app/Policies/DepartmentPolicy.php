<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        // Users with view or manage departments permission can view any department
        return $user->can('view departments') || $user->can('manage departments');
    }

    /**
     * Determine whether the user can view department schedules.
     * More restrictive: requires membership unless user is admin.
     */
    public function viewSchedule(User $user, Department $department): bool
    {
        // Admins with manage permission can view all schedules
        if ($user->can('manage departments')) {
            return true;
        }

        // Regular users can only view schedules for departments they are members of
        return $department->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        // User can update if they have manage permission or are head of department
        return $user->can('manage departments') ||
               $department->head_of_department === $user->id;
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->can('manage departments');
    }
}
