<?php

namespace App\Policies;

use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\User;

class TrainingClassMaterialPolicy
{
    public function viewAny(User $user, TrainingClass $trainingClass): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($trainingClass->teacher_id === $user->id) {
            return true;
        }

        return $trainingClass->training->students()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('training_class_id', $trainingClass->id)
            ->exists();
    }

    public function view(User $user, TrainingClassMaterial $pivot): bool
    {
        $trainingClass = $pivot->trainingClass;

        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($trainingClass->teacher_id === $user->id) {
            return true;
        }

        if (! $pivot->is_active) {
            return false;
        }

        return $trainingClass->training->students()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('training_class_id', $trainingClass->id)
            ->exists();
    }

    public function create(User $user, TrainingClass $trainingClass): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $trainingClass->teacher_id === $user->id;
    }

    /**
     * Update / delete authorisation. A teacher who assigned the material to
     * this class can manage that assignment; the class's own teacher can
     * too. Admins always can.
     */
    public function update(User $user, TrainingClassMaterial $pivot): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($pivot->teacher_id === $user->id) {
            return true;
        }

        return $pivot->trainingClass?->teacher_id === $user->id;
    }

    public function delete(User $user, TrainingClassMaterial $pivot): bool
    {
        return $this->update($user, $pivot);
    }

    public function download(User $user, TrainingClassMaterial $pivot): bool
    {
        return $this->view($user, $pivot);
    }

    public function restore(User $user, TrainingClassMaterial $pivot): bool
    {
        return $this->update($user, $pivot);
    }

    public function forceDelete(User $user, TrainingClassMaterial $pivot): bool
    {
        return $this->update($user, $pivot);
    }
}
