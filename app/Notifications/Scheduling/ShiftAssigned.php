<?php

namespace App\Notifications\Scheduling;

use App\Models\Scheduling\Shift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shift $shift,
        public string $timeSlot,
        public ?User $assignedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $assignedByName = $this->getAssignedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');
        $department = $this->shift->department?->name ?? 'N/A';

        $mailMessage = (new MailMessage)
            ->subject("Programmation : vous êtes assigné(e) au shift du {$shiftDate}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$assignedByName} vous a programmé(e) dans un shift.")
            ->line('')
            ->line("**Département :** {$department}")
            ->line("**Date :** {$shiftDate}")
            ->line("**Horaires :** {$this->shift->start_time} - {$this->shift->end_time}")
            ->line("**Créneau :** {$this->timeSlot}");

        if ($this->shift->title) {
            $mailMessage->line("**Titre :** {$this->shift->title}");
        }

        if ($this->shift->location) {
            $mailMessage->line("**Lieu :** {$this->shift->location}");
        }

        if ($this->shift->notes) {
            $mailMessage->line("**Notes :** {$this->shift->notes}");
        }

        return $mailMessage
            ->action('Voir le planning', $this->getActionUrl())
            ->line('Merci de votre collaboration !');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $assignedByName = $this->getAssignedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');

        return [
            'type' => 'shift_assigned',
            'title' => "Shift assigné le {$shiftDate}",
            'message' => "{$assignedByName} vous a programmé(e) au shift du {$shiftDate} ({$this->shift->start_time} - {$this->shift->end_time}), créneau {$this->timeSlot}.",
            'shift_id' => $this->shift->id,
            'shift_uuid' => $this->shift->uuid,
            'department_id' => $this->shift->department_id,
            'department_name' => $this->shift->department?->name,
            'shift_date' => $this->shift->date->toDateString(),
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
            'time_slot' => $this->timeSlot,
            'assigned_by_id' => $this->assignedBy?->id,
            'assigned_by_name' => $assignedByName,
            'action_url' => $this->getActionUrl(),
            'created_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    private function getAssignedByName(): string
    {
        return $this->assignedBy instanceof User
            ? trim("{$this->assignedBy->first_name} {$this->assignedBy->last_name}")
            : 'Le système';
    }

    private function getActionUrl(): string
    {
        return route('departments.schedule.shifts.show', [
            'department' => $this->shift->department?->uuid,
            'schedule' => $this->shift->weeklySchedule?->uuid,
            'shift' => $this->shift->uuid,
        ]);
    }
}
