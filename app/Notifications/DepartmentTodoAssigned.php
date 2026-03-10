<?php

namespace App\Notifications;

use App\Models\Scheduling\DepartmentTodo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepartmentTodoAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DepartmentTodo $todo, public ?User $assignedBy = null)
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

        $departmentName = $this->todo->department?->name ?? 'Département';

        $mailMessage = (new MailMessage)
            ->subject("Tâche de département assignée : {$this->todo->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$assignedByName} vous a assigné la tâche \"{$this->todo->title}\" dans le département \"{$departmentName}\".")
            ->line('');

        if ($this->todo->description) {
            $mailMessage->line('**Description :**')
                ->line($this->todo->description)
                ->line('');
        }

        if ($this->todo->due_date) {
            $mailMessage->line("**Date d'échéance :** {$this->todo->due_date->format('d/m/Y')}");
        }

        if ($this->todo->priority) {
            $mailMessage->line("**Priorité :** {$this->todo->priority->label()}");
        }

        if ($this->todo->estimated_minutes) {
            $mailMessage->line("**Durée estimée :** {$this->todo->estimated_minutes} minutes");
        }

        return $mailMessage
            ->action('Voir les tâches du département', route('departments.show', $this->todo->department))
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

        $departmentName = $this->todo->department?->name ?? 'Département';

        return [
            'type' => 'department_todo_assigned',
            'title' => "Tâche de département assignée : {$this->todo->title}",
            'message' => "{$assignedByName} vous a assigné la tâche \"{$this->todo->title}\" dans le département \"{$departmentName}\".",
            'todo_id' => $this->todo->id,
            'todo_uuid' => $this->todo->uuid,
            'todo_title' => $this->todo->title,
            'department_id' => $this->todo->department_id,
            'department_name' => $departmentName,
            'assigned_by_id' => $this->assignedBy?->id,
            'assigned_by_name' => $assignedByName,
            'action_url' => route('departments.show', $this->todo->department),
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
}
