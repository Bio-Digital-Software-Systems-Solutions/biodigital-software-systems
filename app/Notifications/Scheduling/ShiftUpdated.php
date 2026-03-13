<?php

namespace App\Notifications\Scheduling;

use App\Models\Scheduling\Shift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public Shift $shift,
        public array $changes,
        public ?User $updatedBy = null,
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
        $updatedByName = $this->getUpdatedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');

        $mailMessage = (new MailMessage)
            ->subject("Modification du shift du {$shiftDate}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$updatedByName} a modifié un shift auquel vous êtes assigné(e).")
            ->line('')
            ->line("**Date :** {$shiftDate}")
            ->line("**Horaires :** {$this->shift->start_time} - {$this->shift->end_time}");

        if (! empty($this->changes)) {
            $mailMessage->line('')->line('**Modifications :**');
            foreach ($this->changes as $field => $change) {
                $label = $this->getFieldLabel($field);
                $mailMessage->line("- {$label} : {$change['old']} → {$change['new']}");
            }
        }

        return $mailMessage
            ->action('Voir le shift', $this->getActionUrl())
            ->line('Merci de votre collaboration !');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $updatedByName = $this->getUpdatedByName();
        $shiftDate = $this->shift->date->format('d/m/Y');

        return [
            'type' => 'shift_updated',
            'title' => "Shift modifié le {$shiftDate}",
            'message' => "{$updatedByName} a modifié le shift du {$shiftDate} ({$this->shift->start_time} - {$this->shift->end_time}).",
            'shift_id' => $this->shift->id,
            'shift_uuid' => $this->shift->uuid,
            'department_id' => $this->shift->department_id,
            'department_name' => $this->shift->department?->name,
            'shift_date' => $this->shift->date->toDateString(),
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
            'changes' => $this->changes,
            'updated_by_id' => $this->updatedBy?->id,
            'updated_by_name' => $updatedByName,
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

    private function getUpdatedByName(): string
    {
        return $this->updatedBy instanceof User
            ? trim("{$this->updatedBy->first_name} {$this->updatedBy->last_name}")
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

    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'start_time' => 'Heure de début',
            'end_time' => 'Heure de fin',
            'date' => 'Date',
            'title' => 'Titre',
            'description' => 'Description',
            'location' => 'Lieu',
            'status' => 'Statut',
            'type' => 'Type',
            'notes' => 'Notes',
            'break_duration' => 'Pause (min)',
            'min_employees' => 'Min. employés',
            'max_employees' => 'Max. employés',
            'hourly_rate' => 'Taux horaire',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
