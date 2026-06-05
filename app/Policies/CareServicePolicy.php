<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CareService;
use App\Models\User;

class CareServicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view care service');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CareService $careService): bool
    {
        // Admins and users with manage permission can view all
        if ($user->can('manage care service') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }

        // Pastors can view their own appointments
        if ($user->can('view care service') && $careService->pastor_id === $user->id) {
            return true;
        }

        // Clients can view their own appointments
        return $user->can('view care service') && $careService->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create care service');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CareService $careService): bool
    {
        // Admins and users with manage permission can update all
        if ($user->can('manage care service') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }

        // Pastors can update their own appointments if they are pending or confirmed
        return $user->can('edit care service') &&
            $careService->pastor_id === $user->id &&
            in_array($careService->status, ['pending', 'confirmed']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CareService $careService): bool
    {
        // Admins and users with manage permission can delete all
        if ($user->can('manage care service') || $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN])) {
            return true;
        }

        // Pastors can delete their own appointments if they are pending
        return $user->can('delete care service') &&
            $careService->pastor_id === $user->id &&
            $careService->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CareService $careService): bool
    {
        if ($user->can('manage care service')) {
            return true;
        }

        return $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CareService $careService): bool
    {
        if ($user->can('manage care service')) {
            return true;
        }

        return $user->hasRole([Role::ADMIN, Role::SUPER_ADMIN]);
    }
}
