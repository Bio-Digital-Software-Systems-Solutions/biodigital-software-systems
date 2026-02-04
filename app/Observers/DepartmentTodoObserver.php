<?php

namespace App\Observers;

use App\Models\Scheduling\DepartmentTodo;
use App\Models\User;
use App\Notifications\DepartmentTodoAssigned;

class DepartmentTodoObserver
{
    /**
     * Handle the DepartmentTodo "created" event.
     */
    public function created(DepartmentTodo $todo): void
    {
        $this->notifyAssignedUser($todo);
    }

    /**
     * Handle the DepartmentTodo "updated" event.
     */
    public function updated(DepartmentTodo $todo): void
    {
        // Notify if assignment changed
        if ($todo->wasChanged('assigned_to') && $todo->assigned_to) {
            $this->notifyAssignedUser($todo);
        }
    }

    /**
     * Notify the assigned user about the todo assignment.
     */
    protected function notifyAssignedUser(DepartmentTodo $todo): void
    {
        if (! $todo->assigned_to) {
            return;
        }

        $assignee = $todo->assignee;

        if (! $assignee) {
            return;
        }

        // Get the user who made the assignment (authenticated user)
        $assignedBy = auth()->user();

        // Don't notify if user assigned to themselves
        if ($assignedBy && $assignedBy->id === $assignee->id) {
            return;
        }

        $assignee->notify(new DepartmentTodoAssigned($todo, $assignedBy));
    }
}
