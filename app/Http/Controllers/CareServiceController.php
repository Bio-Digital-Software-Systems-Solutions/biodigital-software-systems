<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferCareServiceRequest;
use App\Mail\CareServiceAppointmentConfirmation;
use App\Mail\CareServiceNewAppointmentNotification;
use App\Mail\CareServiceTransferNotification;
use App\Models\CareService;
use App\Models\Message;
use App\Models\User;
use App\Services\CareServiceStatisticsService;
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

class CareServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view care service')->only(['index', 'show']);
        $this->middleware('can:create care service')->only(['create', 'store']);
        $this->middleware('can:edit care service')->only(['edit', 'update']);
        $this->middleware('can:delete care service')->only(['destroy']);
    }

    /**
     * Display a listing of appointments for authenticated pastor
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if user can manage all appointments (admin/super-admin)
        $canManageAll = $user->can('manage care service') ||
            $user->hasRole(['admin', 'super-admin']);

        $query = CareService::with(['user', 'pastor']);

        // If not admin, filter appointments based on user role
        if (! $canManageAll) {
            // Check if user has pastor role or pastor permissions
            $isPastor = $user->hasRole('pastor') || $user->can('manage care service appointments');

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
        $statsQuery = CareService::query();

        // Apply same permission filtering as main query
        if (! $canManageAll) {
            $isPastor = $user->hasRole('pastor') || $user->can('manage care service appointments');
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

        return Inertia::render('CareService/Index', [
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
                'canCreate' => $user->can('create care service'),
                'canEdit' => $user->can('edit care service'),
                'canDelete' => $user->can('delete care service'),
                'canManage' => $user->can('manage care service'),
                'canSelectPastor' => $user->can('select pastor for care service'),
                'canViewCareServiceDashboard' => $user->can('view care service dashboard'),
                'canTransfer' => $user->can('transfer care service'),
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
        // Get all users with care service permissions
        $pastors = User::whereHas('permissions', function ($query): void {
            $query->where('name', 'view care service');
        })
            ->orWhereHas('roles', function ($query): void {
                $query->whereIn('name', ['admin', 'pastor', 'writer']);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('CareService/Create', [
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
            ! CareService::isTimeSlotAvailable(
                $pastorId,
                $appointmentDateTime,
                $validated['duration_minutes']
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Ce créneau horaire n\'est pas disponible.',
            ]);
        }

        /** @var CareService $appointment */
        $appointment = CareService::create([
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
                    ->send(new CareServiceAppointmentConfirmation($appointment));

                // Track that the notification email was sent
                $appointment->update(['notification_email_sent_at' => now()]);
            }

            // Email to pastor
            Mail::to($appointment->pastor->email)
                ->send(new CareServiceNewAppointmentNotification($appointment));
        } catch (\Exception $e) {
            // Log the error but don't fail the appointment creation
            Log::error('Failed to send care service appointment emails: '.$e->getMessage());
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
            Log::error('Failed to create care service platform messages: '.$e->getMessage());
        }

        return redirect()->route('care-service.show', $appointment)
            ->with('success', 'Rendez-vous créé avec succès.');
    }

    /**
     * Display the specified appointment
     */
    public function show(CareService $careService): Response
    {
        $this->authorize('view', $careService);

        // Load relationships including parent and follow-ups for navigation
        $careService->load([
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
        // - An admin/super-admin with the permission
        $canViewClientNotes =
            $user->id === $careService->pastor_id ||
            $user->id === $careService->user_id ||
            ($user->hasRole(['admin', 'super-admin']) && $user->can('view care service client notes'));

        return Inertia::render('CareService/Show', [
            'appointment' => $careService,
            'canEdit' => $careService->status === 'pending',
            'canConfirm' => $careService->can_be_confirmed,
            'canCancel' => $careService->can_be_cancelled,
            'canViewClientNotes' => $canViewClientNotes,
        ]);
    }

    /**
     * Show the form for editing the appointment
     */
    public function edit(CareService $careService): Response
    {
        $this->authorize('update', $careService);

        $careService->load(['user', 'pastor']);

        // Get all users with care service permissions
        $pastors = User::whereHas('permissions', function ($query): void {
            $query->where('name', 'view care service');
        })
            ->orWhereHas('roles', function ($query): void {
                $query->whereIn('name', ['admin', 'pastor', 'writer']);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('CareService/Edit', [
            'appointment' => $careService,
            'pastors' => $pastors,
        ]);
    }

    /**
     * Update the appointment
     */
    public function update(Request $request, CareService $careService): RedirectResponse
    {
        $this->authorize('update', $careService);

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
            ! CareService::isTimeSlotAvailable(
                $validated['pastor_id'],
                $appointmentDateTime,
                $validated['duration_minutes'],
                $careService->id
            )
        ) {
            throw ValidationException::withMessages([
                'appointment_time' => 'This time slot is not available.',
            ]);
        }

        $careService->update([
            'pastor_id' => $validated['pastor_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $validated['duration_minutes'],
            'location_type' => $validated['location_type'],
            'zoom_link' => $validated['zoom_link'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('care-service.show', $careService)
            ->with('success', 'Rendez-vous mis à jour avec succès.');
    }

    /**
     * Remove the appointment
     */
    public function destroy(CareService $careService): RedirectResponse
    {
        $this->authorize('delete', $careService);

        $careService->delete();

        return redirect()->route('care-service.index')
            ->with('success', 'Rendez-vous supprimé avec succès.');
    }

    /**
     * Confirm an appointment
     */
    public function confirm(CareService $careService): RedirectResponse
    {
        $this->authorize('update', $careService);

        try {
            $careService->confirm();

            // Send notifications to client
            $this->sendStatusChangeNotifications($careService, 'confirmed');

            return back()->with('success', 'Rendez-vous confirmé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, CareService $careService): RedirectResponse
    {
        $this->authorize('update', $careService);

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        try {
            $careService->cancel($validated['cancellation_reason'] ?? null);

            // Send notifications to client
            $this->sendStatusChangeNotifications($careService, 'cancelled');

            return back()->with('success', 'Rendez-vous annulé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark appointment as completed
     */
    public function complete(CareService $careService): RedirectResponse
    {
        $this->authorize('update', $careService);

        try {
            $careService->complete();

            return back()->with('success', 'Rendez-vous marqué comme terminé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark appointment as no-show
     */
    public function noShow(CareService $careService): RedirectResponse
    {
        $this->authorize('update', $careService);

        try {
            $careService->markAsNoShow();

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

        $slots = CareService::getAvailableTimeSlots(
            $request->user()->id,
            $validated['date'],
            $validated['duration'] ?? 60
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Display the care service dashboard
     */
    public function dashboard(Request $request, CareServiceStatisticsService $statisticsService): Response
    {
        $user = $request->user();

        // Check if user has permission to view the care service dashboard
        if (! $user->can('view care service dashboard')) {
            abort(403, 'Accès non autorisé au tableau de bord du service de soin.');
        }

        // Check who can view all appointments:
        // - admin/super-admin roles
        // - Users with "view all care service" or "manage care service" permission
        $canViewAllAppointments = $user->hasRole(['admin', 'super-admin']) ||
            $user->can('view all care service') ||
            $user->can('manage care service');

        // Check if user is a pastor or care-service-agent (they see only their own appointments)
        $isPastorOrAgent = $user->hasRole(['pastor', 'care-service-agent']);

        // Users must either have full access OR be a pastor/agent to access the dashboard
        if (! $canViewAllAppointments && ! $isPastorOrAgent) {
            abort(403, 'Accès non autorisé au tableau de bord du service de soin.');
        }

        $period = $request->get('period', 'month');

        // Get all pastors/agents for transfer functionality
        $pastors = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'pastor', 'care-service-agent']);
        })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        // Determine if we need to filter by user
        // Pastors and care-service-agents (who don't have full access) only see their own appointments
        $filterByUserId = $canViewAllAppointments ? null : $user->id;

        // Get comprehensive statistics (filtered for pastors/agents)
        $stats = $statisticsService->getCareServiceDashboardStats($period, $filterByUserId);

        // Build appointments query with role-based filtering
        $appointmentsQuery = CareService::with(['user', 'pastor', 'transferredFrom', 'transferredTo', 'parent', 'assignedAgent']);

        // Pastors and care-service-agents (who don't have full access) only see their own appointments
        // Uses the polymorphic assigned_agent relationship
        if ($filterByUserId) {
            $appointmentsQuery->forAssignedAgent($user->id, User::class);
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(20);

        return Inertia::render('CareService/Dashboard', [
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
            'themes' => CareService::THEMES,
            'currentPeriod' => $period,
            'can' => [
                'transfer' => $user->can('transfer care service'),
                'viewAll' => $user->can('view all care service'),
                'viewStatistics' => $user->can('view care service statistics'),
            ],
        ]);
    }

    /**
     * Get care service dashboard statistics as JSON (for AJAX updates)
     */
    public function dashboardStatistics(Request $request, CareServiceStatisticsService $statisticsService): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('view care service statistics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->get('period', 'month');
        $stats = $statisticsService->getCareServiceDashboardStats($period);

        return response()->json($stats);
    }

    /**
     * Transfer an appointment to another pastor/agent
     */
    public function transfer(TransferCareServiceRequest $request, CareService $careService): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Get the new pastor for notifications
            $newPastor = User::findOrFail($validated['transferred_to_id']);
            $oldPastor = $careService->pastor;

            // Perform the transfer
            $careService->transferTo(
                $validated['transferred_to_id'],
                $validated['transfer_reason'] ?? null
            );

            // Send notifications
            $this->sendTransferNotifications($careService, $oldPastor, $newPastor);

            return back()->with('success', "Rendez-vous transféré avec succès à {$newPastor->first_name} {$newPastor->last_name}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send notifications when an appointment is transferred
     */
    private function sendTransferNotifications(CareService $appointment, User $oldPastor, User $newPastor): void
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
                new CareServiceTransferNotification($appointment, $oldPastor, $newPastor, 'new_pastor')
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
                new CareServiceTransferNotification($appointment, $oldPastor, $newPastor, 'old_pastor')
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
                    new CareServiceTransferNotification($appointment, $oldPastor, $newPastor, 'client')
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
    private function sendStatusChangeNotifications(CareService $appointment, string $newStatus): void
    {
        try {
            // Load relationships
            $appointment->load(['pastor', 'user']);

            // Prepare notification content based on status
            $statusTexts = [
                'confirmed' => [
                    'subject' => 'Votre rendez-vous de soin pastoral a été confirmé',
                    'action' => 'confirmé',
                    'email_template' => CareServiceAppointmentConfirmation::class,
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
                        ->send(new CareServiceAppointmentConfirmation($appointment));
                }
                // For cancellation, we can send a simple notification for now
                // TODO: Create a dedicated CareServiceAppointmentCancellation Mailable if needed
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
            Log::error('Failed to send care service status change notifications: '.$e->getMessage());
        }
    }
}
