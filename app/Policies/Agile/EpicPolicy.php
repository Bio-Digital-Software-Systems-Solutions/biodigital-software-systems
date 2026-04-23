<?php

namespace App\Policies\Agile;

use App\Models\Agile\Epic;
use App\Models\User;

class EpicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view epics');
    }

    public function view(User $user, Epic $epic): bool
    {
        if ($user->id === $epic->owner_id) {
            return true;
        }

        if ($user->id === $epic->project?->project_manager_id) {
            return true;
        }

        return $user->can('view epics');
    }

    public function create(User $user): bool
    {
        return $user->can('create epics');
    }

    public function update(User $user, Epic $epic): bool
    {
        if ($user->id === $epic->owner_id) {
            return true;
        }

        return $user->can('edit epics');
    }

    public function delete(User $user, Epic $epic): bool
    {
        if ($user->id === $epic->owner_id) {
            return true;
        }

        return $user->can('delete epics');
    }
}
