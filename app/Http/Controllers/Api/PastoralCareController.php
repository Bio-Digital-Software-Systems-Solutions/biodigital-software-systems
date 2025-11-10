<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PastoralCare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Http\Requests\PastoralCareStoreRequest;

class PastoralCareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'getPastors',
            'getAvailableSlots',
            'store',
            'confirm',
            'cancel'
        ]);
    }

    /**
     * Get list of available pastors for booking
     * Fixed: Updated to use correct phone_number column
     */
    public function getPastors(): JsonResponse
    {
        // Fixed: use phone_number instead of phone column
        $pastors = User::role('pastor')
            ->select('id', 'first_name', 'last_name', 'email', 'phone_number')
            ->with(['roles'])
            ->get()
            ->map(function ($pastor) {
                return [
                    'id' => $pastor->id,
                    'name' => $pastor->first_name . ' ' . $pastor->last_name,
                    'email' => $pastor->email,
                    'phone' => $pastor->phone_number,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pastors
        ]);
    }

    /**
     * Get available time slots for a pastor on a specific date
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pastor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:180',
        ]);

        // Verify the user is a pastor
        $pastor = User::findOrFail($validated['pastor_id']);
        if (!$pastor->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a pastor'
            ], 400);
        }

        $slots = PastoralCare::getAvailableTimeSlots(
            $validated['pastor_id'],
            $validated['date'],
            $validated['duration'] ?? 60
        );

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $validated['date'],
                'slots' => $slots
            ]
        ]);
    }

    /**
     * Book a new appointment (public endpoint)
     */
    public function store(PastoralCareStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Note: pastor verification and time slot availability are handled in the FormRequest

        // Combine date and time
        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'] . ' ' . $validated['appointment_time']
        );

        $appointment = PastoralCare::create([
            'pastor_id' => $validated['pastor_id'],
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'] ?? null,
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        // Load relationships for response
        $appointment->load('pastor');

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous créé avec succès. Un email de confirmation va vous être envoyé.',
            'data' => [
                'uuid' => $appointment->uuid,
                'appointment' => [
                    'id' => $appointment->id,
                    'uuid' => $appointment->uuid,
                    'client_name' => $appointment->client_name,
                    'client_email' => $appointment->client_email,
                    'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                    'appointment_time' => $appointment->appointment_time->format('H:i'),
                    'duration_minutes' => $appointment->duration_minutes,
                    'location_type' => $appointment->location_type,
                    'status' => $appointment->status,
                    'pastor' => [
                        'name' => $appointment->pastor->first_name . ' ' . $appointment->pastor->last_name,
                        'email' => $appointment->pastor->email,
                    ]
                ]
            ]
        ], 201);
    }

    /**
     * Display appointments for authenticated pastor
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required'
            ], 403);
        }

        $query = PastoralCare::with(['user', 'pastor'])
            ->forPastor($user->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->paginate($request->input('per_page', 15));

        $stats = [
            'pending' => PastoralCare::forPastor($user->id)->pending()->count(),
            'confirmed' => PastoralCare::forPastor($user->id)->confirmed()->count(),
            'completed' => PastoralCare::forPastor($user->id)->completed()->count(),
            'cancelled' => PastoralCare::forPastor($user->id)->cancelled()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'stats' => $stats,
            'filters' => $request->only(['status', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Show specific appointment by UUID (public endpoint for confirmations)
     */
    public function show($uuid): JsonResponse
    {
        $appointment = PastoralCare::where('uuid', $uuid)
            ->with(['pastor'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $appointment->id,
                'uuid' => $appointment->uuid,
                'client_name' => $appointment->client_name,
                'client_email' => $appointment->client_email,
                'client_phone' => $appointment->client_phone,
                'appointment_date' => $appointment->appointment_date->format('d/m/Y'),
                'appointment_time' => $appointment->appointment_time->format('H:i'),
                'duration_minutes' => $appointment->duration_minutes,
                'location_type' => $appointment->location_type,
                'zoom_link' => $appointment->zoom_link,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'pastor_notes' => $appointment->pastor_notes,
                'pastor' => [
                    'name' => $appointment->pastor->first_name . ' ' . $appointment->pastor->last_name,
                    'email' => $appointment->pastor->email,
                ],
                'can_be_confirmed' => $appointment->can_be_confirmed,
                'can_be_cancelled' => $appointment->can_be_cancelled,
            ]
        ]);
    }

    /**
     * Confirm appointment via UUID (public endpoint)
     */
    public function confirm($uuid): JsonResponse
    {
        $appointment = PastoralCare::where('uuid', $uuid)->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        try {
            $appointment->confirm();
            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous confirmé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel appointment via UUID (public endpoint)
     */
    public function cancel(Request $request, $uuid): JsonResponse
    {
        $appointment = PastoralCare::where('uuid', $uuid)->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        try {
            $appointment->cancel($validated['cancellation_reason'] ?? null);
            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous annulé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update appointment (authenticated pastor only)
     */
    public function update(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required'
            ], 403);
        }

        $appointment = PastoralCare::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'zoom_link' => 'nullable|url',
            'notes' => 'nullable|string|max:1000',
            'pastor_notes' => 'nullable|string|max:2000',
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous mis à jour avec succès',
            'data' => $appointment
        ]);
    }

    /**
     * Delete appointment (authenticated pastor only)
     */
    public function destroy(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required'
            ], 403);
        }

        $appointment = PastoralCare::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous supprimé avec succès'
        ]);
    }

    /**
     * Mark appointment as completed (authenticated pastor only)
     */
    public function complete(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required'
            ], 403);
        }

        $appointment = PastoralCare::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        try {
            $appointment->complete();
            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous marqué comme terminé'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mark appointment as no-show (authenticated pastor only)
     */
    public function noShow(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required'
            ], 403);
        }

        $appointment = PastoralCare::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable'
            ], 404);
        }

        try {
            $appointment->markAsNoShow();
            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous marqué comme absence'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
