<?php

namespace App\Notifications;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment)
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
        $startDate = Carbon::parse($this->appointment->start_datetime);
        $endDate = Carbon::parse($this->appointment->end_datetime);

        $mailMessage = (new MailMessage)
            ->subject("Rendez-vous cree : {$this->appointment->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Votre rendez-vous \"{$this->appointment->title}\" a ete cree avec succes.")
            ->line('')
            ->line('**Details du rendez-vous :**')
            ->line("- **Date :** {$startDate->format('d/m/Y')}")
            ->line("- **Heure :** {$startDate->format('H:i')} - {$endDate->format('H:i')}");

        if ($this->appointment->location) {
            $mailMessage->line("- **Lieu :** {$this->appointment->location}");
        }

        $mailMessage->line('- **Type :** '.ucfirst($this->appointment->type));

        // Add meeting link if present
        if ($this->appointment->meeting_link && in_array($this->appointment->meeting_mode, ['online', 'hybrid'])) {
            $platformLabel = $this->getMeetingPlatformLabel($this->appointment->meeting_platform);
            $mailMessage->line("- **Reunion en ligne ({$platformLabel}) :** {$this->appointment->meeting_link}");
        }

        // Get participants count
        $participantsCount = $this->appointment->participants()->count();
        if ($participantsCount > 0) {
            $mailMessage->line('')
                ->line("**Participants invites :** {$participantsCount} personne(s)");
        }

        return $mailMessage
            ->action('Voir le rendez-vous', route('appointments.show', $this->appointment->uuid))
            ->line('Les invitations ont ete envoyees aux participants.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $startDate = Carbon::parse($this->appointment->start_datetime);

        return [
            'type' => 'appointment_created',
            'title' => "Rendez-vous cree : {$this->appointment->title}",
            'message' => "Votre rendez-vous \"{$this->appointment->title}\" prevu le {$startDate->format('d/m/Y a H:i')} a ete cree avec succes.",
            'appointment_id' => $this->appointment->id,
            'appointment_uuid' => $this->appointment->uuid,
            'appointment_title' => $this->appointment->title,
            'appointment_date' => $startDate->format('d/m/Y'),
            'appointment_time' => $startDate->format('H:i').' - '.Carbon::parse($this->appointment->end_datetime)->format('H:i'),
            'action_url' => route('appointments.show', $this->appointment->uuid),
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
     * Get human-readable meeting platform label.
     */
    protected function getMeetingPlatformLabel(?string $platform): string
    {
        return match ($platform) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            'ms_teams' => 'Microsoft Teams',
            'other' => 'Autre',
            default => 'Visioconference',
        };
    }
}
