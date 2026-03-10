<?php

namespace App\Observers;

use App\Mail\PastoralCareAppointmentUpdated;
use App\Models\PastoralCare;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PastoralCareObserver
{
    /**
     * Fields that trigger notification when changed.
     */
    protected array $notifiableFields = [
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'location_type',
        'zoom_link',
        'pastor_id',
        'status',
    ];

    /**
     * Handle the PastoralCare "created" event.
     */
    public function created(PastoralCare $pastoralCare): void
    {
        // Note: Creation notifications are handled in the controller
        // to have access to request context and better control over timing
        // This observer focuses on updates to ensure no notification is missed
    }

    /**
     * Handle the PastoralCare "updated" event.
     */
    public function updated(PastoralCare $pastoralCare): void
    {
        // Check if any notifiable field was changed
        $changes = $this->getNotifiableChanges($pastoralCare);

        if ($changes === []) {
            return;
        }

        // Don't send update notifications for status changes that are handled elsewhere
        // (confirm, cancel methods in the controller already send their own notifications)
        if (count($changes) === 1 && isset($changes['status'])) {
            return;
        }

        $this->sendUpdateNotifications($pastoralCare, $changes);
    }

    /**
     * Handle the PastoralCare "deleted" event.
     */
    public function deleted(PastoralCare $pastoralCare): void
    {
        //
    }

    /**
     * Handle the PastoralCare "restored" event.
     */
    public function restored(PastoralCare $pastoralCare): void
    {
        //
    }

    /**
     * Handle the PastoralCare "force deleted" event.
     */
    public function forceDeleted(PastoralCare $pastoralCare): void
    {
        //
    }

    /**
     * Get the changes that should trigger notifications.
     */
    protected function getNotifiableChanges(PastoralCare $pastoralCare): array
    {
        $changes = [];

        foreach ($this->notifiableFields as $field) {
            if ($pastoralCare->wasChanged($field)) {
                $original = $pastoralCare->getOriginal($field);
                $new = $pastoralCare->getAttribute($field);

                // Format date fields for display
                if ($field === 'appointment_date') {
                    $original = $original ? \Carbon\Carbon::parse($original)->format('d/m/Y') : null;
                    $new = $new ? \Carbon\Carbon::parse($new)->format('d/m/Y') : null;
                }

                // Format time fields for display
                if ($field === 'appointment_time') {
                    $original = $original ? \Carbon\Carbon::parse($original)->format('H:i') : null;
                    $new = $new ? \Carbon\Carbon::parse($new)->format('H:i') : null;
                }

                // Get pastor name if pastor_id changed
                if ($field === 'pastor_id') {
                    $originalPastor = $original ? \App\Models\User::find($original) : null;
                    $newPastor = $new ? \App\Models\User::find($new) : null;
                    $original = $originalPastor ? $originalPastor->first_name.' '.$originalPastor->last_name : '-';
                    $new = $newPastor ? $newPastor->first_name.' '.$newPastor->last_name : '-';
                }

                // Format location_type for display
                if ($field === 'location_type') {
                    $locationLabels = [
                        'in_person' => 'En personne',
                        'zoom' => 'Zoom',
                        'hybrid' => 'Hybride',
                    ];
                    $original = $locationLabels[$original] ?? $original;
                    $new = $locationLabels[$new] ?? $new;
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
     * Send notifications when an appointment is updated.
     */
    protected function sendUpdateNotifications(PastoralCare $pastoralCare, array $changes): void
    {
        try {
            // Load relationships if not already loaded
            $pastoralCare->loadMissing(['user', 'pastor']);

            // Send email to client if they have an email
            if ($pastoralCare->client_email) {
                Mail::to($pastoralCare->client_email)
                    ->send(new PastoralCareAppointmentUpdated($pastoralCare, $changes, 'client'));
            }

            // Send email to pastor
            if ($pastoralCare->pastor && $pastoralCare->pastor->email) {
                // Don't notify the pastor if they are the one making the change
                $currentUserId = auth()->id();
                if ($pastoralCare->pastor->id !== $currentUserId) {
                    Mail::to($pastoralCare->pastor->email)
                        ->send(new PastoralCareAppointmentUpdated($pastoralCare, $changes, 'pastor'));
                }
            }

            // If user has an account and is different from client_email, notify them too
            if ($pastoralCare->user && $pastoralCare->user->email && $pastoralCare->user->email !== $pastoralCare->client_email) {
                $currentUserId = auth()->id();
                if ($pastoralCare->user->id !== $currentUserId) {
                    Mail::to($pastoralCare->user->email)
                        ->send(new PastoralCareAppointmentUpdated($pastoralCare, $changes, 'client'));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send pastoral care update notifications: '.$e->getMessage(), [
                'pastoral_care_id' => $pastoralCare->id,
                'changes' => $changes,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
