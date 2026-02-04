<?php

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->updateStepStatus($task);
        $this->notifyAssignedUser($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Only update step status if the task status was changed
        if ($task->wasChanged('status_id')) {
            $this->updateStepStatus($task);
            $this->updateProjectStatus($task);
        }

        // Notify user if assignment changed
        if ($task->wasChanged('assigned_to') && $task->assigned_to) {
            $this->notifyAssignedUser($task);
        }
    }

    /**
     * Notify the assigned user about the task assignment.
     */
    protected function notifyAssignedUser(Task $task): void
    {
        if (! $task->assigned_to) {
            return;
        }

        $assignee = $task->assignedUser;

        if (! $assignee) {
            return;
        }

        // Get the user who made the assignment (authenticated user)
        $assignedBy = auth()->user();

        // Don't notify if user assigned to themselves
        if ($assignedBy && $assignedBy->id === $assignee->id) {
            return;
        }

        $assignee->notify(new TaskAssigned($task, $assignedBy));
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $this->updateStepStatus($task);
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        $this->updateStepStatus($task);
    }

    /**
     * Update the parent step status based on tasks completion
     */
    protected function updateStepStatus(Task $task): void
    {
        // Only process if the task belongs to a program step
        if (! $task->program_step_id) {
            return;
        }

        $step = $task->programStep;

        if (! $step) {
            return;
        }

        // Get all tasks for this step (excluding soft deleted)
        // Note: Status is auto-loaded via Task model's $with property
        $allTasks = $step->tasks()->whereNull('deleted_at')->get();

        // If no tasks, set step to pending
        if ($allTasks->isEmpty()) {
            $step->status = 'pending';
            $step->save();

            return;
        }

        // Get completed status
        $completedStatus = Status::where('name', 'completed')->first();

        // Count completed tasks
        $completedTasksCount = $allTasks->where('status_id', $completedStatus?->id)->count();
        $totalTasksCount = $allTasks->count();

        // Determine step status based on tasks
        if ($completedTasksCount === 0) {
            // No tasks completed - status remains pending
            $step->status = 'pending';
        } elseif ($completedTasksCount === $totalTasksCount) {
            // All tasks completed - set to completed
            $step->status = 'completed';
        } else {
            // Some tasks completed - set to in_progress
            $step->status = 'in_progress';
        }

        $step->save();
    }

    /**
     * Update the parent project status based on tasks status
     */
    protected function updateProjectStatus(Task $task): void
    {
        // Only process if the task belongs to a project
        if (! $task->taskable_type || $task->taskable_type !== 'App\Models\Project') {
            return;
        }

        $project = $task->taskable;

        if (! $project) {
            return;
        }

        // Get pending status
        $pendingStatus = Status::where('name', 'pending')->first();

        // Get all tasks for this project (excluding soft deleted)
        // Note: Status is auto-loaded via Task model's $with property
        $allTasks = $project->tasks()->whereNull('deleted_at')->get();

        // If the project is in planning status
        if ($project->status === ProjectStatus::PLANNING) {
            // Check if at least one task has a status other than pending
            $hasStartedTask = $allTasks->where('status_id', '!=', $pendingStatus?->id)->isNotEmpty();

            if ($hasStartedTask) {
                // Auto-start the project
                $project->status = ProjectStatus::ACTIVE;
                $project->save();
            }
        }
    }
}
