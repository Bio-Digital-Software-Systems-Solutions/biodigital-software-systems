<?php

namespace App\Policies;

use App\Models\BookRental;
use App\Models\User;

class BookRentalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view books');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BookRental $bookRental): bool
    {
        // User can view if they own the rental or have library management permission
        return $user->id === $bookRental->user_id || $user->can('manage library');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('rent books');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BookRental $bookRental): bool
    {
        // User can update (return, extend) if they own the rental or have library management permission
        return $user->id === $bookRental->user_id || $user->can('manage library');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BookRental $bookRental): bool
    {
        // Only library managers can delete rentals
        return $user->can('manage library');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BookRental $bookRental): bool
    {
        return $user->can('manage library');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BookRental $bookRental): bool
    {
        return $user->can('manage library');
    }
}
