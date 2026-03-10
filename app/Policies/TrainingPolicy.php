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
        return true; // Les formations sont visibles par tous les utilisateurs authentifiés
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Training $training): bool
    {
        // Les formations actives sont visibles par tous
        if ($training->is_active) {
            return true;
        }
        // Les formations inactives sont visibles par les admins et enseignants
        if ($user->hasAnyRole(['admin', 'super-admin', 'teacher'])) {
            return true;
        }
        return $training->teacher_id === $user->id;
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
        // Seul le professeur assigné ou un admin peut modifier
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
        // Seuls les admins peuvent supprimer
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can enroll in the training.
     */
    public function enroll(User $user, Training $training): bool
    {
        // Tous les utilisateurs authentifiés peuvent tenter de s'inscrire
        // Les vérifications spécifiques (déjà inscrit, formation active, etc.)
        // sont gérées dans le contrôleur
        return true;
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
