<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Authorization for the agile "story task" context — Task records whose
 * taskable_type is App\Models\Agile\UserStory. Legacy controllers
 * (KanbanController, GanttController, ProjectController…) keep their own
 * permission middleware and do not rely on this policy.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view story tasks');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->id === $task->assigned_to || $user->id === $task->reporter_id) {
            return true;
        }

        return $user->can('view story tasks');
    }

    public function create(User $user): bool
    {
        return $user->can('create story tasks');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->id === $task->assigned_to) {
            return true;
        }

        return $user->can('edit story tasks');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('delete story tasks');
    }
}
