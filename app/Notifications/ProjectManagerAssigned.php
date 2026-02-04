<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectManagerAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public Project $project;

    public string $role;

    public ?User $assignedBy;

    /**
     * Create a new notification instance.
     *
     * @param  string  $role  'manager' or 'reviewer'
     */
    public function __construct(Project $project, string $role, ?User $assignedBy = null)
    {
        $this->project = $project;
        $this->role = $role;
        $this->assignedBy = $assignedBy;
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
        $assignedByName = $this->assignedBy
            ? $this->assignedBy->first_name.' '.$this->assignedBy->last_name
            : 'Le système';

        $mailMessage = (new MailMessage)
            ->subject("Vous êtes {$roleLabel} du projet : {$this->project->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$assignedByName} vous a désigné comme **{$roleLabel}** du projet \"{$this->project->name}\".")
            ->line('');

        if ($this->project->description) {
            $mailMessage->line('**Description :**')
                ->line($this->project->description)
                ->line('');
        }

        if ($this->project->start_date) {
            $mailMessage->line("**Date de début :** {$this->project->start_date->format('d/m/Y')}");
        }

        if ($this->project->end_date) {
            $mailMessage->line("**Date de fin :** {$this->project->end_date->format('d/m/Y')}");
        }

        if ($this->project->priority) {
            $mailMessage->line("**Priorité :** {$this->project->priority->value}");
        }

        return $mailMessage
            ->action('Voir le projet', route('projects.show', $this->project))
            ->line('Merci de votre collaboration !');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $roleLabel = $this->getRoleLabel($this->role);
        $assignedByName = $this->assignedBy
            ? $this->assignedBy->first_name.' '.$this->assignedBy->last_name
            : 'Le système';

        return [
            'type' => 'project_manager_assigned',
            'title' => "Vous êtes {$roleLabel} : {$this->project->name}",
            'message' => "{$assignedByName} vous a désigné comme {$roleLabel} du projet \"{$this->project->name}\".",
            'project_id' => $this->project->id,
            'project_uuid' => $this->project->uuid,
            'project_name' => $this->project->name,
            'role' => $this->role,
            'role_label' => $roleLabel,
            'assigned_by_id' => $this->assignedBy?->id,
            'assigned_by_name' => $assignedByName,
            'action_url' => route('projects.show', $this->project),
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
            'manager' => 'Chef de projet',
            'reviewer' => 'Réviseur',
            default => ucfirst($role),
        };
    }
}
