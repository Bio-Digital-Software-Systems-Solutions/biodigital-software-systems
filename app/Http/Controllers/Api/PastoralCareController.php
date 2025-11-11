<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PastoralCare;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Http\Requests\PastoralCareStoreRequest;
use App\Mail\PastoralCareNewAppointmentNotification;
use App\Mail\PastoralCareAppointmentConfirmation;
use Illuminate\Support\Facades\Mail;

class PastoralCareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'getPastors',
            'getAvailableDays',
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
        if (!$pastor->hasRole('pastor')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a pastor'
            ], 400);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $availableDays = [];

        // Get pastor's availability settings
        $availabilities = \App\Models\PastorAvailability::where('pastor_id', $validated['pastor_id'])
            ->active()
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'available_days' => []
                ]
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
                $slots = PastoralCare::getAvailableTimeSlots(
                    $validated['pastor_id'],
                    $current->toDateString(),
                    60 // Default duration for checking availability
                );

                if (!empty($slots)) {
                    $availableDays[] = [
                        'date' => $current->toDateString(),
                        'day_name' => $current->locale('fr')->format('D'),
                        'full_date' => $current->locale('fr')->format('l j F Y'),
                        'slots_count' => count($slots)
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
                'end_date' => $validated['end_date']
            ]
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

        // Get consultation mode from pastor's availability for this date
        $currentDate = \Carbon\Carbon::parse($validated['date']);
        $dayOfWeek = $currentDate->dayOfWeek;

        $availability = \App\Models\PastorAvailability::where('pastor_id', $validated['pastor_id'])
            ->active()
            ->where(function ($query) use ($currentDate, $dayOfWeek) {
                $query->where(function ($q) use ($dayOfWeek) {
                    $q->where('type', 'weekly')
                      ->where('day_of_week', $dayOfWeek);
                })
                ->orWhere(function ($q) use ($currentDate) {
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
                'consultation_mode' => $consultationMode
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

            // Send notifications to client
            $this->sendStatusChangeNotifications($appointment, 'confirmed');

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

            // Send notifications to client
            $this->sendStatusChangeNotifications($appointment, 'cancelled');

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

    /**
     * Send notifications for new appointment (email + internal messages)
     */
    private function sendAppointmentNotifications(PastoralCare $appointment): void
    {
        try {
            $pastor = $appointment->pastor;

            // 1. Send email notification to pastor
            Mail::to($pastor->email)->send(new PastoralCareNewAppointmentNotification($appointment));

            // 2. Send internal message to pastor
            $pastorMessageContent = "Vous avez un nouveau rendez-vous de soin pastoral :\n\n" .
                "📅 Date : " . $appointment->appointment_date->format('d/m/Y') . "\n" .
                "⏰ Heure : " . $appointment->appointment_time->format('H:i') . "\n" .
                "⌛ Durée : " . $appointment->duration_minutes . " minutes\n" .
                "👤 Client : " . ($appointment->client_name ?? 'Non renseigné') . "\n" .
                "📧 Email : " . ($appointment->client_email ?? 'Non renseigné') . "\n" .
                "📱 Téléphone : " . ($appointment->client_phone ?? 'Non renseigné') . "\n" .
                "📍 Type : " . $this->getLocationTypeLabel($appointment->location_type) . "\n";

            if ($appointment->zoom_link) {
                $pastorMessageContent .= "🔗 Lien Zoom : " . $appointment->zoom_link . "\n";
            }

            if ($appointment->notes) {
                $pastorMessageContent .= "\n📝 Notes : " . $appointment->notes;
            }

            Message::create([
                'subject' => 'Nouveau rendez-vous de soin pastoral - ' . $appointment->appointment_date->format('d/m/Y'),
                'content' => $pastorMessageContent,
                'sender_id' => 1, // System user
                'receiver_id' => $pastor->id,
                'type' => 'appointment',
                'recipient_type' => 'user',
            ]);

            // 3. Send email notification to client (if email provided)
            if ($appointment->client_email) {
                // For now, we'll send a simple notification - can be enhanced with a dedicated Mailable later
                $clientSubject = 'Confirmation de rendez-vous - ICC Munich';
                $clientContent = "Bonjour " . ($appointment->client_name ?? '') . ",\n\n" .
                    "Votre demande de rendez-vous de soin pastoral a été enregistrée avec succès.\n\n" .
                    "Détails de votre rendez-vous :\n" .
                    "📅 Date : " . $appointment->appointment_date->format('d/m/Y') . "\n" .
                    "⏰ Heure : " . $appointment->appointment_time->format('H:i') . "\n" .
                    "⌛ Durée : " . $appointment->duration_minutes . " minutes\n" .
                    "👨‍💼 Pasteur : " . $pastor->first_name . " " . $pastor->last_name . "\n" .
                    "📍 Type : " . $this->getLocationTypeLabel($appointment->location_type) . "\n";

                if ($appointment->zoom_link) {
                    $clientContent .= "🔗 Lien Zoom : " . $appointment->zoom_link . "\n";
                }

                $clientContent .= "\nVotre rendez-vous est en attente de confirmation. Vous recevrez un email de confirmation une fois que le pasteur aura validé le créneau.\n\n" .
                    "Cordialement,\nL'équipe ICC Munich";

                // Simple email for client
                Mail::raw($clientContent, function ($message) use ($appointment, $clientSubject) {
                    $message->to($appointment->client_email)
                            ->subject($clientSubject)
                            ->from(config('mail.from.address', 'noreply@icc-munich.de'), 'ICC Munich');
                });
            }

            // 4. Send internal message to client (if they are a registered user)
            $clientUser = User::where('email', $appointment->client_email)->first();
            if ($clientUser) {
                $clientMessageContent = "Votre demande de rendez-vous de soin pastoral a été enregistrée :\n\n" .
                    "📅 Date : " . $appointment->appointment_date->format('d/m/Y') . "\n" .
                    "⏰ Heure : " . $appointment->appointment_time->format('H:i') . "\n" .
                    "⌛ Durée : " . $appointment->duration_minutes . " minutes\n" .
                    "👨‍💼 Pasteur : " . $pastor->first_name . " " . $pastor->last_name . "\n" .
                    "📍 Type : " . $this->getLocationTypeLabel($appointment->location_type) . "\n";

                if ($appointment->zoom_link) {
                    $clientMessageContent .= "🔗 Lien Zoom : " . $appointment->zoom_link . "\n";
                }

                $clientMessageContent .= "\n⏳ Statut : En attente de confirmation\n" .
                    "Vous recevrez une notification une fois le rendez-vous confirmé par le pasteur.";

                Message::create([
                    'subject' => 'Demande de rendez-vous enregistrée - ' . $appointment->appointment_date->format('d/m/Y'),
                    'content' => $clientMessageContent,
                    'sender_id' => 1, // System user
                    'receiver_id' => $clientUser->id,
                    'type' => 'appointment',
                    'recipient_type' => 'user',
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't break the appointment creation
            \Log::error('Failed to send appointment notifications: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get human-readable label for location type
     */
    private function getLocationTypeLabel(string $locationType): string
    {
        return match($locationType) {
            'in_person' => 'En présentiel à l\'église',
            'zoom' => 'Visioconférence (Zoom)',
            'hybrid' => 'Hybride (au choix du pasteur)',
            default => $locationType,
        };
    }

    /**
     * Send status change notifications to the client
     */
    private function sendStatusChangeNotifications(PastoralCare $appointment, string $newStatus): void
    {
        try {
            // Load relationships
            $appointment->load(['pastor', 'user']);

            // Prepare notification content based on status
            $statusTexts = [
                'confirmed' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été confirmé',
                    'action' => 'confirmé',
                ],
                'cancelled' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été annulé',
                    'action' => 'annulé',
                ]
            ];

            $statusInfo = $statusTexts[$newStatus] ?? null;
            if (!$statusInfo) {
                return;
            }

            // Send email notification to client if email is available
            if ($appointment->client_email) {
                if ($newStatus === 'confirmed') {
                    Mail::to($appointment->client_email)
                        ->send(new \App\Mail\PastoralCareAppointmentConfirmation($appointment));
                }
                // For cancellation, we can send a simple notification for now
                // TODO: Create a dedicated PastoralCareAppointmentCancellation Mailable if needed
            }

            // Send platform message to client if they have an account
            if ($appointment->user_id) {
                $messageContent = "Votre rendez-vous de soin pastoral avec {$appointment->pastor->first_name} {$appointment->pastor->last_name} ";
                $messageContent .= "prévu le " . $appointment->appointment_date->format('d/m/Y');
                $messageContent .= " à " . $appointment->appointment_time->format('H:i');
                $messageContent .= " a été {$statusInfo['action']}.";

                if ($newStatus === 'cancelled' && $appointment->cancellation_reason) {
                    $messageContent .= "\n\nRaison: " . $appointment->cancellation_reason;
                }

                Message::create([
                    'sender_id' => $appointment->pastor_id,
                    'receiver_id' => $appointment->user_id,
                    'subject' => $statusInfo['subject'],
                    'content' => $messageContent,
                    'type' => 'appointment_status_change',
                ]);
            }

        } catch (\Exception $e) {
            // Log the error but don't fail the status change
            \Log::error('Failed to send pastoral care status change notifications: ' . $e->getMessage());
        }
    }
}
