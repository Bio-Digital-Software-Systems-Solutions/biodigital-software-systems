<?php

namespace App\Services;

use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskParticipant;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class TaskActivityService
{
    /**
     * Log a status change activity.
     */
    public function logStatusChange(Task $task, int $oldStatusId, int $newStatusId, ?User $causer = null): Activity
    {
        $oldStatus = Status::find($oldStatusId);
        $newStatus = Status::find($newStatusId);

        $oldStatusName = $oldStatus?->name ?? 'unknown';
        $newStatusName = $newStatus?->name ?? 'unknown';

        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'status_changed',
                'old_status_id' => $oldStatusId,
                'new_status_id' => $newStatusId,
                'old_status_name' => $oldStatusName,
                'new_status_name' => $newStatusName,
            ])
            ->event('status_changed')
            ->log("Statut changé de \"{$oldStatusName}\" à \"{$newStatusName}\"");
    }

    /**
     * Log a progress change activity.
     */
    public function logProgressChange(Task $task, ?int $oldProgress, int $newProgress, ?User $causer = null): Activity
    {
        $oldProgressValue = $oldProgress ?? 0;

        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'progress_updated',
                'old_progress' => $oldProgressValue,
                'new_progress' => $newProgress,
            ])
            ->event('progress_updated')
            ->log("Progression mise à jour de {$oldProgressValue}% à {$newProgress}%");
    }

    /**
     * Log a participant added activity.
     */
    public function logParticipantAdded(Task $task, TaskParticipant $participant, ?User $causer = null): Activity
    {
        $user = $participant->user;
        $userName = $user ? "{$user->first_name} {$user->last_name}" : 'Utilisateur inconnu';

        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'participant_added',
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'user_name' => $userName,
                'role' => $participant->role,
            ])
            ->event('participant_added')
            ->log("Participant ajouté : {$userName} ({$participant->role})");
    }

    /**
     * Log a participant removed activity.
     */
    public function logParticipantRemoved(Task $task, User $user, string $role, ?User $causer = null): Activity
    {
        $userName = "{$user->first_name} {$user->last_name}";

        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'participant_removed',
                'user_id' => $user->id,
                'user_name' => $userName,
                'role' => $role,
            ])
            ->event('participant_removed')
            ->log("Participant retiré : {$userName}");
    }

    /**
     * Log an attachment added activity.
     */
    public function logAttachmentAdded(Task $task, TaskAttachment $attachment, ?User $causer = null): Activity
    {
        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'attachment_added',
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
            ])
            ->event('attachment_added')
            ->log("Document ajouté : {$attachment->file_name}");
    }

    /**
     * Log an attachment removed activity.
     */
    public function logAttachmentRemoved(Task $task, string $fileName, ?User $causer = null): Activity
    {
        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'attachment_removed',
                'file_name' => $fileName,
            ])
            ->event('attachment_removed')
            ->log("Document supprimé : {$fileName}");
    }

    /**
     * Log assignee change activity.
     */
    public function logAssigneeChange(Task $task, ?int $oldAssigneeId, ?int $newAssigneeId, ?User $causer = null): Activity
    {
        $oldAssignee = $oldAssigneeId ? User::find($oldAssigneeId) : null;
        $newAssignee = $newAssigneeId ? User::find($newAssigneeId) : null;

        $oldName = $oldAssignee ? "{$oldAssignee->first_name} {$oldAssignee->last_name}" : 'Non assigné';
        $newName = $newAssignee ? "{$newAssignee->first_name} {$newAssignee->last_name}" : 'Non assigné';

        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'assignee_changed',
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $newAssigneeId,
                'old_assignee_name' => $oldName,
                'new_assignee_name' => $newName,
            ])
            ->event('assignee_changed')
            ->log("Assigné changé de \"{$oldName}\" à \"{$newName}\"");
    }

    /**
     * Log task creation.
     */
    public function logTaskCreated(Task $task, ?User $causer = null): Activity
    {
        return activity()
            ->performedOn($task)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'type' => 'task_created',
                'title' => $task->title,
            ])
            ->event('created')
            ->log('Tâche créée');
    }

    /**
     * Get all activities for a task.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getTaskActivities(Task $task): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
