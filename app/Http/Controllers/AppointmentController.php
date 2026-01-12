<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Mail\AppointmentCreated;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentInvitation;
use App\Notifications\AppointmentConfirmation;
use App\Notifications\AppointmentCancellation;
use App\Services\CacheService;
use App\Services\AppointmentNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view appointments')->only(['index', 'show']);
        $this->middleware('can:create appointments')->only(['create', 'store']);
        $this->middleware('can:edit appointments')->only(['edit', 'update']);
        $this->middleware('can:delete appointments')->only(['destroy']);
        $this->middleware('can:manage appointment participants')->only([
            'inviteUser', 'removeParticipant', 'markAttended'
        ]);
    }

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $type = $request->get('type');
        $date = $request->get('date');
        $view = $request->get('view', 'list'); // list, calendar
        $page = (int) $request->get('page', 1);

        $cacheKey = $this->generateCacheKey('appointments.index', [
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'date' => $date,
            'view' => $view,
        ]);

        $appointments = CacheService::rememberPaginated(
            $cacheKey,
            $page,
            function () use ($search, $status, $type, $date) {
                $query = Appointment::with(['organizer:id,first_name,last_name,email', 'participants:id,first_name,last_name,email'])
                    ->withCount('participants')
                    ->select([
                        'id', 'uuid', 'title', 'description', 'start_datetime', 'end_datetime',
                        'location', 'status', 'type', 'user_id', 'created_at'
                    ]);

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%")
                          ->orWhereHas('organizer', function ($q) use ($search) {
                              $q->where('name', 'like', "%{$search}%");
                          });
                    });
                }

                if ($status) {
                    $query->withStatus($status);
                }

                if ($type) {
                    $query->withType($type);
                }

                if ($date) {
                    $query->forDate($date);
                }

                // Default ordering: upcoming first, then by start time
                return $query->orderBy('start_datetime', 'asc')->paginate(15);
            },
            CacheService::SHORT_CACHE
        );

        // Get statistics for the dashboard
        $stats = CacheService::remember('appointments.stats', function () {
            return [
                'total' => Appointment::count(),
                'upcoming' => Appointment::upcoming()->count(),
                'today' => Appointment::today()->count(),
                'pending' => Appointment::withStatus('pending')->count(),
                'confirmed' => Appointment::withStatus('confirmed')->count(),
            ];
        }, CacheService::SHORT_CACHE);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'date' => $date,
                'view' => $view,
            ],
            'statuses' => ['pending', 'confirmed', 'cancelled', 'completed'],
            'types' => ['individual', 'group', 'consultation', 'meeting'],
        ]);
    }

    /**
     * Show the form for creating a new appointment.
     */
    public function create(Request $request): Response
    {
        $prefilledData = [];

        // Check if we have pre-filled data from query parameters
        if ($request->has('date')) {
            $prefilledData['date'] = $request->get('date');
        }

        if ($request->has('time')) {
            $prefilledData['time'] = $request->get('time');
        }

        // Check for pre-selected participants
        if ($request->has('participant_ids')) {
            $participantIds = $request->get('participant_ids');
            // Handle both array and single value cases
            if (is_array($participantIds)) {
                $prefilledData['participant_ids'] = array_map('intval', $participantIds);
            } else {
                $prefilledData['participant_ids'] = [intval($participantIds)];
            }
        }

        // Get available users for invitations
        $users = User::select(['id', 'uuid', 'first_name', 'last_name', 'email'])
            ->where('id', '!=', Auth::id())
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                $user->name = $user->first_name . ' ' . $user->last_name;
                return $user;
            });

        // Get pre-selected participants data if any
        $preselectedParticipants = [];
        if (!empty($prefilledData['participant_ids'])) {
            $preselectedParticipants = User::select(['id', 'uuid', 'first_name', 'last_name', 'email'])
                ->whereIn('id', $prefilledData['participant_ids'])
                ->get()
                ->map(function ($user) {
                    $user->name = $user->first_name . ' ' . $user->last_name;
                    return $user;
                });
        }

        return Inertia::render('Appointments/Create', [
            'users' => $users,
            'prefilledData' => $prefilledData,
            'preselectedParticipants' => $preselectedParticipants,
            'types' => ['individual', 'group', 'consultation', 'meeting'],
        ]);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $appointment = Appointment::create($data);

        // Invite participants if any
        if (!empty($data['participant_ids'])) {
            // Bulk load users to avoid N+1 queries
            $users = User::whereIn('id', $data['participant_ids'])->get()->keyBy('id');
            foreach ($data['participant_ids'] as $userId) {
                if ($users->has($userId)) {
                    $this->inviteParticipantWithUser($appointment, $users[$userId]);
                }
            }
        }

        // Send confirmation message to organizer
        $notificationService = new AppointmentNotificationService();
        $notificationService->sendOrganizerConfirmation($appointment);

        // Clear cache
        CacheService::forgetPattern('appointments.*');

        return redirect()->route('appointments.index')
            ->with('message', 'Rendez-vous créé avec succès.');
    }

    /**
     * Invite a participant to an appointment with confirmation token.
     */
    private function inviteParticipant(Appointment $appointment, int $userId): void
    {
        $user = User::find($userId);
        if (!$user) return;

        // Generate unique confirmation token
        $confirmationToken = Str::random(64);

        // Attach participant with confirmation token
        $appointment->participants()->attach($userId, [
            'status' => 'pending',
            'confirmation_token' => $confirmationToken,
            'invited_at' => now(),
            'notification_sent_at' => now(),
        ]);

        // Send complete invitation notification (email + database notification + direct message + message notification)
        $notificationService = new AppointmentNotificationService();
        $notificationService->sendInvitationNotification($appointment, $user, $confirmationToken);
    }

    /**
     * Invite a participant to an appointment with confirmation token (with User object to avoid N+1).
     */
    private function inviteParticipantWithUser(Appointment $appointment, User $user): void
    {
        // Generate unique confirmation token
        $confirmationToken = Str::random(64);

        // Attach participant with confirmation token
        $appointment->participants()->attach($user->id, [
            'status' => 'pending',
            'confirmation_token' => $confirmationToken,
            'invited_at' => now(),
            'notification_sent_at' => now(),
        ]);

        // Send complete invitation notification (email + database notification + direct message + message notification)
        $notificationService = new AppointmentNotificationService();
        $notificationService->sendInvitationNotification($appointment, $user, $confirmationToken);
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment): Response
    {
        // Check permission
        if (!$appointment->canBeViewedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation de voir ce rendez-vous.');
        }

        $appointment->load([
            'organizer:id,first_name,last_name,email',
            'participants:id,first_name,last_name,email',
            'appointmentable'
        ]);

        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
            'canModify' => $appointment->canBeModifiedBy(Auth::user()),
            'canCancel' => $appointment->can_be_cancelled,
        ]);
    }

    /**
     * Show the form for editing the specified appointment.
     */
    public function edit(Appointment $appointment): Response
    {
        // Check permission
        if (!$appointment->canBeModifiedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier ce rendez-vous.');
        }

        $appointment->load(['participants:id,uuid,first_name,last_name,email']);

        // Get available users for invitations
        $users = User::select(['id', 'uuid', 'first_name', 'last_name', 'email'])
            ->where('id', '!=', $appointment->user_id)
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'users' => $users,
            'types' => ['individual', 'group', 'consultation', 'meeting'],
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        // Check permission
        if (!$appointment->canBeModifiedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier ce rendez-vous.');
        }

        $data = $request->validated();

        // Store previous participants before update
        $appointment->load('participants');
        $previousParticipants = $appointment->participants->pluck('id')->toArray();

        $appointment->update($data);

        // Update participants if provided
        if (isset($data['participant_ids'])) {
            // Get new participants
            $newParticipants = $data['participant_ids'];

            // Clear current participants
            $appointment->participants()->detach();

            // Re-invite all participants (existing ones will get update notification)
            // Bulk load users to avoid N+1 queries
            $users = User::whereIn('id', $newParticipants)->get()->keyBy('id');
            foreach ($newParticipants as $userId) {
                if ($users->has($userId)) {
                    $this->inviteParticipantWithUser($appointment, $users[$userId]);
                }
            }

            // Notify about the update
            $appointment->load('participants');
            $notificationService = new AppointmentNotificationService();
            foreach ($appointment->participants as $participant) {
                $notificationService->sendUpdateNotification($appointment, $participant, 'updated');
            }
        }

        // Clear cache
        CacheService::forgetPattern('appointments.*');

        return redirect()->route('appointments.show', $appointment->uuid)
            ->with('message', 'Rendez-vous mis à jour avec succès.');
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        // Check permission
        if (!$appointment->canBeModifiedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation de supprimer ce rendez-vous.');
        }

        $appointment->delete();

        // Clear cache
        CacheService::forgetPattern('appointments.*');

        return redirect()->route('appointments.index')
            ->with('message', 'Rendez-vous supprimé avec succès.');
    }

    /**
     * Cancel the specified appointment.
     */
    public function cancel(Appointment $appointment): RedirectResponse
    {
        // Check permission
        if (!$appointment->canBeModifiedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation d\'annuler ce rendez-vous.');
        }

        if (!$appointment->can_be_cancelled) {
            return redirect()->back()
                ->with('error', 'Ce rendez-vous ne peut plus être annulé.');
        }

        // Load participants before cancelling
        $appointment->load('participants');

        $appointment->update(['status' => 'cancelled']);

        // Send cancellation notifications to all participants
        $notificationService = new AppointmentNotificationService();
        foreach ($appointment->participants as $participant) {
            $participant->notify(new AppointmentCancellation($appointment, 'Rendez-vous annulé par l\'organisateur'));
            $notificationService->sendUpdateNotification($appointment, $participant, 'cancelled');
        }

        // Clear cache
        CacheService::forgetPattern('appointments.*');

        return redirect()->back()
            ->with('message', 'Rendez-vous annulé avec succès.');
    }

    /**
     * Confirm the specified appointment.
     */
    public function confirm(Appointment $appointment): RedirectResponse
    {
        // Check permission
        if (!$appointment->canBeModifiedBy(Auth::user())) {
            abort(403, 'Vous n\'avez pas l\'autorisation de confirmer ce rendez-vous.');
        }

        $appointment->update(['status' => 'confirmed']);

        // Send confirmation notifications to all participants
        $appointment->load('participants');
        $notificationService = new AppointmentNotificationService();
        foreach ($appointment->participants as $participant) {
            $notificationService->sendUpdateNotification($appointment, $participant, 'confirmed');
        }

        // Clear cache
        CacheService::forgetPattern('appointments.*');

        return redirect()->back()
            ->with('message', 'Rendez-vous confirmé avec succès.');
    }

    /**
     * Accept appointment invitation.
     */
    public function acceptInvitation(Appointment $appointment): RedirectResponse
    {
        $appointment->acceptInvitation(Auth::user());

        return redirect()->back()
            ->with('message', 'Invitation acceptée avec succès.');
    }

    /**
     * Decline appointment invitation.
     */
    public function declineInvitation(Appointment $appointment): RedirectResponse
    {
        $appointment->declineInvitation(Auth::user());

        return redirect()->back()
            ->with('message', 'Invitation déclinée.');
    }

    /**
     * Get available time slots for a specific date.
     */
    public function availableSlots(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:240',
            'organizer_id' => 'nullable|exists:users,id'
        ]);

        $date = $request->get('date');
        $duration = $request->get('duration', 60);
        $organizerId = $request->get('organizer_id');

        $organizer = $organizerId ? User::find($organizerId) : Auth::user();


        $slots = Appointment::getAvailableSlots($date, $duration, '03:00', '00:00', $organizer);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'duration_minutes' => $duration,
                'available_slots' => $slots,
                'total_slots' => count($slots)
            ]
        ]);
    }

    /**
     * Calendar view for appointments.
     */
    public function calendar(Request $request): Response
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $appointments = CacheService::remember(
            "appointments.calendar.{$month}",
            function () use ($startDate, $endDate) {
                return Appointment::with(['organizer:id,first_name,last_name', 'participants:id,first_name,last_name'])
                    ->betweenDates($startDate, $endDate)
                    ->whereNotIn('status', ['cancelled'])
                    ->get()
                    ->map(function ($appointment) {
                        return [
                            'id' => $appointment->uuid,
                            'title' => $appointment->title,
                            'start' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                            'end' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                            'backgroundColor' => $this->getStatusColor($appointment->status),
                            'borderColor' => $this->getStatusColor($appointment->status),
                            'url' => route('appointments.show', $appointment->uuid),
                            'extendedProps' => [
                                'status' => $appointment->status,
                                'type' => $appointment->type,
                                'location' => $appointment->location,
                                'organizer' => $appointment->organizer->first_name . ' ' . $appointment->organizer->last_name,
                                'participants_count' => $appointment->participants_count,
                            ]
                        ];
                    });
            },
            CacheService::SHORT_CACHE
        );

        return Inertia::render('Appointments/Calendar', [
            'appointments' => $appointments,
            'currentMonth' => $month,
        ]);
    }

    /**
     * Generate cache key for appointments.
     */
    private function generateCacheKey(string $base, array $params): string
    {
        $filteredParams = array_filter($params);
        return $filteredParams ? $base . '.' . md5(serialize($filteredParams)) : $base;
    }

    /**
     * Get status color for calendar events.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'confirmed' => '#10b981',
            'cancelled' => '#ef4444',
            'completed' => '#6b7280',
            default => '#3b82f6',
        };
    }
}