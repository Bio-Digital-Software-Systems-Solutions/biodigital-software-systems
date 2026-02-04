<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectManagerAssigned;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $this->notifyProjectManager($project);
        $this->notifyReviewer($project);
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        // Notify if project manager changed
        if ($project->wasChanged('project_manager_id') && $project->project_manager_id) {
            $this->notifyProjectManager($project);
        }

        // Notify if reviewer changed
        if ($project->wasChanged('reviewer_id') && $project->reviewer_id) {
            $this->notifyReviewer($project);
        }
    }

    /**
     * Notify the project manager about the assignment.
     */
    protected function notifyProjectManager(Project $project): void
    {
        if (! $project->project_manager_id) {
            return;
        }

        $manager = $project->manager;

        if (! $manager) {
            return;
        }

        // Get the user who made the assignment (authenticated user)
        $assignedBy = auth()->user();

        // Don't notify if user assigned to themselves
        if ($assignedBy && $assignedBy->id === $manager->id) {
            return;
        }

        $manager->notify(new ProjectManagerAssigned($project, 'manager', $assignedBy));
    }

    /**
     * Notify the reviewer about the assignment.
     */
    protected function notifyReviewer(Project $project): void
    {
        if (! $project->reviewer_id) {
            return;
        }

        $reviewer = $project->reviewer;

        if (! $reviewer) {
            return;
        }

        // Get the user who made the assignment (authenticated user)
        $assignedBy = auth()->user();

        // Don't notify if user assigned to themselves
        if ($assignedBy && $assignedBy->id === $reviewer->id) {
            return;
        }

        $reviewer->notify(new ProjectManagerAssigned($project, 'reviewer', $assignedBy));
    }
}
