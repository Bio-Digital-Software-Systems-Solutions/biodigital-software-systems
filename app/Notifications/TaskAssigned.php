<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task, public ?User $assignedBy = null)
    {
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
        $assignedByName = $this->assignedBy instanceof \App\Models\User
            ? $this->assignedBy->first_name.' '.$this->assignedBy->last_name
            : 'Le système';

        $mailMessage = (new MailMessage)
            ->subject("Tâche assignée : {$this->task->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$assignedByName} vous a assigné la tâche \"{$this->task->title}\".")
            ->line('');

        if ($this->task->description) {
            $mailMessage->line('**Description :**')
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
        $assignedByName = $this->assignedBy instanceof \App\Models\User
            ? $this->assignedBy->first_name.' '.$this->assignedBy->last_name
            : 'Le système';

        return [
            'type' => 'task_assigned',
            'title' => "Tâche assignée : {$this->task->title}",
            'message' => "{$assignedByName} vous a assigné la tâche \"{$this->task->title}\".",
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project?->name,
            'assigned_by_id' => $this->assignedBy?->id,
            'assigned_by_name' => $assignedByName,
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
