<?php

namespace App\Notifications\Scheduling;

use App\Models\Scheduling\Shift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftUnassigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shift $shift,
        public string $timeSlot,
        public ?User $removedBy = null,
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
        $removedByName = $this->getRemovedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');

        return (new MailMessage)
            ->subject("Retrait du shift du {$shiftDate}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$removedByName} vous a retiré(e) d'un shift.")
            ->line('')
            ->line('**Département :** '.($this->shift->department?->name ?? 'N/A'))
            ->line("**Date :** {$shiftDate}")
            ->line("**Horaires :** {$this->shift->start_time} - {$this->shift->end_time}")
            ->line("**Créneau :** {$this->timeSlot}")
            ->action('Voir le planning', $this->getActionUrl())
            ->line("N'hésitez pas à contacter votre responsable pour toute question.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $removedByName = $this->getRemovedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');

        return [
            'type' => 'shift_unassigned',
            'title' => "Retrait du shift du {$shiftDate}",
            'message' => "{$removedByName} vous a retiré(e) du shift du {$shiftDate} ({$this->shift->start_time} - {$this->shift->end_time}), créneau {$this->timeSlot}.",
            'shift_id' => $this->shift->id,
            'shift_uuid' => $this->shift->uuid,
            'department_id' => $this->shift->department_id,
            'department_name' => $this->shift->department?->name,
            'shift_date' => $this->shift->date->toDateString(),
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
            'time_slot' => $this->timeSlot,
            'removed_by_id' => $this->removedBy?->id,
            'removed_by_name' => $removedByName,
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

    private function getRemovedByName(): string
    {
        return $this->removedBy instanceof User
            ? trim("{$this->removedBy->first_name} {$this->removedBy->last_name}")
            : 'Le système';
    }

    private function getActionUrl(): string
    {
        return route('departments.schedule.index', [
            'department' => $this->shift->department?->uuid,
        ]);
    }
}
