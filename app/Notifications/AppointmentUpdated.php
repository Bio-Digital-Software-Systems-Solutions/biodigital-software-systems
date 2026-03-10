<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class AppointmentUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment, public array $changes = [])
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
        $organizerName = $this->appointment->organizer->first_name . ' ' . $this->appointment->organizer->last_name;

        $mailMessage = (new MailMessage)
            ->subject("Rendez-vous modifié : {$this->appointment->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Le rendez-vous \"{$this->appointment->title}\" a été modifié par {$organizerName}.")
            ->line('')
            ->line("**Détails du rendez-vous :**")
            ->line("- **Date :** {$startDate->format('d/m/Y')}")
            ->line("- **Heure :** {$startDate->format('H:i')} - {$endDate->format('H:i')}");

        if ($this->appointment->location) {
            $mailMessage->line("- **Lieu :** {$this->appointment->location}");
        }

        if ($this->changes !== []) {
            $mailMessage->line('')
                ->line("**Modifications apportées :**");

            foreach ($this->changes as $field => $change) {
                $fieldLabel = $this->getFieldLabel($field);
                $mailMessage->line("- {$fieldLabel} : {$change['old']} → {$change['new']}");
            }
        }

        return $mailMessage
            ->action('Voir le rendez-vous', route('appointments.show', $this->appointment->uuid))
            ->line('Merci de votre attention.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $startDate = Carbon::parse($this->appointment->start_datetime);
        $organizerName = $this->appointment->organizer->first_name . ' ' . $this->appointment->organizer->last_name;

        return [
            'type' => 'appointment_updated',
            'title' => "Rendez-vous modifié : {$this->appointment->title}",
            'message' => "Le rendez-vous \"{$this->appointment->title}\" prévu le {$startDate->format('d/m/Y à H:i')} a été modifié par {$organizerName}.",
            'appointment_id' => $this->appointment->id,
            'appointment_uuid' => $this->appointment->uuid,
            'appointment_title' => $this->appointment->title,
            'appointment_date' => $startDate->format('d/m/Y'),
            'appointment_time' => $startDate->format('H:i') . ' - ' . Carbon::parse($this->appointment->end_datetime)->format('H:i'),
            'organizer_name' => $organizerName,
            'changes' => $this->changes,
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
     * Get human-readable field label.
     */
    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'title' => 'Titre',
            'description' => 'Description',
            'start_datetime' => 'Date/heure de début',
            'end_datetime' => 'Date/heure de fin',
            'location' => 'Lieu',
            'status' => 'Statut',
            'type' => 'Type',
            'visibility' => 'Visibilité',
            'max_participants' => 'Participants max',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
