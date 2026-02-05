<?php

namespace App\Policies;

use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\User;

class TrainingClassMaterialPolicy
{
    /**
     * Determine whether the user can view any models for a specific class.
     * Admins, teachers of the class, or enrolled students can view materials.
     */
    public function viewAny(User $user, TrainingClass $trainingClass): bool
    {
        // Admins and super-admins can view all materials
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Check if user is the teacher of this class
        if ($trainingClass->teacher_id === $user->id) {
            return true;
        }

        // Check if user is an enrolled student in this class (approved status)
        return $trainingClass->training->students()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('training_class_id', $trainingClass->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the model.
     * Admins, teachers, or enrolled students can view.
     */
    public function view(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        $trainingClass = $trainingClassMaterial->trainingClass;

        // Admins and super-admins can view all materials (even inactive ones)
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Check if user is the teacher of this class
        if ($trainingClass->teacher_id === $user->id) {
            return true;
        }

        // Check if material is active for students
        if (! $trainingClassMaterial->is_active) {
            return false;
        }

        // Check if user is an enrolled student in this class (approved status)
        return $trainingClass->training->students()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('training_class_id', $trainingClass->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models for a specific class.
     * Admins and the teacher of the class can create materials.
     */
    public function create(User $user, TrainingClass $trainingClass): bool
    {
        // Admins and super-admins can create materials for any class
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClass->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     * Admins and the teacher who uploaded it can update.
     */
    public function update(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        // Admins and super-admins can update any material
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClassMaterial->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     * Admins and the teacher who uploaded it can delete.
     */
    public function delete(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        // Admins and super-admins can delete any material
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClassMaterial->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can download the model.
     * Students enrolled in the class or the teacher can download.
     */
    public function download(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        return $this->view($user, $trainingClassMaterial);
    }

    /**
     * Determine whether the user can restore the model.
     * Admins and the original uploader can restore.
     */
    public function restore(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        // Admins and super-admins can restore any material
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClassMaterial->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Admins and the original uploader can force delete.
     */
    public function forceDelete(User $user, TrainingClassMaterial $trainingClassMaterial): bool
    {
        // Admins and super-admins can force delete any material
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClassMaterial->teacher_id === $user->id;
    }
}
