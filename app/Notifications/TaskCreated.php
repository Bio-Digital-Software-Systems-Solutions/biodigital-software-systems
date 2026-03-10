<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task, public Project $project, public ?User $createdBy = null)
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
        $createdByName = $this->createdBy instanceof \App\Models\User
            ? $this->createdBy->first_name.' '.$this->createdBy->last_name
            : 'Le système';

        $mailMessage = (new MailMessage)
            ->subject("Nouvelle tâche ajoutée au projet : {$this->project->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Une nouvelle tâche a été ajoutée au projet \"{$this->project->name}\" par {$createdByName}.")
            ->line('')
            ->line("**Tâche :** {$this->task->title}");

        if ($this->task->description) {
            $description = \Str::limit(strip_tags((string) $this->task->description), 200);
            $mailMessage->line("**Description :** {$description}");
        }

        if ($this->task->due_date) {
            $mailMessage->line("**Date d'échéance :** {$this->task->due_date->format('d/m/Y')}");
        }

        if ($this->task->priority) {
            $priorityLabel = $this->getPriorityLabel($this->task->priority);
            $mailMessage->line("**Priorité :** {$priorityLabel}");
        }

        if ($this->task->assignee) {
            $assigneeName = $this->task->assignee->first_name.' '.$this->task->assignee->last_name;
            $mailMessage->line("**Assignée à :** {$assigneeName}");
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
        $createdByName = $this->createdBy instanceof \App\Models\User
            ? $this->createdBy->first_name.' '.$this->createdBy->last_name
            : 'Le système';

        return [
            'type' => 'task_created',
            'title' => "Nouvelle tâche : {$this->task->title}",
            'message' => "Une nouvelle tâche \"{$this->task->title}\" a été ajoutée au projet \"{$this->project->name}\" par {$createdByName}.",
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'task_title' => $this->task->title,
            'project_id' => $this->project->id,
            'project_uuid' => $this->project->uuid,
            'project_name' => $this->project->name,
            'created_by_id' => $this->createdBy?->id,
            'created_by_name' => $createdByName,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->toISOString(),
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
