<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentParticipantController extends Controller
{
    /**
     * Confirm participation to an appointment.
     */
    public function confirm(Appointment $appointment, string $token): Response|RedirectResponse
    {
        // Find the participant with this token
        $participant = $appointment->participants()
            ->wherePivot('confirmation_token', $token)
            ->first();

        if (!$participant) {
            return Inertia::render('Appointments/ConfirmationError', [
                'error' => 'Token de confirmation invalide ou expiré.',
                'appointment' => $appointment->only(['title', 'start_datetime']),
            ]);
        }

        // Check if already responded
        $pivotData = $participant->pivot;
        if ($pivotData->status !== 'pending') {
            $status = $pivotData->status === 'accepted' ? 'confirmé' : 'décliné';
            return Inertia::render('Appointments/ConfirmationAlready', [
                'status' => $status,
                'appointment' => $appointment->only(['title', 'start_datetime']),
            ]);
        }

        // Update participant status
        $appointment->participants()->updateExistingPivot($participant->id, [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Notify organizer and all other participants
        $this->notifyAllParticipants($appointment, $participant, 'confirmed');

        return Inertia::render('Appointments/ConfirmationSuccess', [
            'appointment' => $appointment->only(['title', 'start_datetime', 'location', 'description']),
            'participant' => $participant->only(['first_name', 'last_name']),
        ]);
    }

    /**
     * Decline participation to an appointment.
     */
    public function decline(Appointment $appointment, string $token, Request $request): Response|RedirectResponse
    {
        // Find the participant with this token
        $participant = $appointment->participants()
            ->wherePivot('confirmation_token', $token)
            ->first();

        if (!$participant) {
            return Inertia::render('Appointments/ConfirmationError', [
                'error' => 'Token de confirmation invalide ou expiré.',
                'appointment' => $appointment->only(['title', 'start_datetime']),
            ]);
        }

        // Check if already responded
        $pivotData = $participant->pivot;
        if ($pivotData->status !== 'pending') {
            $status = $pivotData->status === 'accepted' ? 'confirmé' : 'décliné';
            return Inertia::render('Appointments/ConfirmationAlready', [
                'status' => $status,
                'appointment' => $appointment->only(['title', 'start_datetime']),
            ]);
        }

        // Handle GET request (show decline form) vs POST request (process decline)
        if ($request->isMethod('GET')) {
            return Inertia::render('Appointments/DeclineForm', [
                'appointment' => $appointment->only(['title', 'start_datetime', 'location', 'description']),
                'participant' => $participant->only(['first_name', 'last_name']),
                'token' => $token,
            ]);
        }

        // Process decline with optional message
        $message = $request->input('message', '');

        // Update participant status
        $appointment->participants()->updateExistingPivot($participant->id, [
            'status' => 'declined',
            'responded_at' => now(),
            'response_message' => $message,
        ]);

        // Notify organizer and all other participants
        $this->notifyAllParticipants($appointment, $participant, 'declined');

        return Inertia::render('Appointments/DeclineSuccess', [
            'appointment' => $appointment->only(['title', 'start_datetime']),
            'participant' => $participant->only(['first_name', 'last_name']),
        ]);
    }

    /**
     * Notify the organizer and all other participants about a confirmation/decline.
     */
    private function notifyAllParticipants(Appointment $appointment, User $respondingParticipant, string $status): void
    {
        // Reload participants to get fresh data
        $appointment->load(['organizer', 'participants']);

        // Notify the organizer (if they are not the responding participant)
        if ($appointment->organizer && $appointment->organizer->id !== $respondingParticipant->id) {
            $appointment->organizer->notify(
                new AppointmentConfirmation($appointment, $respondingParticipant, $status)
            );
        }

        // Notify all other participants (excluding the responding participant)
        foreach ($appointment->participants as $participant) {
            // Skip the responding participant
            if ($participant->id === $respondingParticipant->id) {
                continue;
            }

            // Skip the organizer (already notified above)
            if ($appointment->organizer && $participant->id === $appointment->organizer->id) {
                continue;
            }

            $participant->notify(
                new AppointmentConfirmation($appointment, $respondingParticipant, $status)
            );
        }
    }
}
