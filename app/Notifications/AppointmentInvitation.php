<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AppointmentInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;
    public $confirmationToken;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, string $confirmationToken)
    {
        $this->appointment = $appointment;
        $this->confirmationToken = $confirmationToken;
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

        $confirmUrl = url("/appointments/{$this->appointment->id}/confirm/{$this->confirmationToken}");
        $declineUrl = url("/appointments/{$this->appointment->id}/decline/{$this->confirmationToken}");

        return (new MailMessage)
            ->subject("Invitation au rendez-vous : {$this->appointment->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous êtes invité(e) au rendez-vous suivant :")
            ->line("**{$this->appointment->title}**")
            ->when($this->appointment->description, function ($message) {
                return $message->line("Description : {$this->appointment->description}");
            })
            ->line("📅 **Date :** {$startDate->format('d/m/Y')}")
            ->line("🕐 **Heure :** {$startDate->format('H:i')} - {$endDate->format('H:i')}")
            ->when($this->appointment->location, function ($message) {
                return $message->line("📍 **Lieu :** {$this->appointment->location}");
            })
            ->line("Type : " . ucfirst($this->appointment->type))
            ->line('Merci de confirmer votre participation en cliquant sur l\'un des boutons ci-dessous :')
            ->action('✅ Confirmer ma participation', $confirmUrl)
            ->line('') // Espace
            ->action('❌ Décliner l\'invitation', $declineUrl)
            ->line('Si vous ne pouvez pas cliquer sur les boutons, copiez et collez les liens suivants dans votre navigateur :')
            ->line("Confirmer : {$confirmUrl}")
            ->line("Décliner : {$declineUrl}")
            ->line('Merci pour votre réponse !')
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
