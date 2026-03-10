<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserMentionedInComment extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $contextType, public Task|Project $context, public TaskComment|ProjectComment $comment, public User $mentionedBy)
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
        $mentionedByName = $this->mentionedBy->first_name.' '.$this->mentionedBy->last_name;
        $contextTitle = $this->getContextTitle();
        $contextLabel = $this->contextType === 'task' ? 'la tâche' : 'le projet';

        $mailMessage = (new MailMessage)
            ->subject('Vous avez été mentionné(e) dans un commentaire')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$mentionedByName} vous a mentionné(e) dans un commentaire sur {$contextLabel} \"{$contextTitle}\" :")
            ->line('')
            ->line('> '.str_replace("\n", "\n> ", $this->comment->content))
            ->line('');

        if ($this->contextType === 'task' && $this->context->project) {
            $mailMessage->line("**Projet :** {$this->context->project->name}");
        }

        return $mailMessage
            ->action('Voir le commentaire', $this->getActionUrl())
            ->line('Vous recevez cette notification car vous avez été mentionné(e) dans un commentaire.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $mentionedByName = $this->mentionedBy->first_name.' '.$this->mentionedBy->last_name;
        $contextTitle = $this->getContextTitle();

        return [
            'type' => 'user_mentioned_in_comment',
            'context_type' => $this->contextType,
            'title' => 'Mention dans un commentaire',
            'message' => "{$mentionedByName} vous a mentionné(e) dans un commentaire sur \"{$contextTitle}\".",
            'context_id' => $this->context->id,
            'context_uuid' => $this->context->uuid ?? null,
            'context_title' => $contextTitle,
            'project_id' => $this->contextType === 'task' ? $this->context->project_id : $this->context->id,
            'project_name' => $this->contextType === 'task' ? $this->context->project?->name : $this->context->name,
            'comment_id' => $this->comment->id,
            'comment_preview' => Str::limit($this->comment->content, 100),
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by_name' => $mentionedByName,
            'action_url' => $this->getActionUrl(),
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
     * Get the context title.
     */
    private function getContextTitle(): string
    {
        if ($this->contextType === 'task') {
            return $this->context->title;
        }

        return $this->context->name;
    }

    /**
     * Get the action URL for the notification.
     */
    private function getActionUrl(): string
    {
        if ($this->contextType === 'task') {
            return route('tasks.show', $this->context->uuid);
        }

        return route('projects.show', $this->context->slug);
    }
}
