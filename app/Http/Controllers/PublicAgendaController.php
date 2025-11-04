<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicAgendaController extends Controller
{
    /**
     * Show the public agenda for a user.
     */
    public function show(User $user): Response
    {
        // Get user info (public information only)
        $userData = [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
        ];

        // Get current month's public appointments
        $currentMonth = now()->format('Y-m');
        $publicAppointments = Appointment::forUser($user)
            ->public()
            ->whereMonth('start_datetime', now()->month)
            ->whereYear('start_datetime', now()->year)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_datetime')
            ->get(['start_datetime', 'end_datetime', 'title', 'type', 'status']);

        $calendarEvents = $publicAppointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'start' => $appointment->start_datetime->toISOString(),
                'end' => $appointment->end_datetime->toISOString(),
                'backgroundColor' => $this->getTypeColor($appointment->type),
                'borderColor' => $this->getTypeColor($appointment->type),
                'extendedProps' => [
                    'type' => $appointment->type,
                    'status' => $appointment->status,
                    'formatted_time' => $appointment->start_datetime->format('H:i') . ' - ' . $appointment->end_datetime->format('H:i'),
                ],
            ];
        });

        return Inertia::render('PublicAgenda/Show', [
            'user' => $userData,
            'appointments' => $calendarEvents,
            'currentMonth' => $currentMonth,
        ]);
    }

    /**
     * Get available time slots for a user.
     */
    public function availableSlots(Request $request, User $user): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $duration = (int) $request->get('duration', 60);

        try {
            $slots = Appointment::getPublicAvailableSlots(
                $user,
                $date,
                $duration
            );

            return response()->json([
                'success' => true,
                'date' => $date,
                'duration_minutes' => $duration,
                'available_slots' => $slots,
                'total_slots' => count($slots),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux disponibles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's public schedule for a specific date.
     */
    public function schedule(Request $request, User $user): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());

        try {
            $schedule = Appointment::getUserSchedule($user, $date);

            return response()->json([
                'success' => true,
                'date' => $date,
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                ],
                'appointments' => $schedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du planning.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get color for appointment type.
     */
    private function getTypeColor(string $type): string
    {
        return match ($type) {
            'individual' => '#3B82F6', // Blue
            'group' => '#10B981',       // Green
            'consultation' => '#F59E0B', // Amber
            'meeting' => '#8B5CF6',     // Purple
            default => '#6B7280',       // Gray
        };
    }
}
