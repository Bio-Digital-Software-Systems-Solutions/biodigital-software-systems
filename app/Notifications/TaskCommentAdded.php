<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public Task $task;

    public TaskComment $comment;

    public User $commentedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, TaskComment $comment, User $commentedBy)
    {
        $this->task = $task;
        $this->comment = $comment;
        $this->commentedBy = $commentedBy;
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
        $commentedByName = $this->commentedBy->first_name.' '.$this->commentedBy->last_name;

        $mailMessage = (new MailMessage)
            ->subject("Nouveau commentaire sur la tâche : {$this->task->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$commentedByName} a ajouté un commentaire sur la tâche \"{$this->task->title}\" :")
            ->line('')
            ->line('> '.str_replace("\n", "\n> ", $this->comment->content))
            ->line('');

        if ($this->task->project) {
            $mailMessage->line("**Projet :** {$this->task->project->name}");
        }

        return $mailMessage
            ->action('Voir la tâche', route('tasks.show', $this->task->uuid))
            ->line('Vous recevez cette notification car vous êtes participant de cette tâche.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $commentedByName = $this->commentedBy->first_name.' '.$this->commentedBy->last_name;

        return [
            'type' => 'task_comment_added',
            'title' => "Nouveau commentaire sur : {$this->task->title}",
            'message' => "{$commentedByName} a commenté la tâche \"{$this->task->title}\".",
            'task_id' => $this->task->id,
            'task_uuid' => $this->task->uuid,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project?->name,
            'comment_id' => $this->comment->id,
            'comment_preview' => \Str::limit($this->comment->content, 100),
            'commented_by_id' => $this->commentedBy->id,
            'commented_by_name' => $commentedByName,
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
}
