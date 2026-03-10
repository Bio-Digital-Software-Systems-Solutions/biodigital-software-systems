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

        // Get current month's appointments (both public and private for complete agenda view)
        $currentMonth = now()->format('Y-m');
        $allAppointments = Appointment::with(['organizer:id,first_name,last_name,email'])
            ->forUser($user)
            ->whereMonth('start_datetime', now()->month)
            ->whereYear('start_datetime', now()->year)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_datetime')
            ->get(['id', 'start_datetime', 'end_datetime', 'title', 'type', 'status', 'visibility', 'user_id']);

        $calendarEvents = $allAppointments->map(fn($appointment): array => [
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
        ]);

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
     * Get user's public schedule for a specific month.
     */
    public function schedule(Request $request, User $user): JsonResponse
    {
        $month = $request->get('month', now()->format('Y-m'));

        try {
            // Get all appointments for the month (both public and private for complete agenda view)
            $appointments = Appointment::with(['organizer:id,first_name,last_name,email'])
                ->forUser($user)
                ->whereYear('start_datetime', substr((string) $month, 0, 4))
                ->whereMonth('start_datetime', substr((string) $month, 5, 2))
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_datetime')
                ->get(['id', 'start_datetime', 'end_datetime', 'title', 'type', 'status', 'visibility', 'user_id']);

            $calendarEvents = $appointments->map(fn($appointment): array => [
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
            ]);

            return response()->json([
                'success' => true,
                'month' => $month,
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                ],
                'appointments' => $calendarEvents,
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
