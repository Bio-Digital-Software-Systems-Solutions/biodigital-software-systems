<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentUpdated;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    /**
     * Fields that trigger notification when changed.
     */
    protected array $notifiableFields = [
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_link',
        'meeting_mode',
        'meeting_platform',
        'status',
        'type',
    ];

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        $this->sendCreationNotifications($appointment);
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        // Check if any notifiable field was changed
        $changes = $this->getNotifiableChanges($appointment);

        if ($changes === []) {
            return;
        }

        $this->sendUpdateNotifications($appointment, $changes);
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        //
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        //
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        //
    }

    /**
     * Get the changes that should trigger notifications.
     */
    protected function getNotifiableChanges(Appointment $appointment): array
    {
        $changes = [];

        foreach ($this->notifiableFields as $field) {
            if ($appointment->wasChanged($field)) {
                $original = $appointment->getOriginal($field);
                $new = $appointment->getAttribute($field);

                // Format datetime fields for display
                if (in_array($field, ['start_datetime', 'end_datetime'])) {
                    $original = $original ? \Carbon\Carbon::parse($original)->format('d/m/Y H:i') : null;
                    $new = $new ? \Carbon\Carbon::parse($new)->format('d/m/Y H:i') : null;
                }

                $changes[$field] = [
                    'old' => $original ?? '-',
                    'new' => $new ?? '-',
                ];
            }
        }

        return $changes;
    }

    /**
     * Send notifications when an appointment is created.
     */
    protected function sendCreationNotifications(Appointment $appointment): void
    {
        try {
            // Load relationships if not already loaded
            $appointment->loadMissing(['organizer', 'participants']);

            // Notify organizer
            if ($appointment->organizer) {
                $appointment->organizer->notify(new AppointmentCreated($appointment));
            }

            // Note: Participants are notified separately via AppointmentInvitation
            // when they are invited (in the controller), so we don't duplicate here
        } catch (\Exception $e) {
            Log::error('Failed to send appointment creation notifications: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when an appointment is updated.
     */
    protected function sendUpdateNotifications(Appointment $appointment, array $changes): void
    {
        try {
            // Load relationships if not already loaded
            $appointment->loadMissing(['organizer', 'participants']);

            // Notify organizer (if not the one making the change)
            $currentUserId = auth()->id();

            if ($appointment->organizer && $appointment->organizer->id !== $currentUserId) {
                $appointment->organizer->notify(new AppointmentUpdated($appointment, $changes));
            }

            // Notify all participants
            foreach ($appointment->participants as $participant) {
                // Skip the user making the change
                if ($participant->id === $currentUserId) {
                    continue;
                }

                $participant->notify(new AppointmentUpdated($appointment, $changes));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send appointment update notifications: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'changes' => $changes,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
