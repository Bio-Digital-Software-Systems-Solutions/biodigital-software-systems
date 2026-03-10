<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PastoralCare;
use App\Models\User;

class PastoralCarePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view pastoral care');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PastoralCare $pastoralCare): bool
    {
        // Admins and users with manage permission can view all
        if ($user->can('manage pastoral care') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }

        // Pastors can view their own appointments
        if ($user->can('view pastoral care') && $pastoralCare->pastor_id === $user->id) {
            return true;
        }
        // Clients can view their own appointments  
        return $user->can('view pastoral care') && $pastoralCare->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create pastoral care');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PastoralCare $pastoralCare): bool
    {
        // Admins and users with manage permission can update all
        if ($user->can('manage pastoral care') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }
        // Pastors can update their own appointments if they are pending or confirmed
        return $user->can('edit pastoral care') &&
            $pastoralCare->pastor_id === $user->id &&
            in_array($pastoralCare->status, ['pending', 'confirmed']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PastoralCare $pastoralCare): bool
    {
        // Admins and users with manage permission can delete all
        if ($user->can('manage pastoral care') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }
        // Pastors can delete their own appointments if they are pending
        return $user->can('delete pastoral care') &&
            $pastoralCare->pastor_id === $user->id &&
            $pastoralCare->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PastoralCare $pastoralCare): bool
    {
        if ($user->can('manage pastoral care')) {
            return true;
        }
        return $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PastoralCare $pastoralCare): bool
    {
        if ($user->can('manage pastoral care')) {
            return true;
        }
        return $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN]);
    }
}