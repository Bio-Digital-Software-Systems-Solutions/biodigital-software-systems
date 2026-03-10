<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancellation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Models\Appointment
     */
    public $appointment;
    /**
     * @var string|null
     */
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, ?string $reason = null)
    {
        $this->appointment = $appointment;
        $this->reason = $reason;
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

        return (new MailMessage)
            ->subject("❌ Rendez-vous annulé : {$this->appointment->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous vous informons que le rendez-vous suivant a été **annulé** :")
            ->line("**{$this->appointment->title}**")
            ->line("📅 **Date :** {$startDate->format('d/m/Y')}")
            ->line("🕐 **Heure :** {$startDate->format('H:i')} - {$endDate->format('H:i')}")
            ->when($this->appointment->location, fn($message) => $message->line("📍 **Lieu :** {$this->appointment->location}"))
            ->when($this->reason, fn($message) => $message->line("**Motif :** {$this->reason}"))
            ->line('Nous nous excusons pour tout désagrément causé.')
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.')
            ->action('Voir mes rendez-vous', url('/appointments'))
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
