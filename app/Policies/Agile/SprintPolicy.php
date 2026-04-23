<?php

namespace App\Policies\Agile;

use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view projects');
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->can('view projects');
    }

    public function start(User $user, Sprint $sprint): bool
    {
        if ($user->id === $sprint->project?->project_manager_id) {
            return true;
        }

        return $user->can('start sprints');
    }

    public function close(User $user, Sprint $sprint): bool
    {
        if ($user->id === $sprint->project?->project_manager_id) {
            return true;
        }

        return $user->can('close sprints');
    }
}
