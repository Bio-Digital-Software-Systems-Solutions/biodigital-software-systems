<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareServiceStoreRequest;
use App\Mail\CareServiceNewAppointmentNotification;
use App\Mail\CareServiceStatusChangeNotification;
use App\Models\CareService;
use App\Models\CareServiceTheme;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CareServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'getPastors',
            'getAvailableDays',
            'getAllAvailableDays',
            'getAvailableSlots',
            'getAllAvailableSlots',
            'getThemes',
            'store',
            'confirm',
            'cancel',
            'show',
            'confirmByClient',
            'confirmByPastor',
            'getConfirmationStatus',
            // Proposal system public endpoints
            'submitProposal',
            'showProposal',
            'acceptCounterProposal',
            'rejectCounterProposal',
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
            ->map(fn ($pastor): array => [
                'id' => $pastor->id,
                'name' => $pastor->first_name.' '.$pastor->last_name,
                'email' => $pastor->email,
                'phone' => $pastor->phone_number,
            ]);

        return response()->json([
            'success' => true,
            'data' => $pastors,
        ]);
    }

    /**
     * Get list of available themes for booking
     */
    public function getThemes(): JsonResponse
    {
        $themes = CareServiceTheme::active()
            ->ordered()
            ->get()
            ->map(fn ($theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
                'slug' => $theme->slug,
                'description' => $theme->description,
                'color' => $theme->color,
                'icon' => $theme->icon,
            ]);

        return response()->json([
            'success' => true,
            'data' => $themes,
        ]);
    }

    /**
     * Get available days for a pastor within a date range
     */
    public function getAvailableDays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pastor_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Verify the user is a pastor
        $pastor = User::findOrFail($validated['pastor_id']);
        if (! $pastor->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a pastor',
            ], 400);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $availableDays = [];

        // Get pastor's availability settings
        $availabilities = \App\Models\CareServiceAvailability::where('pastor_id', $validated['pastor_id'])
            ->active()
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'available_days' => [],
                ],
            ]);
        }

        // Check each date in the range
        $current = $startDate->copy();
        while ($current <= $endDate) {
            // Check if pastor has availability for this date
            $hasAvailability = false;

            foreach ($availabilities as $availability) {
                if ($availability->appliesTo($current)) {
                    $hasAvailability = true;
                    break;
                }
            }

            // If pastor has availability, check if there are any free slots
            if ($hasAvailability) {
                $slots = CareService::getAvailableTimeSlots(
                    $validated['pastor_id'],
                    $current->toDateString(),
                    60 // Default duration for checking availability
                );

                if ($slots !== []) {
                    $availableDays[] = [
                        'date' => $current->toDateString(),
                        'day_name' => $current->locale('fr')->format('D'),
                        'full_date' => $current->locale('fr')->format('l j F Y'),
                        'slots_count' => count($slots),
                    ];
                }
            }

            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available_days' => $availableDays,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
        ]);
    }

    /**
     * Get available days from ALL pastors within a date range
     * Used when user cannot select a specific pastor
     */
    public function getAllAvailableDays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Get all pastors
        $pastors = User::role('pastor')->pluck('id')->toArray();

        if (empty($pastors)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'available_days' => [],
                ],
            ]);
        }

        // Get all availabilities for all pastors
        $availabilities = \App\Models\CareServiceAvailability::whereIn('pastor_id', $pastors)
            ->active()
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'available_days' => [],
                ],
            ]);
        }

        $availableDays = [];

        // Check each date in the range
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $totalSlotsForDay = 0;
            $pastorsWithSlots = [];

            // Check all pastors for this date
            foreach ($pastors as $pastorId) {
                // Check if pastor has availability for this date
                $hasAvailability = false;
                foreach ($availabilities as $availability) {
                    if ($availability->pastor_id == $pastorId && $availability->appliesTo($current)) {
                        $hasAvailability = true;
                        break;
                    }
                }

                // If pastor has availability, check if there are any free slots
                if ($hasAvailability) {
                    $slots = CareService::getAvailableTimeSlots(
                        $pastorId,
                        $current->toDateString(),
                        60 // Default duration for checking availability
                    );

                    if ($slots !== []) {
                        $totalSlotsForDay += count($slots);
                        $pastorsWithSlots[] = $pastorId;
                    }
                }
            }

            if ($totalSlotsForDay > 0) {
                $availableDays[] = [
                    'date' => $current->toDateString(),
                    'day_name' => $current->locale('fr')->format('D'),
                    'full_date' => $current->locale('fr')->format('l j F Y'),
                    'slots_count' => $totalSlotsForDay,
                    'pastors_count' => count($pastorsWithSlots),
                ];
            }

            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available_days' => $availableDays,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
        ]);
    }

    /**
     * Get available time slots from ALL pastors on a specific date
     * Used when user cannot select a specific pastor
     */
    public function getAllAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:180',
        ]);

        // Get all pastors
        $pastors = User::role('pastor')
            ->select('id', 'first_name', 'last_name', 'email', 'phone_number')
            ->get();

        $allSlots = [];
        $duration = $validated['duration'] ?? 60;

        foreach ($pastors as $pastor) {
            $slots = CareService::getAvailableTimeSlots(
                $pastor->id,
                $validated['date'],
                $duration
            );

            foreach ($slots as $time) {
                $allSlots[] = [
                    'time' => $time,
                    'pastor_id' => $pastor->id,
                ];
            }
        }

        // Sort by time
        usort($allSlots, fn (array $a, array $b): int => strcmp((string) $a['time'], (string) $b['time']));

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $validated['date'],
                'slots' => $allSlots,
            ],
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
        if (! $pastor->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a pastor',
            ], 400);
        }

        $slots = CareService::getAvailableTimeSlots(
            $validated['pastor_id'],
            $validated['date'],
            $validated['duration'] ?? 60
        );

        // Get consultation mode from pastor's availability for this date
        $currentDate = \Carbon\Carbon::parse($validated['date']);
        $dayOfWeek = $currentDate->dayOfWeek;

        $availability = \App\Models\CareServiceAvailability::where('pastor_id', $validated['pastor_id'])
            ->active()
            ->where(function ($query) use ($currentDate, $dayOfWeek): void {
                $query->where(function ($q) use ($dayOfWeek): void {
                    $q->where('type', 'weekly')
                        ->where('day_of_week', $dayOfWeek);
                })
                    ->orWhere(function ($q) use ($currentDate): void {
                        $q->where('type', 'specific')
                            ->where('specific_date', $currentDate->toDateString());
                    });
            })
            ->first();

        $consultationMode = $availability ? $availability->consultation_mode : 'in_person';

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $validated['date'],
                'slots' => $slots,
                'consultation_mode' => $consultationMode,
            ],
        ]);
    }

    /**
     * Book a new appointment (public endpoint)
     */
    public function store(CareServiceStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Note: pastor verification and time slot availability are handled in the FormRequest

        // Combine date and time
        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        $appointment = CareService::create([
            'pastor_id' => $validated['pastor_id'],
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        // Attach themes
        if (! empty($validated['theme_ids'])) {
            $appointment->themes()->sync($validated['theme_ids']);
        }

        // Load relationships for response
        $appointment->load(['pastor', 'themes']);

        // Send notifications
        $this->sendAppointmentNotifications($appointment);

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
                        'name' => $appointment->pastor->first_name.' '.$appointment->pastor->last_name,
                        'email' => $appointment->pastor->email,
                    ],
                ],
            ],
        ], 201);
    }

    /**
     * Display appointments for authenticated pastor
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $query = CareService::with(['user', 'pastor'])
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
            'pending' => CareService::forPastor($user->id)->pending()->count(),
            'confirmed' => CareService::forPastor($user->id)->confirmed()->count(),
            'completed' => CareService::forPastor($user->id)->completed()->count(),
            'cancelled' => CareService::forPastor($user->id)->cancelled()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'stats' => $stats,
            'filters' => $request->only(['status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show specific appointment by UUID (public endpoint for confirmations)
     */
    public function show($uuid): JsonResponse
    {
        $appointment = CareService::where('uuid', $uuid)
            ->with(['pastor'])
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
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
                    'name' => $appointment->pastor->first_name.' '.$appointment->pastor->last_name,
                    'email' => $appointment->pastor->email,
                ],
                'can_be_confirmed' => $appointment->can_be_confirmed,
                'can_be_cancelled' => $appointment->can_be_cancelled,
            ],
        ]);
    }

    /**
     * Confirm appointment via UUID (public endpoint)
     */
    public function confirm($uuid): JsonResponse
    {
        $appointment = CareService::where('uuid', $uuid)->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        try {
            $appointment->confirm();

            // Send notifications to client
            $this->sendStatusChangeNotifications($appointment, 'confirmed');

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous confirmé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel appointment via UUID (public endpoint)
     */
    public function cancel(Request $request, $uuid): JsonResponse
    {
        $appointment = CareService::where('uuid', $uuid)->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        try {
            $appointment->cancel($validated['cancellation_reason'] ?? null);

            // Send notifications to client
            $this->sendStatusChangeNotifications($appointment, 'cancelled');

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous annulé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm appointment by client using their unique token (public endpoint)
     */
    public function confirmByClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $appointment = CareService::findByClientToken($validated['token']);

        if (! $appointment instanceof \App\Models\CareService) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable ou token invalide',
            ], 404);
        }

        try {
            $appointment->confirmByClient($validated['token']);

            // Check if both parties have now confirmed
            $appointment->refresh();

            $message = 'Votre confirmation a été enregistrée avec succès.';
            if ($appointment->is_fully_confirmed) {
                $message = 'Rendez-vous confirmé par les deux parties. Le rendez-vous est maintenant validé.';
                // Send final confirmation notifications
                $this->sendDualConfirmationNotifications($appointment);
            } else {
                // Notify the other party that client has confirmed
                $this->sendPartialConfirmationNotification($appointment, 'client');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'confirmation_status' => $appointment->confirmation_status,
                    'status' => $appointment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm appointment by pastor using their unique token (public endpoint)
     */
    public function confirmByPastor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $appointment = CareService::findByPastorToken($validated['token']);

        if (! $appointment instanceof \App\Models\CareService) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable ou token invalide',
            ], 404);
        }

        try {
            $appointment->confirmByPastor($validated['token']);

            // Check if both parties have now confirmed
            $appointment->refresh();

            $message = 'Votre confirmation a été enregistrée avec succès.';
            if ($appointment->is_fully_confirmed) {
                $message = 'Rendez-vous confirmé par les deux parties. Le rendez-vous est maintenant validé.';
                // Send final confirmation notifications
                $this->sendDualConfirmationNotifications($appointment);
            } else {
                // Notify the other party that pastor has confirmed
                $this->sendPartialConfirmationNotification($appointment, 'pastor');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'confirmation_status' => $appointment->confirmation_status,
                    'status' => $appointment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get confirmation status for an appointment (public endpoint)
     */
    public function getConfirmationStatus($uuid): JsonResponse
    {
        $appointment = CareService::where('uuid', $uuid)->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $appointment->uuid,
                'status' => $appointment->status,
                'confirmation_status' => $appointment->confirmation_status,
                'appointment_date' => $appointment->appointment_date->format('d/m/Y'),
                'appointment_time' => $appointment->appointment_time->format('H:i'),
            ],
        ]);
    }

    /**
     * Update appointment (authenticated pastor only)
     */
    public function update(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $appointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'zoom_link' => 'nullable|url',
            'notes' => 'nullable|string|max:1000',
            'pastor_notes' => 'nullable|string|max:2000',
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        // Handle pastor_notes separately to add with timestamp
        $pastorNoteContent = $validated['pastor_notes'] ?? null;
        unset($validated['pastor_notes']);

        // Update other fields
        if (! empty($validated)) {
            $appointment->update($validated);
        }

        // Add new pastor note with timestamp
        if ($pastorNoteContent) {
            $appointment->addPastorNote($pastorNoteContent);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous mis à jour avec succès',
            'data' => $appointment->fresh(),
        ]);
    }

    /**
     * Delete appointment (authenticated pastor only)
     */
    public function destroy(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $appointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous supprimé avec succès',
        ]);
    }

    /**
     * Mark appointment as completed (authenticated pastor only)
     */
    public function complete(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $appointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        try {
            $appointment->complete();

            // Send notifications to both client and pastor
            $this->sendStatusChangeNotifications($appointment, 'completed');

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous marqué comme terminé',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark appointment as no-show (authenticated pastor only)
     */
    public function noShow(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $appointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        try {
            $appointment->markAsNoShow();

            // Send notifications to both client and pastor
            $this->sendStatusChangeNotifications($appointment, 'no_show');

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous marqué comme absence',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send notifications for new appointment (email + internal messages)
     */
    private function sendAppointmentNotifications(CareService $appointment): void
    {
        try {
            $pastor = $appointment->pastor;

            // 1. Send email notification to pastor
            Mail::to($pastor->email)->send(new CareServiceNewAppointmentNotification($appointment));

            // 2. Send internal message to pastor
            $pastorMessageContent = "Vous avez un nouveau rendez-vous de soin pastoral :\n\n".
                '📅 Date : '.$appointment->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$appointment->appointment_time->format('H:i')."\n".
                '⌛ Durée : '.$appointment->duration_minutes." minutes\n".
                '👤 Client : '.($appointment->client_name ?? 'Non renseigné')."\n".
                '📧 Email : '.($appointment->client_email ?? 'Non renseigné')."\n".
                '📱 Téléphone : '.($appointment->client_phone ?? 'Non renseigné')."\n".
                '📍 Type : '.$this->getLocationTypeLabel($appointment->location_type)."\n";

            if ($appointment->zoom_link) {
                $pastorMessageContent .= '🔗 Lien Zoom : '.$appointment->zoom_link."\n";
            }

            if ($appointment->notes) {
                $pastorMessageContent .= "\n📝 Notes : ".$appointment->notes;
            }

            Message::create([
                'subject' => 'Nouveau rendez-vous de soin pastoral - '.$appointment->appointment_date->format('d/m/Y'),
                'content' => $pastorMessageContent,
                'sender_id' => 1, // System user
                'receiver_id' => $pastor->id,
                'type' => 'system',
                'recipient_type' => 'user',
            ]);

            // 3. Send email notification to client (if email provided)
            if ($appointment->client_email) {
                // For now, we'll send a simple notification - can be enhanced with a dedicated Mailable later
                $clientSubject = 'Confirmation de rendez-vous - '.config('app.name');
                $clientContent = 'Bonjour '.($appointment->client_name ?? '').",\n\n".
                    "Votre demande de rendez-vous de soin pastoral a été enregistrée avec succès.\n\n".
                    "Détails de votre rendez-vous :\n".
                    '📅 Date : '.$appointment->appointment_date->format('d/m/Y')."\n".
                    '⏰ Heure : '.$appointment->appointment_time->format('H:i')."\n".
                    '⌛ Durée : '.$appointment->duration_minutes." minutes\n".
                    '👨‍💼 Pasteur : '.$pastor->first_name.' '.$pastor->last_name."\n".
                    '📍 Type : '.$this->getLocationTypeLabel($appointment->location_type)."\n";

                if ($appointment->zoom_link) {
                    $clientContent .= '🔗 Lien Zoom : '.$appointment->zoom_link."\n";
                }

                $clientContent .= "\nVotre rendez-vous est en attente de confirmation. Vous recevrez un email de confirmation une fois que le pasteur aura validé le créneau.\n\n".
                    "Cordialement,\nL'équipe ".config('app.name');

                // Simple email for client
                Mail::raw($clientContent, function ($message) use ($appointment, $clientSubject): void {
                    $message->to($appointment->client_email)
                        ->subject($clientSubject)
                        ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
                });

                // Track that the notification email was sent
                $appointment->update(['notification_email_sent_at' => now()]);
            }

            // 4. Send internal message to client (if they are a registered user)
            $clientUser = User::where('email', $appointment->client_email)->first();
            if ($clientUser) {
                $clientMessageContent = "Votre demande de rendez-vous de soin pastoral a été enregistrée :\n\n".
                    '📅 Date : '.$appointment->appointment_date->format('d/m/Y')."\n".
                    '⏰ Heure : '.$appointment->appointment_time->format('H:i')."\n".
                    '⌛ Durée : '.$appointment->duration_minutes." minutes\n".
                    '👨‍💼 Pasteur : '.$pastor->first_name.' '.$pastor->last_name."\n".
                    '📍 Type : '.$this->getLocationTypeLabel($appointment->location_type)."\n";

                if ($appointment->zoom_link) {
                    $clientMessageContent .= '🔗 Lien Zoom : '.$appointment->zoom_link."\n";
                }

                $clientMessageContent .= "\n⏳ Statut : En attente de confirmation\n".
                    'Vous recevrez une notification une fois le rendez-vous confirmé par le pasteur.';

                Message::create([
                    'subject' => 'Demande de rendez-vous enregistrée - '.$appointment->appointment_date->format('d/m/Y'),
                    'content' => $clientMessageContent,
                    'sender_id' => 1, // System user
                    'receiver_id' => $clientUser->id,
                    'type' => 'system',
                    'recipient_type' => 'user',
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't break the appointment creation
            \Log::error('Failed to send appointment notifications: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get human-readable label for location type
     */
    private function getLocationTypeLabel(string $locationType): string
    {
        return match ($locationType) {
            'in_person' => 'En présentiel à l\'église',
            'zoom' => 'Visioconférence (Zoom)',
            'hybrid' => 'Hybride (au choix du pasteur)',
            default => $locationType,
        };
    }

    /**
     * Send status change notifications to both the client and pastor
     */
    private function sendStatusChangeNotifications(CareService $appointment, string $newStatus): void
    {
        try {
            // Load relationships
            $appointment->load(['pastor', 'user']);

            // Prepare notification content based on status
            $statusTexts = [
                'pending' => [
                    'subject' => 'Votre rendez-vous de soin pastoral est en attente',
                    'action' => 'mis en attente',
                ],
                'confirmed' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été confirmé',
                    'action' => 'confirmé',
                ],
                'completed' => [
                    'subject' => 'Votre rendez-vous de soin pastoral est terminé',
                    'action' => 'marqué comme terminé',
                ],
                'cancelled' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été annulé',
                    'action' => 'annulé',
                ],
                'no_show' => [
                    'subject' => 'Rendez-vous pastoral - Non-présentation',
                    'action' => 'marqué comme non présenté',
                ],
            ];

            $statusInfo = $statusTexts[$newStatus] ?? null;
            if (! $statusInfo) {
                return;
            }

            // Send email notification to client if email is available
            if ($appointment->client_email) {
                Mail::to($appointment->client_email)
                    ->send(new CareServiceStatusChangeNotification($appointment, $newStatus, 'client'));
            }

            // Send email notification to pastor
            if ($appointment->pastor && $appointment->pastor->email) {
                Mail::to($appointment->pastor->email)
                    ->send(new CareServiceStatusChangeNotification($appointment, $newStatus, 'pastor'));
            }

            // Send platform message to client if they have an account
            if ($appointment->user_id) {
                $messageContent = "Votre rendez-vous de soin pastoral avec {$appointment->pastor->first_name} {$appointment->pastor->last_name} ";
                $messageContent .= 'prévu le '.$appointment->appointment_date->format('d/m/Y');
                $messageContent .= ' à '.$appointment->appointment_time->format('H:i');
                $messageContent .= " a été {$statusInfo['action']}.";

                if ($newStatus === 'cancelled' && $appointment->cancellation_reason) {
                    $messageContent .= "\n\nRaison: ".$appointment->cancellation_reason;
                }

                Message::create([
                    'sender_id' => $appointment->pastor_id,
                    'receiver_id' => $appointment->user_id,
                    'subject' => $statusInfo['subject'],
                    'content' => $messageContent,
                    'type' => 'system',
                ]);
            }

            // Send platform message to pastor
            Message::create([
                'sender_id' => $appointment->user_id ?? $appointment->pastor_id,
                'receiver_id' => $appointment->pastor_id,
                'subject' => "Rendez-vous pastoral {$statusInfo['action']} - {$appointment->client_name}",
                'content' => "Le rendez-vous de soin pastoral avec {$appointment->client_name} prévu le "
                    .$appointment->appointment_date->format('d/m/Y')
                    .' à '.$appointment->appointment_time->format('H:i')
                    ." a été {$statusInfo['action']}.",
                'type' => 'system',
            ]);

        } catch (\Exception $e) {
            // Log the error but don't fail the status change
            \Log::error('Failed to send care service status change notifications: '.$e->getMessage());
        }
    }

    /**
     * Create a follow-up appointment from an existing appointment (authenticated pastor only)
     */
    public function createFollowUp(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        // Find the parent appointment
        $parentAppointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $parentAppointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous parent introuvable',
            ], 404);
        }

        // Validate the follow-up request
        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:30|max:180',
            'location_type' => 'nullable|in:in_person,zoom,hybrid',
            'zoom_link' => 'nullable|url',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Combine date and time
        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        // Validate time slot availability
        $durationMinutes = $validated['duration_minutes'] ?? $parentAppointment->duration_minutes;
        if (! CareService::isTimeSlotAvailable($user->id, $appointmentDateTime, $durationMinutes)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce créneau horaire n\'est pas disponible',
            ], 422);
        }

        // Create the follow-up appointment with client info from parent
        $followUpAppointment = CareService::create([
            'pastor_id' => $user->id,
            'parent_id' => $parentAppointment->id,
            'user_id' => $parentAppointment->user_id,
            'client_name' => $parentAppointment->client_name,
            'client_email' => $parentAppointment->client_email,
            'client_phone' => $parentAppointment->client_phone,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $durationMinutes,
            'location_type' => $validated['location_type'] ?? $parentAppointment->location_type,
            'zoom_link' => $validated['zoom_link'] ?? $parentAppointment->zoom_link,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        // Load relationships for response
        $followUpAppointment->load('pastor');

        // Send notifications
        $this->sendFollowUpNotifications($followUpAppointment, $parentAppointment);

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous de suivi créé avec succès. Une notification a été envoyée au client.',
            'data' => [
                'uuid' => $followUpAppointment->uuid,
                'appointment' => [
                    'id' => $followUpAppointment->id,
                    'uuid' => $followUpAppointment->uuid,
                    'client_name' => $followUpAppointment->client_name,
                    'client_email' => $followUpAppointment->client_email,
                    'appointment_date' => $followUpAppointment->appointment_date->format('Y-m-d'),
                    'appointment_time' => $followUpAppointment->appointment_time->format('H:i'),
                    'duration_minutes' => $followUpAppointment->duration_minutes,
                    'location_type' => $followUpAppointment->location_type,
                    'status' => $followUpAppointment->status,
                    'parent_id' => $parentAppointment->id,
                    'pastor' => [
                        'name' => $followUpAppointment->pastor->first_name.' '.$followUpAppointment->pastor->last_name,
                        'email' => $followUpAppointment->pastor->email,
                    ],
                ],
            ],
        ], 201);
    }

    /**
     * Send notifications for follow-up appointment (dual confirmation system)
     * Both client and pastor receive emails with their own confirmation links
     */
    private function sendFollowUpNotifications(CareService $appointment, CareService $parentAppointment): void
    {
        try {
            $pastor = $appointment->pastor;

            // 1. Send email notification to client with their confirmation token
            if ($appointment->client_email) {
                Mail::to($appointment->client_email)
                    ->send(new \App\Mail\CareServiceFollowUpNotification($appointment, $parentAppointment));
            }

            // 2. Send email notification to pastor with their confirmation token
            if ($pastor && $pastor->email) {
                Mail::to($pastor->email)
                    ->send(new \App\Mail\CareServicePastorFollowUpNotification($appointment, $parentAppointment));
            }

            // 3. Send internal message to pastor
            $pastorMessageContent = "Vous avez créé un rendez-vous de suivi :\n\n".
                '📅 Date : '.$appointment->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$appointment->appointment_time->format('H:i')."\n".
                '⌛ Durée : '.$appointment->duration_minutes." minutes\n".
                '👤 Client : '.($appointment->client_name ?? 'Non renseigné')."\n".
                '📧 Email : '.($appointment->client_email ?? 'Non renseigné')."\n".
                '📍 Type : '.$this->getLocationTypeLabel($appointment->location_type)."\n".
                "\n🔗 Suite au rendez-vous du ".$parentAppointment->appointment_date->format('d/m/Y')."\n".
                "\n⚠️ Double confirmation requise : Vous et le client devez chacun confirmer le rendez-vous via le lien reçu par email.";

            Message::create([
                'subject' => 'Rendez-vous de suivi créé - Confirmation requise - '.$appointment->appointment_date->format('d/m/Y'),
                'content' => $pastorMessageContent,
                'sender_id' => 1, // System user
                'receiver_id' => $pastor->id,
                'type' => 'system',
                'recipient_type' => 'user',
            ]);

            // 4. Send internal message to client if they have an account
            $clientUser = User::where('email', $appointment->client_email)->first();
            if ($clientUser) {
                $clientMessageContent = "Un rendez-vous de suivi a été planifié pour vous :\n\n".
                    '📅 Date : '.$appointment->appointment_date->format('d/m/Y')."\n".
                    '⏰ Heure : '.$appointment->appointment_time->format('H:i')."\n".
                    '⌛ Durée : '.$appointment->duration_minutes." minutes\n".
                    '👨‍💼 Pasteur : '.$pastor->first_name.' '.$pastor->last_name."\n".
                    '📍 Type : '.$this->getLocationTypeLabel($appointment->location_type)."\n".
                    "\n⏳ Statut : En attente de double confirmation\n".
                    "⚠️ Vous et le pasteur devez chacun confirmer le rendez-vous.\n".
                    'Veuillez confirmer votre présence via le lien reçu par email.';

                Message::create([
                    'subject' => 'Rendez-vous de suivi planifié - Confirmation requise - '.$appointment->appointment_date->format('d/m/Y'),
                    'content' => $clientMessageContent,
                    'sender_id' => 1, // System user
                    'receiver_id' => $clientUser->id,
                    'type' => 'system',
                    'recipient_type' => 'user',
                ]);
            }

        } catch (\Exception $e) {
            // Log the error but don't fail the appointment creation
            \Log::error('Failed to send care service follow-up notifications: '.$e->getMessage());
        }
    }

    /**
     * Generate a report for the care service appointment (authenticated pastor only)
     */
    public function generateReport(Request $request, $uuid)
    {
        $user = $request->user();

        if (! $user->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Pastor access required',
            ], 403);
        }

        $appointment = CareService::where('uuid', $uuid)
            ->forPastor($user->id)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable',
            ], 404);
        }

        $format = $request->input('format', 'pdf');

        if (! in_array($format, ['pdf', 'excel', 'word'])) {
            return response()->json([
                'success' => false,
                'message' => 'Format non supporté. Formats disponibles: pdf, excel, word',
            ], 400);
        }

        try {
            $reportService = new \App\Services\CareServiceReportService($appointment);

            return match ($format) {
                'pdf' => $reportService->generatePdf(),
                'excel' => $reportService->generateExcel(),
                'word' => $reportService->generateWord(),
            };
        } catch (\Exception $e) {
            \Log::error('Failed to generate care service report: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notifications when both parties have confirmed the appointment
     */
    private function sendDualConfirmationNotifications(CareService $appointment): void
    {
        try {
            $appointment->load(['pastor', 'user']);

            // Send final confirmation email to client
            if ($appointment->client_email) {
                Mail::to($appointment->client_email)
                    ->send(new \App\Mail\CareServiceDualConfirmationNotification($appointment, 'client'));
            }

            // Send final confirmation email to pastor
            if ($appointment->pastor && $appointment->pastor->email) {
                Mail::to($appointment->pastor->email)
                    ->send(new \App\Mail\CareServiceDualConfirmationNotification($appointment, 'pastor'));
            }

            // Send platform message to client if they have an account
            if ($appointment->user_id) {
                Message::create([
                    'sender_id' => $appointment->pastor_id,
                    'receiver_id' => $appointment->user_id,
                    'subject' => 'Rendez-vous confirmé par les deux parties',
                    'content' => "Votre rendez-vous de soin pastoral avec {$appointment->pastor->first_name} {$appointment->pastor->last_name} "
                        .'prévu le '.$appointment->appointment_date->format('d/m/Y')
                        .' à '.$appointment->appointment_time->format('H:i')
                        .' a été confirmé par les deux parties. Le rendez-vous est maintenant validé.',
                    'type' => 'system',
                ]);
            }

            // Send platform message to pastor
            Message::create([
                'sender_id' => $appointment->user_id ?? 1,
                'receiver_id' => $appointment->pastor_id,
                'subject' => "Rendez-vous confirmé - {$appointment->client_name}",
                'content' => "Le rendez-vous de soin pastoral avec {$appointment->client_name} prévu le "
                    .$appointment->appointment_date->format('d/m/Y')
                    .' à '.$appointment->appointment_time->format('H:i')
                    .' a été confirmé par les deux parties. Le rendez-vous est maintenant validé.',
                'type' => 'system',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send dual confirmation notifications: '.$e->getMessage());
        }
    }

    /**
     * Send notification when one party has confirmed (to notify the other party)
     */
    private function sendPartialConfirmationNotification(CareService $appointment, string $confirmedBy): void
    {
        try {
            $appointment->load(['pastor', 'user']);

            if ($confirmedBy === 'client') {
                // Client confirmed - notify pastor
                if ($appointment->pastor && $appointment->pastor->email) {
                    Mail::to($appointment->pastor->email)
                        ->send(new \App\Mail\CareServicePartialConfirmationNotification($appointment, 'pastor', $confirmedBy));
                }

                // Send platform message to pastor
                Message::create([
                    'sender_id' => $appointment->user_id ?? 1,
                    'receiver_id' => $appointment->pastor_id,
                    'subject' => "Confirmation client reçue - {$appointment->client_name}",
                    'content' => "{$appointment->client_name} a confirmé sa présence au rendez-vous du "
                        .$appointment->appointment_date->format('d/m/Y')
                        .' à '.$appointment->appointment_time->format('H:i')
                        .'. En attente de votre confirmation.',
                    'type' => 'system',
                ]);
            } else {
                // Pastor confirmed - notify client
                if ($appointment->client_email) {
                    Mail::to($appointment->client_email)
                        ->send(new \App\Mail\CareServicePartialConfirmationNotification($appointment, 'client', $confirmedBy));
                }

                // Send platform message to client if they have an account
                if ($appointment->user_id) {
                    Message::create([
                        'sender_id' => $appointment->pastor_id,
                        'receiver_id' => $appointment->user_id,
                        'subject' => 'Confirmation du pasteur reçue',
                        'content' => "{$appointment->pastor->first_name} {$appointment->pastor->last_name} a confirmé le rendez-vous du "
                            .$appointment->appointment_date->format('d/m/Y')
                            .' à '.$appointment->appointment_time->format('H:i')
                            .'. En attente de votre confirmation.',
                        'type' => 'system',
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send partial confirmation notification: '.$e->getMessage());
        }
    }

    // ==========================================
    // PROPOSAL SYSTEM ENDPOINTS
    // ==========================================

    /**
     * Submit a new appointment proposal (public endpoint)
     * Used when client wants to propose a specific date/time not in the available slots
     */
    public function submitProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:30|max:180',
            'location_type' => 'required|in:in_person,zoom,hybrid',
            'zoom_link' => 'nullable|url',
            'notes' => 'nullable|string|max:1000',
            'proposal_reason' => 'required|string|max:1000',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => 'required|integer|exists:care_service_themes,id',
        ]);

        // Combine date and time
        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        // Validate that the proposed time is within reasonable hours (8:00 - 20:00)
        $hour = (int) $appointmentDateTime->format('H');
        if ($hour < 8 || $hour >= 20) {
            return response()->json([
                'success' => false,
                'message' => 'L\'heure proposée doit être entre 08:00 et 20:00.',
            ], 422);
        }

        // Check if the date is not too far in the future (max 3 months)
        $maxDate = now()->addMonths(3)->endOfDay();
        if ($appointmentDateTime->gt($maxDate)) {
            return response()->json([
                'success' => false,
                'message' => 'La date proposée ne peut pas être à plus de 3 mois.',
            ], 422);
        }

        // Create the proposal without assigning a pastor yet
        // Pastor will be assigned when a care service agent accepts the proposal
        $proposal = CareService::create([
            'pastor_id' => User::role('pastor')->first()->id, // Temporary, will be reassigned
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'proposed',
            'is_proposal' => true,
            'proposal_reason' => $validated['proposal_reason'],
        ]);

        // Attach themes
        if (! empty($validated['theme_ids'])) {
            $proposal->themes()->sync($validated['theme_ids']);
        }

        // Send notifications to care service team
        $this->sendProposalNotifications($proposal);

        return response()->json([
            'success' => true,
            'message' => 'Votre proposition de rendez-vous a été soumise. Vous recevrez une réponse par email.',
            'data' => [
                'uuid' => $proposal->uuid,
                'proposal_token' => $proposal->proposal_token,
                'appointment' => [
                    'client_name' => $proposal->client_name,
                    'client_email' => $proposal->client_email,
                    'appointment_date' => $proposal->appointment_date->format('Y-m-d'),
                    'appointment_time' => $proposal->appointment_time->format('H:i'),
                    'duration_minutes' => $proposal->duration_minutes,
                    'location_type' => $proposal->location_type,
                    'status' => $proposal->status,
                    'proposal_response_status' => $proposal->proposal_response_status,
                ],
            ],
        ], 201);
    }

    /**
     * Show a proposal by token (public endpoint)
     */
    public function showProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $proposal = CareService::findByProposalToken($validated['token']);

        if (! $proposal instanceof \App\Models\CareService) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable ou token invalide',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $proposal->uuid,
                'client_name' => $proposal->client_name,
                'client_email' => $proposal->client_email,
                'appointment_date' => $proposal->appointment_date->format('d/m/Y'),
                'appointment_time' => $proposal->appointment_time->format('H:i'),
                'duration_minutes' => $proposal->duration_minutes,
                'location_type' => $proposal->location_type,
                'status' => $proposal->status,
                'proposal_reason' => $proposal->proposal_reason,
                'proposal_response_status' => $proposal->proposal_response_status,
                'proposal_status_label' => $proposal->proposal_status_label,
                'counter_proposed_date' => $proposal->counter_proposed_date?->format('d/m/Y'),
                'counter_proposed_time' => $proposal->counter_proposed_time,
                'counter_proposal_message' => $proposal->counter_proposal_message,
                'has_counter_proposal' => $proposal->hasCounterProposal(),
            ],
        ]);
    }

    /**
     * Get all pending proposals (authenticated care service/Admin only)
     */
    public function getPendingProposals(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'super-admin', 'care-service-agent', 'pastor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Care Service access required',
            ], 403);
        }

        $query = CareService::with(['careServiceAgent'])
            ->isProposal()
            ->orderBy('proposal_submitted_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('proposal_response_status', $request->status);
        } else {
            // Default to pending proposals
            $query->where('proposal_response_status', 'pending');
        }

        $proposals = $query->paginate($request->input('per_page', 15));

        $stats = [
            'pending' => CareService::isProposal()->where('proposal_response_status', 'pending')->count(),
            'counter_proposed' => CareService::isProposal()->where('proposal_response_status', 'counter_proposed')->count(),
            'accepted' => CareService::isProposal()->where('proposal_response_status', 'accepted')->count(),
            'rejected' => CareService::isProposal()->where('proposal_response_status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $proposals,
            'stats' => $stats,
        ]);
    }

    /**
     * Accept a proposal and assign a pastor (authenticated care service/Admin only)
     */
    public function acceptProposal(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'super-admin', 'care-service-agent', 'pastor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Care Service access required',
            ], 403);
        }

        $proposal = CareService::where('uuid', $uuid)->isProposal()->first();

        if (! $proposal) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable',
            ], 404);
        }

        // Check if proposal is already processed before any other checks
        if ($proposal->proposal_response_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette proposition a déjà été traitée.',
            ], 400);
        }

        $validated = $request->validate([
            'pastor_id' => 'required|exists:users,id',
        ]);

        // Verify the selected user is a pastor
        $pastor = User::findOrFail($validated['pastor_id']);
        if (! $pastor->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur sélectionné n\'est pas un pasteur.',
            ], 422);
        }

        // Check if the pastor is available at the proposed time
        if (! CareService::isTimeSlotAvailable($validated['pastor_id'], $proposal->appointment_time, $proposal->duration_minutes)) {
            return response()->json([
                'success' => false,
                'message' => 'Le pasteur sélectionné n\'est pas disponible à ce créneau.',
            ], 422);
        }

        try {
            $proposal->acceptProposal($validated['pastor_id'], $user->id);

            // Send acceptance notification to client
            $this->sendProposalAcceptedNotification($proposal);

            // Send notification to assigned pastor
            $this->sendAppointmentNotifications($proposal->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Proposition acceptée. Le client et le pasteur ont été notifiés.',
                'data' => $proposal->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a proposal with optional counter-proposal (authenticated care service/Admin only)
     */
    public function rejectProposal(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'super-admin', 'care-service-agent', 'pastor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Care Service access required',
            ], 403);
        }

        $proposal = CareService::where('uuid', $uuid)->isProposal()->first();

        if (! $proposal) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable',
            ], 404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'counter_proposed_date' => 'nullable|date|after_or_equal:today',
            'counter_proposed_time' => 'nullable|date_format:H:i|required_with:counter_proposed_date',
            'counter_proposal_message' => 'nullable|string|max:1000',
        ]);

        try {
            $proposal->rejectProposal(
                $user->id,
                $validated['rejection_reason'],
                $validated['counter_proposed_date'] ?? null,
                $validated['counter_proposed_time'] ?? null,
                $validated['counter_proposal_message'] ?? null
            );

            // Send notification to client
            if ($proposal->hasCounterProposal()) {
                $this->sendCounterProposalNotification($proposal);
                $message = 'Contre-proposition envoyée. Le client a été notifié.';
            } else {
                $this->sendProposalRejectedNotification($proposal);
                $message = 'Proposition refusée. Le client a été notifié.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $proposal->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Client accepts the counter-proposal (public endpoint)
     */
    public function acceptCounterProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $proposal = CareService::findByProposalToken($validated['token']);

        if (! $proposal instanceof \App\Models\CareService) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable ou token invalide',
            ], 404);
        }

        try {
            $proposal->acceptCounterProposal($validated['token']);

            // Send notifications that the appointment is now pending
            $this->sendAppointmentNotifications($proposal->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Vous avez accepté la contre-proposition. Votre rendez-vous est maintenant en attente de confirmation.',
                'data' => [
                    'uuid' => $proposal->uuid,
                    'appointment_date' => $proposal->appointment_date->format('d/m/Y'),
                    'appointment_time' => $proposal->appointment_time->format('H:i'),
                    'status' => $proposal->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Client rejects the counter-proposal (public endpoint)
     */
    public function rejectCounterProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
            'reason' => 'nullable|string|max:500',
        ]);

        $proposal = CareService::findByProposalToken($validated['token']);

        if (! $proposal instanceof \App\Models\CareService) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable ou token invalide',
            ], 404);
        }

        try {
            $proposal->rejectCounterProposal($validated['token'], $validated['reason'] ?? null);

            // Notify care service team that client rejected the counter-proposal
            $this->sendCounterProposalRejectedNotification($proposal);

            return response()->json([
                'success' => true,
                'message' => 'Vous avez refusé la contre-proposition. Vous pouvez soumettre une nouvelle proposition si vous le souhaitez.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ==========================================
    // PROPOSAL NOTIFICATION METHODS
    // ==========================================

    /**
     * Send notifications for new proposal to care service team
     */
    private function sendProposalNotifications(CareService $proposal): void
    {
        try {
            // Get all care service agents and admins
            $agents = User::role(['admin', 'super-admin', 'care-service-agent'])->get();

            $messageContent = "Nouvelle proposition de rendez-vous reçue :\n\n".
                '👤 Client : '.$proposal->client_name."\n".
                '📧 Email : '.$proposal->client_email."\n".
                '📅 Date proposée : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure proposée : '.$proposal->appointment_time->format('H:i')."\n".
                '⌛ Durée : '.$proposal->duration_minutes." minutes\n".
                '📍 Type : '.$this->getLocationTypeLabel($proposal->location_type)."\n".
                "\n💬 Raison de la proposition :\n".$proposal->proposal_reason."\n".
                "\n🔗 Veuillez traiter cette demande dans le tableau de bord du service de soin.";

            foreach ($agents as $agent) {
                // Send internal message
                Message::create([
                    'subject' => 'Nouvelle proposition de rendez-vous - '.$proposal->client_name,
                    'content' => $messageContent,
                    'sender_id' => 1, // System user
                    'receiver_id' => $agent->id,
                    'type' => 'system',
                    'recipient_type' => 'user',
                ]);

                // Send email notification
                Mail::raw($messageContent, function ($message) use ($agent, $proposal): void {
                    $message->to($agent->email)
                        ->subject('Nouvelle proposition de rendez-vous - '.$proposal->client_name)
                        ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
                });
            }

            // Send confirmation email to client
            $clientContent = "Bonjour {$proposal->client_name},\n\n".
                "Votre proposition de rendez-vous a été soumise avec succès.\n\n".
                "Détails de votre proposition :\n".
                '📅 Date : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$proposal->appointment_time->format('H:i')."\n".
                '⌛ Durée : '.$proposal->duration_minutes." minutes\n".
                '📍 Type : '.$this->getLocationTypeLabel($proposal->location_type)."\n\n".
                "Notre équipe examinera votre proposition et vous répondra dans les plus brefs délais.\n\n".
                "Cordialement,\nL'équipe ".config('app.name');

            Mail::raw($clientContent, function ($message) use ($proposal): void {
                $message->to($proposal->client_email)
                    ->subject('Proposition de rendez-vous reçue - '.config('app.name'))
                    ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
            });

        } catch (\Exception $e) {
            \Log::error('Failed to send proposal notifications: '.$e->getMessage());
        }
    }

    /**
     * Send notification when proposal is accepted
     */
    private function sendProposalAcceptedNotification(CareService $proposal): void
    {
        try {
            $proposal->load('pastor');

            $clientContent = "Bonjour {$proposal->client_name},\n\n".
                "Bonne nouvelle ! Votre proposition de rendez-vous a été acceptée.\n\n".
                "Détails de votre rendez-vous :\n".
                '📅 Date : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$proposal->appointment_time->format('H:i')."\n".
                '⌛ Durée : '.$proposal->duration_minutes." minutes\n".
                '👨‍💼 Pasteur : '.$proposal->pastor->first_name.' '.$proposal->pastor->last_name."\n".
                '📍 Type : '.$this->getLocationTypeLabel($proposal->location_type)."\n\n".
                "Vous recevrez un email de confirmation avec les détails complets.\n\n".
                "Cordialement,\nL'équipe ".config('app.name');

            Mail::raw($clientContent, function ($message) use ($proposal): void {
                $message->to($proposal->client_email)
                    ->subject('Votre proposition de rendez-vous a été acceptée - '.config('app.name'))
                    ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
            });

        } catch (\Exception $e) {
            \Log::error('Failed to send proposal accepted notification: '.$e->getMessage());
        }
    }

    /**
     * Send notification when proposal is rejected without counter-proposal
     */
    private function sendProposalRejectedNotification(CareService $proposal): void
    {
        try {
            $clientContent = "Bonjour {$proposal->client_name},\n\n".
                "Nous sommes désolés, votre proposition de rendez-vous n'a pas pu être acceptée.\n\n".
                "Proposition initiale :\n".
                '📅 Date : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$proposal->appointment_time->format('H:i')."\n\n".
                "Raison du refus :\n".
                $proposal->proposal_rejection_reason."\n\n".
                "Nous vous invitons à soumettre une nouvelle proposition ou à choisir parmi les créneaux disponibles.\n\n".
                "Cordialement,\nL'équipe ".config('app.name');

            Mail::raw($clientContent, function ($message) use ($proposal): void {
                $message->to($proposal->client_email)
                    ->subject('Votre proposition de rendez-vous n\'a pas pu être acceptée - '.config('app.name'))
                    ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
            });

        } catch (\Exception $e) {
            \Log::error('Failed to send proposal rejected notification: '.$e->getMessage());
        }
    }

    /**
     * Send notification when counter-proposal is sent
     */
    private function sendCounterProposalNotification(CareService $proposal): void
    {
        try {
            $confirmUrl = config('app.url').'/care-service/proposal/respond?token='.$proposal->proposal_token;

            $clientContent = "Bonjour {$proposal->client_name},\n\n".
                "Nous avons examiné votre proposition de rendez-vous et aimerions vous suggérer un autre créneau.\n\n".
                "Votre proposition initiale :\n".
                '📅 Date : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$proposal->appointment_time->format('H:i')."\n\n".
                "Notre contre-proposition :\n".
                '📅 Date : '.$proposal->counter_proposed_date->format('d/m/Y')."\n".
                '⏰ Heure : '.$proposal->counter_proposed_time."\n\n";

            if ($proposal->counter_proposal_message) {
                $clientContent .= "Message de l'équipe :\n".$proposal->counter_proposal_message."\n\n";
            }

            $clientContent .= "Pour accepter ou refuser cette contre-proposition, veuillez cliquer sur le lien suivant :\n".
                $confirmUrl."\n\n".
                "Cordialement,\nL'équipe ".config('app.name');

            Mail::raw($clientContent, function ($message) use ($proposal): void {
                $message->to($proposal->client_email)
                    ->subject('Contre-proposition pour votre rendez-vous - '.config('app.name'))
                    ->from(config('mail.from.address', 'noreply@icc-munich.de'), config('app.name'));
            });

        } catch (\Exception $e) {
            \Log::error('Failed to send counter-proposal notification: '.$e->getMessage());
        }
    }

    /**
     * Send notification when client rejects counter-proposal
     */
    private function sendCounterProposalRejectedNotification(CareService $proposal): void
    {
        try {
            // Notify care service team
            $agents = User::role(['admin', 'super-admin', 'care-service-agent'])->get();

            $messageContent = "Le client a refusé la contre-proposition :\n\n".
                '👤 Client : '.$proposal->client_name."\n".
                '📧 Email : '.$proposal->client_email."\n".
                '📅 Date proposée initialement : '.$proposal->appointment_date->format('d/m/Y')."\n".
                '📅 Contre-proposition refusée : '.$proposal->counter_proposed_date?->format('d/m/Y').' à '.$proposal->counter_proposed_time."\n";

            if ($proposal->cancellation_reason) {
                $messageContent .= "\n💬 Raison du refus : ".$proposal->cancellation_reason;
            }

            foreach ($agents as $agent) {
                Message::create([
                    'subject' => 'Contre-proposition refusée - '.$proposal->client_name,
                    'content' => $messageContent,
                    'sender_id' => 1,
                    'receiver_id' => $agent->id,
                    'type' => 'system',
                    'recipient_type' => 'user',
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send counter-proposal rejected notification: '.$e->getMessage());
        }
    }
}
