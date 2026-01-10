<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskParticipantAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public Task $task;
    public string $role;
    public User $addedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, string $role, User $addedBy)
    {
        $this->task = $task;
        $this->role = $role;
        $this->addedBy = $addedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = $this->getRoleLabel($this->role);
        $addedByName = $this->addedBy->first_name . ' ' . $this->addedBy->last_name;

        $mailMessage = (new MailMessage)
            ->subject("Vous avez été ajouté à la tâche : {$this->task->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$addedByName} vous a ajouté en tant que **{$roleLabel}** à la tâche \"{$this->task->title}\".")
            ->line('');

        if ($this->task->description) {
            $mailMessage->line("**Description :**")
                ->line($this->task->description)
                ->line('');
        }

        if ($this->task->project) {
            $mailMessage->line("**Projet :** {$this->task->project->name}");
        }

        if ($this->task->due_date) {
            $mailMessage->line("**Date d'échéance :** {$this->task->due_date->format('d/m/Y')}");
        }

        if ($this->task->priority) {
            $priorityLabel = $this->getPriorityLabel($this->task->priority);
            $mailMessage->line("**Priorité :** {$priorityLabel}");
        }

        return $mailMessage
            ->action('Voir la tâche', route('tasks.show', $this->task->uuid))
            ->line('Merci de votre collaboration !');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $roleLabel = $this->getRoleLabel($this->role);
        $addedByName = $this->addedBy->first_name . ' ' . $this->addedBy->last_name;

        return [
            'type' => 'task_participant_added',
            'title' => "Ajouté à la tâche : {$this->task->title}",
            'message' => "{$addedByName} vous a ajouté en tant que {$roleLabel} à la tâche \"{$this->task->title}\".",
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project?->name,
            'role' => $this->role,
            'role_label' => $roleLabel,
            'added_by_id' => $this->addedBy->id,
            'added_by_name' => $addedByName,
            'action_url' => route('tasks.show', $this->task->uuid),
            'created_at' => now(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get human-readable role label.
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'assignee' => 'Assigné',
            'reviewer' => 'Réviseur',
            'collaborator' => 'Collaborateur',
            'observer' => 'Observateur',
            default => ucfirst($role),
        };
    }

    /**
     * Get human-readable priority label.
     */
    private function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'lowest' => 'Très basse',
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'highest' => 'Très haute',
            default => ucfirst($priority),
        };
    }
}
