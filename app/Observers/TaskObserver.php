<?php

namespace App\Observers;

use App\Models\Status;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->updateStepStatus($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Only update step status if the task status was changed
        if ($task->isDirty('status_id')) {
            $this->updateStepStatus($task);
        }
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
}
