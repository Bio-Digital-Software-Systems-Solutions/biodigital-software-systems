<?php

namespace App\Notifications;

use App\Models\DepartmentMeeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepartmentMeetingCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public DepartmentMeeting $meeting;

    /**
     * Create a new notification instance.
     */
    public function __construct(DepartmentMeeting $meeting)
    {
        $this->meeting = $meeting;
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
        $appointment = $this->meeting->appointment;
        $department = $this->meeting->department;
        $creator = $this->meeting->creator;

        $startDate = \Carbon\Carbon::parse($appointment->start_datetime);
        $endDate = \Carbon\Carbon::parse($appointment->end_datetime);

        $mail = (new MailMessage)
            ->subject("Réunion du département {$department->name} : {$appointment->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Une nouvelle réunion a été planifiée pour le département **{$department->name}**.")
            ->line('')
            ->line("**{$appointment->title}**")
            ->line("📅 Date : {$startDate->format('l d F Y')}")
            ->line("🕐 Horaire : {$startDate->format('H:i')} - {$endDate->format('H:i')}");

        if ($appointment->location) {
            $mail->line("📍 Lieu : {$appointment->location}");
        }

        if ($appointment->description) {
            $mail->line('')
                 ->line("**Description :**")
                 ->line($appointment->description);
        }

        if ($this->meeting->notes) {
            $mail->line('')
                 ->line("**Notes :**")
                 ->line($this->meeting->notes);
        }

        if ($this->meeting->is_mandatory) {
            $mail->line('')
                 ->line("⚠️ **Cette réunion est obligatoire.**");
        }

        $mail->line('')
             ->line("Organisé par : {$creator->first_name} {$creator->last_name}")
             ->action('Voir les détails', route('appointments.show', $appointment->uuid))
             ->line('Merci de votre participation !');

        return $mail;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $appointment = $this->meeting->appointment;
        $department = $this->meeting->department;
        $creator = $this->meeting->creator;

        $startDate = \Carbon\Carbon::parse($appointment->start_datetime);

        return [
            'type' => 'department_meeting_created',
            'title' => "Réunion du département {$department->name}",
            'message' => "Nouvelle réunion \"{$appointment->title}\" le {$startDate->format('d/m/Y à H:i')} pour le département {$department->name}.",
            'department_id' => $department->id,
            'department_uuid' => $department->uuid,
            'department_name' => $department->name,
            'appointment_id' => $appointment->id,
            'appointment_uuid' => $appointment->uuid,
            'appointment_title' => $appointment->title,
            'appointment_date' => $startDate->format('d/m/Y'),
            'appointment_time' => $startDate->format('H:i') . ' - ' . \Carbon\Carbon::parse($appointment->end_datetime)->format('H:i'),
            'location' => $appointment->location,
            'is_mandatory' => $this->meeting->is_mandatory,
            'creator_name' => "{$creator->first_name} {$creator->last_name}",
            'meeting_uuid' => $this->meeting->uuid,
            'action_url' => route('appointments.show', $appointment->uuid),
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
