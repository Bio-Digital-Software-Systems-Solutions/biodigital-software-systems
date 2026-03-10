<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectParticipantAdded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Project $project, public string $role, public User $addedBy)
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
        $roleLabel = $this->getRoleLabel($this->role);
        $addedByName = $this->addedBy->first_name . ' ' . $this->addedBy->last_name;

        $mailMessage = (new MailMessage)
            ->subject("Vous avez été ajouté au projet : {$this->project->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$addedByName} vous a ajouté en tant que **{$roleLabel}** au projet \"{$this->project->name}\".")
            ->line('');

        if ($this->project->description) {
            $mailMessage->line("**Description du projet :**")
                ->line($this->project->description)
                ->line('');
        }

        if ($this->project->start_date || $this->project->end_date) {
            $dates = '';
            if ($this->project->start_date) {
                $dates .= "Début : " . $this->project->start_date->format('d/m/Y');
            }
            if ($this->project->end_date) {
                $dates .= ($dates !== '' && $dates !== '0' ? ' - ' : '') . "Fin : " . $this->project->end_date->format('d/m/Y');
            }
            $mailMessage->line("**Dates :** {$dates}");
        }

        return $mailMessage
            ->action('Voir le projet', route('projects.show', $this->project->uuid))
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
            'type' => 'project_participant_added',
            'title' => "Ajouté au projet : {$this->project->name}",
            'message' => "{$addedByName} vous a ajouté en tant que {$roleLabel} au projet \"{$this->project->name}\".",
            'project_id' => $this->project->id,
            'project_uuid' => $this->project->uuid,
            'project_name' => $this->project->name,
            'role' => $this->role,
            'role_label' => $roleLabel,
            'added_by_id' => $this->addedBy->id,
            'added_by_name' => $addedByName,
            'action_url' => route('projects.show', $this->project->uuid),
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
            'member' => 'Membre',
            'contributor' => 'Contributeur',
            'observer' => 'Observateur',
            'lead' => 'Responsable',
            default => ucfirst($role),
        };
    }
}
