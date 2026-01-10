<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view projects');
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        // Project manager can always view
        if ($project->project_manager_id === $user->id) {
            return true;
        }

        // Check if user is a participant
        if ($project->participants && $project->participants->contains('id', $user->id)) {
            return true;
        }

        // Check if user has view projects permission
        return $user->can('view projects');
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->can('create projects');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // Project manager can always update
        if ($project->project_manager_id === $user->id) {
            return true;
        }

        // Check permission
        return $user->can('edit projects');
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only project manager or admin can delete
        if ($project->project_manager_id === $user->id) {
            return true;
        }

        return $user->can('delete projects');
    }
}
