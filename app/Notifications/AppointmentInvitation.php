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

    /**
     * @var \App\Models\Appointment
     */
    public $appointment;
    /**
     * @var string
     */
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($this->appointment->end_datetime);

        $confirmUrl = route('appointments.participant.confirm', [
            'appointment' => $this->appointment->uuid,
            'token' => $this->confirmationToken
        ]);
        $declineUrl = route('appointments.participant.decline', [
            'appointment' => $this->appointment->uuid,
            'token' => $this->confirmationToken
        ]);

        return (new MailMessage)
            ->subject("Invitation au rendez-vous : {$this->appointment->title}")
            ->view('emails.appointment-invitation', [
                'appointment' => $this->appointment,
                'participant' => $notifiable,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'confirmUrl' => $confirmUrl,
                'declineUrl' => $declineUrl,
                'confirmationToken' => $this->confirmationToken,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $startDate = \Carbon\Carbon::parse($this->appointment->start_datetime);
        $organizerName = $this->appointment->organizer->first_name . ' ' . $this->appointment->organizer->last_name;

        $confirmUrl = route('appointments.participant.confirm', [
            'appointment' => $this->appointment->uuid,
            'token' => $this->confirmationToken
        ]);
        $declineUrl = route('appointments.participant.decline', [
            'appointment' => $this->appointment->uuid,
            'token' => $this->confirmationToken
        ]);

        return [
            'type' => 'appointment_invitation',
            'title' => "Invitation au rendez-vous : {$this->appointment->title}",
            'message' => "Vous êtes invité(e) par {$organizerName} au rendez-vous \"{$this->appointment->title}\" le {$startDate->format('d/m/Y à H:i')}.",
            'appointment_id' => $this->appointment->id,
            'appointment_uuid' => $this->appointment->uuid,
            'appointment_title' => $this->appointment->title,
            'appointment_date' => $startDate->format('d/m/Y'),
            'appointment_time' => $startDate->format('H:i') . ' - ' . \Carbon\Carbon::parse($this->appointment->end_datetime)->format('H:i'),
            'organizer_name' => $organizerName,
            'confirmation_token' => $this->confirmationToken,
            'confirm_url' => $confirmUrl,
            'decline_url' => $declineUrl,
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
}
