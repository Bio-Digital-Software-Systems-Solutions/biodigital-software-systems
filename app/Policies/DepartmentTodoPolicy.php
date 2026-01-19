<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentTodoPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user has access to a department's todos.
     * Access is granted if user:
     * - Is a member of the department
     * - Has 'manage departments' permission (admin access)
     * - Is the head of the department
     */
    protected function hasAccessToDepartment(User $user, Department $department): bool
    {
        // Admin-level access via permission
        if ($user->can('manage departments')) {
            return true;
        }

        // Head of department has full access
        if ($department->head_of_department === $user->id) {
            return true;
        }

        // Department members have access
        return $department->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the list of todos for a department.
     */
    public function viewAny(User $user, Department $department): bool
    {
        return $this->hasAccessToDepartment($user, $department);
    }

    /**
     * Determine whether the user can view a specific todo.
     */
    public function view(User $user, DepartmentTodo $todo): bool
    {
        return $this->hasAccessToDepartment($user, $todo->department);
    }

    /**
     * Determine whether the user can create todos for a department.
     */
    public function create(User $user, Department $department): bool
    {
        return $this->hasAccessToDepartment($user, $department);
    }

    /**
     * Determine whether the user can update a todo.
     */
    public function update(User $user, DepartmentTodo $todo): bool
    {
        return $this->hasAccessToDepartment($user, $todo->department);
    }

    /**
     * Determine whether the user can delete a todo.
     */
    public function delete(User $user, DepartmentTodo $todo): bool
    {
        return $this->hasAccessToDepartment($user, $todo->department);
    }

    /**
     * Determine whether the user can bulk update todos for a department.
     */
    public function bulkUpdate(User $user, Department $department): bool
    {
        return $this->hasAccessToDepartment($user, $department);
    }
}
