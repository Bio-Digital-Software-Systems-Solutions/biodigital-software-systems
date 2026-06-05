<?php

namespace App\Observers;

use App\Mail\CareServiceAppointmentUpdated;
use App\Models\CareService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CareServiceObserver
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
     * Handle the CareService "created" event.
     */
    public function created(CareService $careService): void
    {
        // Note: Creation notifications are handled in the controller
        // to have access to request context and better control over timing
        // This observer focuses on updates to ensure no notification is missed
    }

    /**
     * Handle the CareService "updated" event.
     */
    public function updated(CareService $careService): void
    {
        // Check if any notifiable field was changed
        $changes = $this->getNotifiableChanges($careService);

        if ($changes === []) {
            return;
        }

        // Don't send update notifications for status changes that are handled elsewhere
        // (confirm, cancel methods in the controller already send their own notifications)
        if (count($changes) === 1 && isset($changes['status'])) {
            return;
        }

        $this->sendUpdateNotifications($careService, $changes);
    }

    /**
     * Handle the CareService "deleted" event.
     */
    public function deleted(CareService $careService): void
    {
        //
    }

    /**
     * Handle the CareService "restored" event.
     */
    public function restored(CareService $careService): void
    {
        //
    }

    /**
     * Handle the CareService "force deleted" event.
     */
    public function forceDeleted(CareService $careService): void
    {
        //
    }

    /**
     * Get the changes that should trigger notifications.
     */
    protected function getNotifiableChanges(CareService $careService): array
    {
        $changes = [];

        foreach ($this->notifiableFields as $field) {
            if ($careService->wasChanged($field)) {
                $original = $careService->getOriginal($field);
                $new = $careService->getAttribute($field);

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
    protected function sendUpdateNotifications(CareService $careService, array $changes): void
    {
        try {
            // Load relationships if not already loaded
            $careService->loadMissing(['user', 'pastor']);

            // Send email to client if they have an email
            if ($careService->client_email) {
                Mail::to($careService->client_email)
                    ->send(new CareServiceAppointmentUpdated($careService, $changes, 'client'));
            }

            // Send email to pastor
            if ($careService->pastor && $careService->pastor->email) {
                // Don't notify the pastor if they are the one making the change
                $currentUserId = auth()->id();
                if ($careService->pastor->id !== $currentUserId) {
                    Mail::to($careService->pastor->email)
                        ->send(new CareServiceAppointmentUpdated($careService, $changes, 'pastor'));
                }
            }

            // If user has an account and is different from client_email, notify them too
            if ($careService->user && $careService->user->email && $careService->user->email !== $careService->client_email) {
                $currentUserId = auth()->id();
                if ($careService->user->id !== $currentUserId) {
                    Mail::to($careService->user->email)
                        ->send(new CareServiceAppointmentUpdated($careService, $changes, 'client'));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send care service update notifications: '.$e->getMessage(), [
                'care_service_id' => $careService->id,
                'changes' => $changes,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
