<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Project $project, public ProjectComment $comment, public User $commentedBy)
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
        $commentedByName = $this->commentedBy->first_name.' '.$this->commentedBy->last_name;

        return (new MailMessage)
            ->subject("Nouveau commentaire sur le projet : {$this->project->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$commentedByName} a ajouté un commentaire sur le projet \"{$this->project->name}\" :")
            ->line('')
            ->line('> '.str_replace("\n", "\n> ", $this->comment->content))
            ->line('')
            ->action('Voir le projet', route('projects.board', $this->project->uuid))
            ->line('Vous recevez cette notification car vous êtes participant de ce projet.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $commentedByName = $this->commentedBy->first_name.' '.$this->commentedBy->last_name;

        return [
            'type' => 'project_comment_added',
            'title' => "Nouveau commentaire sur : {$this->project->name}",
            'message' => "{$commentedByName} a commenté le projet \"{$this->project->name}\".",
            'project_id' => $this->project->id,
            'project_uuid' => $this->project->uuid,
            'project_name' => $this->project->name,
            'comment_id' => $this->comment->id,
            'comment_preview' => \Str::limit($this->comment->content, 100),
            'commented_by_id' => $this->commentedBy->id,
            'commented_by_name' => $commentedByName,
            'action_url' => route('projects.board', $this->project->uuid),
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
