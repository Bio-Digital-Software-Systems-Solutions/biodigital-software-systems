<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferPastoralCareRequest;
use App\Mail\PastoralCareAppointmentConfirmation;
use App\Mail\PastoralCareNewAppointmentNotification;
use App\Mail\PastoralCareTransferNotification;
use App\Models\Message;
use App\Models\PastoralCare;
use App\Models\User;
use App\Services\PastoralCareStatisticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PastoralCareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view pastoral care')->only(['index', 'show']);
        $this->middleware('can:create pastoral care')->only(['create', 'store']);
        $this->middleware('can:edit pastoral care')->only(['edit', 'update']);
        $this->middleware('can:delete pastoral care')->only(['destroy']);
    }

    /**
     * Display a listing of appointments for authenticated pastor
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if user can manage all appointments (admin/SuperAdmin)
        $canManageAll = $user->can('manage pastoral care') ||
            $user->hasRole(['admin', 'SuperAdmin']);

        $query = PastoralCare::with(['user', 'pastor']);

        // If not admin, filter appointments based on user role
        if (! $canManageAll) {
            // Check if user has pastor role or pastor permissions
            $isPastor = $user->hasRole(['pastor', 'Pastor']) || $user->can('manage pastoral appointments');

            if ($isPastor) {
                // Pastor sees appointments where they are the pastor
                $query->forPastor($user->id);
            } else {
                // Regular user (member) sees appointments where they are the client
                $query->where('user_id', $user->id);
            }
        }

        $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->paginate(15)
            ->appends($request->query());

        // Create base stats query with same filters as main query but without pagination
        $statsQuery = PastoralCare::query();

        // Apply same permission filtering as main query
        if (! $canManageAll) {
            $isPastor = $user->hasRole(['pastor', 'Pastor']) || $user->can('manage pastoral appointments');
            if ($isPastor) {
                $statsQuery->forPastor($user->id);
            } else {
                $statsQuery->where('user_id', $user->id);
            }
        }

        // Apply same filters as main query
        if ($request->filled('status')) {
            $statsQuery->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $statsQuery->where('appointment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->where('appointment_date', '<=', $request->date_to);
        }

        return Inertia::render('PastoralCare/Index', [
            'appointments' => [
                'data' => $appointments->items(),
                'links' => $appointments->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                ],
            ],
            'filters' => $request->only(['status', 'date_from', 'date_to']),
            'canManageAll' => $canManageAll,
            'permissions' => [
                'canCreate' => $user->can('create pastoral care'),
                'canEdit' => $user->can('edit pastoral care'),
                'canDelete' => $user->can('delete pastoral care'),
                'canManage' => $user->can('manage pastoral care'),
                'canSelectPastor' => $user->can('select pastor for pastoral care'),
                'canViewMlrDashboard' => $user->can('view mlr dashboard'),
                'canTransfer' => $user->can('transfer pastoral care'),
            ],
            'stats' => [
                'total_appointments' => (clone $statsQuery)->count(),
                'pending_appointments' => (clone $statsQuery)->pending()->count(),
                'confirmed_appointments' => (clone $statsQuery)->confirmed()->count(),
                'completed_appointments' => (clone $statsQuery)->completed()->count(),
                'cancelled_appointments' => (clone $statsQuery)->cancelled()->count(),
                'this_week_appointments' => (clone $statsQuery)->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->count(),
                'next_week_appointments' => (clone $statsQuery)->whereBetween('appointment_date', [
                    now()->addWeek()->startOfWeek(),
                    now()->addWeek()->endOfWeek(),
                ])->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new appointment
     */
    public function create(): Response
    {
        // Get all users with pastoral care permissions
        $pastors = User::whereHas('permissions', function ($query) {
            $query->where('name', 'view pastoral care');
        })
            ->orWhereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'pastor', 'writer']);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('PastoralCare/Create', [
            'pastors' => $pastors,
        ]);
    }

    /**
     * Store a newly created appointment
     */
    public function store(Request $request): RedirectResponse
    {
        // Check if this is a public booking (unauthenticated user) or internal booking (authenticated user)
        $isPublicBooking = ! $request->user();

        $validationRules = [
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:30|max:180',
            'location_type' => 'required|in:in_person,zoom,hybrid',
            'zoom_link' => 'nullable|url|required_if:location_type,zoom,hybrid',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($isPublicBooking) {
            // Public booking - client details required, pastor selected
            $validationRules = array_merge($validationRules, [
                'pastor_id' => 'required|exists:users,id',
                'client_name' => 'required|string|max:255',
                'client_email' => 'required|email|max:255',
                'client_phone' => 'nullable|string|max:20',
            ]);
        } else {
            // Internal booking - client details optional, pastor can be selected
            $validationRules = array_merge($validationRules, [
                'pastor_id' => 'nullable|exists:users,id',
                'client_name' => 'nullable|string|max:255',
                'client_email' => 'nullable|email|max:255',
                'client_phone' => 'nullable|string|max:20',
            ]);
        }

        $validated = $request->validate($validationRules);

        // Combine date and time
        $appointmentDateTime = Carbon::parse($validated['appointment_date'].' '.$validated['appointment_time']);

        // Determine pastor ID
        $pastorId = $isPublicBooking
            ? $validated['pastor_id']
            : ($validated['pastor_id'] ?? $request->user()->id);

        // Verify the selected user is a pastor (for public bookings)
        if ($isPublicBooking) {
            $pastor = User::findOrFail($pastorId);
            if (! $pastor->hasRole('pastor')) {
                throw ValidationException::withMessages([
                    'pastor_id' => 'L\'utilisateur sélectionné n\'est pas un pasteur.',
                ]);
            }
        }

        // Check if time slot is available
        if (
            ! PastoralCare::isTimeSlotAvailable(
                $pastorId,
                $appointmentDateTime,
                $validated['duration_minutes']
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Ce créneau horaire n\'est pas disponible.',
            ]);
        }

        /** @var PastoralCare $appointment */
        $appointment = PastoralCare::create([
            'user_id' => Auth::id(),
            'pastor_id' => $pastorId,
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? (Auth::user() ? Auth::user()->email : null),
            'client_phone' => $validated['client_phone'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        // Load the pastor relationship for notifications
        $appointment->load('pastor');

        // Send notification emails
        try {
            // Email to requester (client) - only if email is provided
            if ($appointment->client_email) {
                Mail::to($appointment->client_email)
                    ->send(new PastoralCareAppointmentConfirmation($appointment));

                // Track that the notification email was sent
                $appointment->update(['notification_email_sent_at' => now()]);
            }

            // Email to pastor
            Mail::to($appointment->pastor->email)
                ->send(new PastoralCareNewAppointmentNotification($appointment));
        } catch (\Exception $e) {
            // Log the error but don't fail the appointment creation
            Log::error('Failed to send pastoral care appointment emails: '.$e->getMessage());
        }

        // Create platform messages
        try {
            // Get the requesting user (if authenticated) or create a system message
            $senderId = $request->user() ? $request->user()->id : 1; // Use system user ID 1 if not authenticated

            // Message to requester
            if ($request->user()) {
                Message::create([
                    'sender_id' => $senderId,
                    'receiver_id' => $request->user()->id,
                    'subject' => 'Confirmation de votre rendez-vous pastoral',
                    'content' => "Votre rendez-vous de soin pastoral avec {$appointment->pastor->first_name} {$appointment->pastor->last_name} a été planifié pour le ".
                        $appointment->appointment_date->format('d/m/Y').' à '.
                        $appointment->appointment_time->format('H:i').
                        '. Vous recevrez une confirmation par email. Consultez vos messages pour plus de détails.',
                    'type' => 'system',
                ]);
            }

            // Message to pastor
            Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $appointment->pastor_id,
                'subject' => 'Nouveau rendez-vous de soin pastoral',
                'content' => 'Un nouveau rendez-vous de soin pastoral a été planifié avec vous pour le '.
                    $appointment->appointment_date->format('d/m/Y').' à '.
                    $appointment->appointment_time->format('H:i').
                    ". Client: {$appointment->client_name} ({$appointment->client_email}). ".
                    'Consultez votre tableau de bord pour gérer ce rendez-vous.',
                'type' => 'system',
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the appointment creation
            Log::error('Failed to create pastoral care platform messages: '.$e->getMessage());
        }

        return redirect()->route('pastoral-care.show', $appointment)
            ->with('success', 'Rendez-vous créé avec succès.');
    }

    /**
     * Display the specified appointment
     */
    public function show(PastoralCare $pastoralCare): Response
    {
        $this->authorize('view', $pastoralCare);

        // Load relationships including parent and follow-ups for navigation
        $pastoralCare->load([
            'user',
            'pastor',
            'parent.pastor',
            'parent.user',
            'followUps.pastor',
            'followUps.user',
        ]);

        $user = Auth::user();

        // Determine if user can view client notes
        // User can view client notes if they are:
        // - The assigned pastor (always has access to their client's notes)
        // - The client themselves (user_id matches)
        // - An admin/SuperAdmin with the permission
        $canViewClientNotes =
            $user->id === $pastoralCare->pastor_id ||
            $user->id === $pastoralCare->user_id ||
            ($user->hasRole(['admin', 'SuperAdmin']) && $user->can('view pastoral care client notes'));

        return Inertia::render('PastoralCare/Show', [
            'appointment' => $pastoralCare,
            'canEdit' => $pastoralCare->status === 'pending',
            'canConfirm' => $pastoralCare->can_be_confirmed,
            'canCancel' => $pastoralCare->can_be_cancelled,
            'canViewClientNotes' => $canViewClientNotes,
        ]);
    }

    /**
     * Show the form for editing the appointment
     */
    public function edit(PastoralCare $pastoralCare): Response
    {
        $this->authorize('update', $pastoralCare);

        $pastoralCare->load(['user', 'pastor']);

        // Get all users with pastoral care permissions
        $pastors = User::whereHas('permissions', function ($query) {
            $query->where('name', 'view pastoral care');
        })
            ->orWhereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'pastor', 'writer']);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('PastoralCare/Edit', [
            'appointment' => $pastoralCare,
            'pastors' => $pastors,
        ]);
    }

    /**
     * Update the appointment
     */
    public function update(Request $request, PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('update', $pastoralCare);

        $validated = $request->validate([
            'pastor_id' => 'required|integer|exists:users,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:30|max:180',
            'location_type' => 'required|in:in_person,zoom,hybrid',
            'zoom_link' => 'nullable|url|required_if:location_type,zoom,hybrid',
            'status' => 'required|in:pending,confirmed,completed,cancelled,no_show',
        ]);

        // Combine date and time
        $appointmentDateTime = Carbon::parse($validated['appointment_date'].' '.$validated['appointment_time']);

        // Check if time slot is available (excluding current appointment)
        if (
            ! PastoralCare::isTimeSlotAvailable(
                $validated['pastor_id'],
                $appointmentDateTime,
                $validated['duration_minutes'],
                $pastoralCare->id
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time' => 'This time slot is not available.',
            ]);
        }

        $pastoralCare->update([
            'pastor_id' => $validated['pastor_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('pastoral-care.show', $pastoralCare)
            ->with('success', 'Rendez-vous mis à jour avec succès.');
    }

    /**
     * Remove the appointment
     */
    public function destroy(PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('delete', $pastoralCare);

        $pastoralCare->delete();

        return redirect()->route('pastoral-care.index')
            ->with('success', 'Rendez-vous supprimé avec succès.');
    }

    /**
     * Confirm an appointment
     */
    public function confirm(PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('update', $pastoralCare);

        try {
            $pastoralCare->confirm();

            // Send notifications to client
            $this->sendStatusChangeNotifications($pastoralCare, 'confirmed');

            return back()->with('success', 'Rendez-vous confirmé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('update', $pastoralCare);

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        try {
            $pastoralCare->cancel($validated['cancellation_reason'] ?? null);

            // Send notifications to client
            $this->sendStatusChangeNotifications($pastoralCare, 'cancelled');

            return back()->with('success', 'Rendez-vous annulé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark appointment as completed
     */
    public function complete(PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('update', $pastoralCare);

        try {
            $pastoralCare->complete();

            return back()->with('success', 'Rendez-vous marqué comme terminé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark appointment as no-show
     */
    public function noShow(PastoralCare $pastoralCare): RedirectResponse
    {
        $this->authorize('update', $pastoralCare);

        try {
            $pastoralCare->markAsNoShow();

            return back()->with('success', 'Rendez-vous marqué comme absence.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:180',
        ]);

        $slots = PastoralCare::getAvailableTimeSlots(
            $request->user()->id,
            $validated['date'],
            $validated['duration'] ?? 60
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Display the MLR dashboard
     */
    public function mlr(Request $request, PastoralCareStatisticsService $statisticsService): Response
    {
        $user = $request->user();

        // Check if user has permission to view MLR dashboard
        if (! $user->can('view mlr dashboard')) {
            abort(403, 'Accès non autorisé au tableau de bord MLR.');
        }

        $period = $request->get('period', 'month');

        // Get all pastors/agents for transfer functionality
        $pastors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'pastor', 'mlr_agent']);
        })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        // Get comprehensive statistics
        $stats = $statisticsService->getMlrDashboardStats($period);

        // Get all appointments for the MLR view (with pagination)
        $appointments = PastoralCare::with(['user', 'pastor', 'transferredFrom', 'transferredTo', 'parent'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(20);

        return Inertia::render('PastoralCare/Mlr', [
            'stats' => $stats,
            'appointments' => [
                'data' => $appointments->items(),
                'links' => $appointments->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                ],
            ],
            'pastors' => $pastors,
            'themes' => PastoralCare::THEMES,
            'currentPeriod' => $period,
            'can' => [
                'transfer' => $user->can('transfer pastoral care'),
                'viewAll' => $user->can('view all pastoral care'),
                'viewStatistics' => $user->can('view pastoral care statistics'),
            ],
        ]);
    }

    /**
     * Get MLR statistics as JSON (for AJAX updates)
     */
    public function mlrStatistics(Request $request, PastoralCareStatisticsService $statisticsService): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('view pastoral care statistics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->get('period', 'month');
        $stats = $statisticsService->getMlrDashboardStats($period);

        return response()->json($stats);
    }

    /**
     * Transfer an appointment to another pastor/agent
     */
    public function transfer(TransferPastoralCareRequest $request, PastoralCare $pastoralCare): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Get the new pastor for notifications
            $newPastor = User::findOrFail($validated['transferred_to_id']);
            $oldPastor = $pastoralCare->pastor;

            // Perform the transfer
            $pastoralCare->transferTo(
                $validated['transferred_to_id'],
                $validated['transfer_reason'] ?? null
            );

            // Send notifications
            $this->sendTransferNotifications($pastoralCare, $oldPastor, $newPastor);

            return back()->with('success', "Rendez-vous transféré avec succès à {$newPastor->first_name} {$newPastor->last_name}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send notifications when an appointment is transferred
     */
    private function sendTransferNotifications(PastoralCare $appointment, User $oldPastor, User $newPastor): void
    {
        try {
            // Refresh appointment to get updated data
            $appointment->refresh();

            // 1. Notify NEW PASTOR - Platform message
            Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $newPastor->id,
                'subject' => 'Nouveau rendez-vous de soin pastoral transféré',
                'content' => "Un rendez-vous de soin pastoral vous a été transféré par {$oldPastor->first_name} {$oldPastor->last_name}.\n\n".
                    "Date: {$appointment->appointment_date->format('d/m/Y')}\n".
                    "Heure: {$appointment->appointment_time->format('H:i')}\n".
                    "Client: {$appointment->client_name}\n".
                    ($appointment->transfer_reason ? "\nRaison du transfert: {$appointment->transfer_reason}" : ''),
                'type' => 'system',
            ]);

            // 1b. Notify NEW PASTOR - Email
            Mail::to($newPastor->email)->send(
                new PastoralCareTransferNotification($appointment, $oldPastor, $newPastor, 'new_pastor')
            );

            // 2. Notify OLD PASTOR - Platform message
            Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $oldPastor->id,
                'subject' => 'Rendez-vous de soin pastoral transféré',
                'content' => "Votre rendez-vous de soin pastoral a été transféré à {$newPastor->first_name} {$newPastor->last_name}.\n\n".
                    "Date: {$appointment->appointment_date->format('d/m/Y')}\n".
                    "Heure: {$appointment->appointment_time->format('H:i')}\n".
                    "Client: {$appointment->client_name}",
                'type' => 'system',
            ]);

            // 2b. Notify OLD PASTOR - Email
            Mail::to($oldPastor->email)->send(
                new PastoralCareTransferNotification($appointment, $oldPastor, $newPastor, 'old_pastor')
            );

            // 3. Notify CLIENT - Platform message (if they have an account)
            if ($appointment->user_id) {
                Message::create([
                    'sender_id' => Auth::id(),
                    'receiver_id' => $appointment->user_id,
                    'subject' => 'Changement de responsable pour votre rendez-vous',
                    'content' => "Votre rendez-vous de soin pastoral du {$appointment->appointment_date->format('d/m/Y')} à {$appointment->appointment_time->format('H:i')} ".
                        "a été transféré de {$oldPastor->first_name} {$oldPastor->last_name} à {$newPastor->first_name} {$newPastor->last_name}.\n\n".
                        'Veuillez confirmer à nouveau votre rendez-vous si nécessaire.',
                    'type' => 'system',
                ]);
            }

            // 3b. Notify CLIENT - Email (always send to client_email, even if they don't have an account)
            if ($appointment->client_email) {
                Mail::to($appointment->client_email)->send(
                    new PastoralCareTransferNotification($appointment, $oldPastor, $newPastor, 'client')
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send transfer notifications: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'old_pastor_id' => $oldPastor->id,
                'new_pastor_id' => $newPastor->id,
                'error' => $e->getMessage(),
            ]);
        }
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
                    'email_template' => PastoralCareAppointmentConfirmation::class,
                ],
                'cancelled' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été annulé',
                    'action' => 'annulé',
                    'email_template' => null, // We'll create a cancellation template if needed
                ],
            ];

            $statusInfo = $statusTexts[$newStatus] ?? null;
            if (! $statusInfo) {
                return;
            }

            // Send email notification to client if email is available
            if ($appointment->client_email) {
                if ($newStatus === 'confirmed') {
                    Mail::to($appointment->client_email)
                        ->send(new PastoralCareAppointmentConfirmation($appointment));
                }
                // For cancellation, we can send a simple notification for now
                // TODO: Create a dedicated PastoralCareAppointmentCancellation Mailable if needed
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
        } catch (\Exception $e) {
            // Log the error but don't fail the status change
            Log::error('Failed to send pastoral care status change notifications: '.$e->getMessage());
        }
    }
}
