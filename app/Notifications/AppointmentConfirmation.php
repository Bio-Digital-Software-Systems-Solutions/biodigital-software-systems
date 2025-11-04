<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;
    public $participant;
    public $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, User $participant, string $status)
    {
        $this->appointment = $appointment;
        $this->participant = $participant;
        $this->status = $status; // 'confirmed' or 'declined'
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($this->appointment->end_datetime);

        $statusText = $this->status === 'confirmed' ? 'confirmé sa participation' : 'décliné l\'invitation';
        $emoji = $this->status === 'confirmed' ? '✅' : '❌';

        return (new MailMessage)
            ->subject("Réponse au rendez-vous : {$this->appointment->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$emoji} **{$this->participant->name}** a {$statusText} au rendez-vous suivant :")
            ->line("**{$this->appointment->title}**")
            ->line("📅 **Date :** {$startDate->format('d/m/Y')}")
            ->line("🕐 **Heure :** {$startDate->format('H:i')} - {$endDate->format('H:i')}")
            ->when($this->appointment->location, function ($message) {
                return $message->line("📍 **Lieu :** {$this->appointment->location}");
            })
            ->action('Voir les détails', url("/appointments/{$this->appointment->id}"))
            ->line('Vous pouvez consulter l\'état complet des participants dans les détails du rendez-vous.')
            ->salutation('L\'équipe ICC München');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
