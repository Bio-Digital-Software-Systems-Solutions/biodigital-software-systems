<?php

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

class TrainingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Training $training): bool
    {
        // Admins and teachers see everything
        if ($user->hasAnyRole(['admin', 'super-admin', 'teacher'])) {
            return true;
        }

        // Training teacher always sees their own training
        if ($training->teacher_id === $user->id) {
            return true;
        }

        // Active public trainings are visible to all
        if ($training->is_active && $training->visibility === 'public') {
            return true;
        }

        // Active private trainings require access check
        if ($training->is_active && $training->visibility === 'private') {
            return $training->isAccessibleBy($user);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'teacher']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Training $training): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return $training->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Training $training): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can enroll in the training.
     */
    public function enroll(User $user, Training $training): bool
    {
        if ($training->visibility === 'private') {
            return $training->isAccessibleBy($user);
        }

        return true;
    }

    /**
     * Determine whether the user can manage access for the training.
     */
    public function manageAccess(User $user, Training $training): bool
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return $training->teacher_id === $user->id && $user->can('manage training access');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Training $training): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Training $training): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
