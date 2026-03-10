<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Messages\TelegramMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment, public bool $isOrganizer = false)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // Add Telegram channel if user has telegram_chat_id and notifications enabled
        if (config('services.telegram.enabled')
            && ! empty($notifiable->telegram_chat_id)
            && $notifiable->telegram_notifications) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($this->appointment->end_datetime);

        $confirmedParticipantsCount = $this->appointment->participants()
            ->wherePivot('status', 'accepted')
            ->count();

        return (new MailMessage)
            ->subject("Rappel : {$this->appointment->title} - Demain à {$startDate->format('H:i')}")
            ->view('emails.appointment-reminder', [
                'appointment' => $this->appointment,
                'recipient' => $notifiable,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'isOrganizer' => $this->isOrganizer,
                'confirmedParticipantsCount' => $confirmedParticipantsCount,
                'detailUrl' => route('appointments.show', $this->appointment->uuid),
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);

        $message = $this->isOrganizer
            ? "Rappel : Votre rendez-vous \"{$this->appointment->title}\" est prévu demain à {$startDate->format('H:i')}."
            : "Rappel : Le rendez-vous \"{$this->appointment->title}\" avec {$this->appointment->organizer->first_name} est prévu demain à {$startDate->format('H:i')}.";

        return [
            'type' => 'appointment_reminder',
            'title' => "Rappel : {$this->appointment->title}",
            'message' => $message,
            'appointment_id' => $this->appointment->id,
            'appointment_uuid' => $this->appointment->uuid,
            'appointment_title' => $this->appointment->title,
            'appointment_date' => $startDate->format('d/m/Y'),
            'appointment_time' => $startDate->format('H:i').' - '.\Carbon\Carbon::parse($this->appointment->end_datetime)->format('H:i'),
            'is_organizer' => $this->isOrganizer,
            'organizer_name' => $this->appointment->organizer->first_name.' '.$this->appointment->organizer->last_name,
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
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($this->appointment->end_datetime);
        $appName = config('app.name', 'ICC Munich');

        $locationType = match ($this->appointment->meeting_mode ?? 'in_person') {
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
            default => 'En presentiel',
        };

        if ($this->isOrganizer) {
            $confirmedCount = $this->appointment->confirmedParticipants()->count();

            $content = "<b>Rappel - Votre rendez-vous de demain</b>\n\n";
            $content .= "<b>{$this->appointment->title}</b>\n\n";
            $content .= "Date: {$startDate->format('d/m/Y')}\n";
            $content .= "Heure: {$startDate->format('H:i')} - {$endDate->format('H:i')}\n";
            $content .= "Participants confirmes: {$confirmedCount}\n";

            if ($this->appointment->location) {
                $content .= "Lieu: {$this->appointment->location}\n";
            }

            $content .= "\nConsultez vos emails pour plus de details.";
        } else {
            $organizer = $this->appointment->organizer->first_name.' '.$this->appointment->organizer->last_name;

            $content = "<b>Rappel de rendez-vous - {$appName}</b>\n\n";
            $content .= "Bonjour {$notifiable->first_name},\n\n";
            $content .= "Votre rendez-vous est prevu pour <b>demain</b>.\n\n";
            $content .= "<b>{$this->appointment->title}</b>\n\n";
            $content .= "Date: {$startDate->format('d/m/Y')}\n";
            $content .= "Heure: {$startDate->format('H:i')} - {$endDate->format('H:i')}\n";
            $content .= "Duree: {$this->appointment->duration_minutes} minutes\n";
            $content .= "Organisateur: {$organizer}\n";
            $content .= "Type: {$locationType}\n";

            if (in_array($this->appointment->meeting_mode, ['online', 'hybrid']) && $this->appointment->meeting_link) {
                $content .= "\nLien de reunion: {$this->appointment->meeting_link}\n";
            }

            if ($this->appointment->location && $this->appointment->meeting_mode !== 'online') {
                $content .= "\nLieu: {$this->appointment->location}\n";
            }

            $content .= "\nEn cas d'empechement, veuillez prevenir l'organisateur.\n\n";
            $content .= 'A bientot !';
        }

        return TelegramMessage::create($content);
    }
}
