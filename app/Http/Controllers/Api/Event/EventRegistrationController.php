<?php

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventTicket;
use App\Models\Event\EventPromoCode;
use App\Models\Event\EventRegistration;
use App\Services\Event\RegistrationService;
use App\Services\Event\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventRegistrationController extends Controller
{
    public function __construct(protected RegistrationService $registrationService, protected TicketService $ticketService)
    {
        $this->middleware('can:view events');
        $this->middleware('can:edit events')->except(['register', 'myRegistrations', 'show', 'cancel']);
    }

    /**
     * Get all registrations for an event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $filters = $request->only(['status', 'ticket_id', 'search', 'participant_role', 'sort', 'direction', 'per_page']);
        $registrations = $this->registrationService->getRegistrations($event, $filters);

        return response()->json([
            'data' => $registrations->items(),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'per_page' => $registrations->perPage(),
                'total' => $registrations->total(),
            ],
            'stats' => $this->registrationService->getStats($event),
        ]);
    }

    /**
     * Get a specific registration.
     */
    public function show(Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $registration->load(['user', 'ticket', 'payments', 'checkins', 'badge']);

        return response()->json([
            'data' => $registration,
        ]);
    }

    /**
     * Register for an event (public or authenticated).
     */
    public function register(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'ticket_id' => 'nullable|exists:event_tickets,id',
            'promo_code' => 'nullable|string|max:50',
            'quantity' => 'nullable|integer|min:1|max:10',
            'participant_role' => 'nullable|string|in:attendee,speaker,moderator,volunteer,staff,sponsor,exhibitor,vip,press,observer',
            'form_answers' => 'nullable|array',
            'dietary_requirements' => 'nullable|array',
            'accessibility_needs' => 'nullable|array',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        // Check if already registered
        if ($this->registrationService->isAlreadyRegistered($event, $validated['email'], Auth::id())) {
            return response()->json([
                'error' => 'Vous êtes déjà inscrit à cet événement.',
            ], 422);
        }

        // Check if event can accept registrations
        if (!$event->canAcceptRegistrations()) {
            return response()->json([
                'error' => 'Les inscriptions sont fermées pour cet événement.',
            ], 422);
        }

        // Get ticket if specified
        $ticket = null;
        if (!empty($validated['ticket_id'])) {
            $ticket = EventTicket::find($validated['ticket_id']);
            if (!$ticket || $ticket->event_id !== $event->id) {
                return response()->json(['error' => 'Billet invalide.'], 422);
            }

            // Check availability
            $quantity = $validated['quantity'] ?? 1;
            $availability = $this->ticketService->checkAvailability($ticket, $quantity);
            if (!$availability['available']) {
                return response()->json(['error' => $availability['message']], 422);
            }
        }

        // Get promo code if specified
        $promoCode = null;
        if (!empty($validated['promo_code'])) {
            $promoCode = $this->ticketService->validatePromoCode($event, $validated['promo_code']);
            if (!$promoCode instanceof \App\Models\Event\EventPromoCode) {
                return response()->json(['error' => 'Code promo invalide.'], 422);
            }
        }

        try {
            $registration = $this->registrationService->register(
                $event,
                $validated,
                $ticket,
                $promoCode,
                Auth::user()
            );

            return response()->json([
                'data' => $registration->load(['ticket', 'event']),
                'message' => 'Inscription réussie !',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue lors de l\'inscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm a registration.
     */
    public function confirm(Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        if ($registration->status !== RegistrationStatus::PENDING) {
            return response()->json(['error' => 'Cette inscription ne peut pas être confirmée.'], 422);
        }

        $registration = $this->registrationService->confirm($registration);

        return response()->json([
            'data' => $registration,
            'message' => 'Inscription confirmée.',
        ]);
    }

    /**
     * Cancel a registration.
     */
    public function cancel(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        // Check if user can cancel (owner or admin)
        $user = Auth::user();
        if ($registration->user_id !== $user->id && !$user->can('edit events')) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        if ($registration->status === RegistrationStatus::CANCELLED) {
            return response()->json(['error' => 'Cette inscription est déjà annulée.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'refund' => 'nullable|boolean',
        ]);

        $registration = $this->registrationService->cancel(
            $registration,
            $validated['reason'] ?? null,
            $user,
            $validated['refund'] ?? false
        );

        return response()->json([
            'data' => $registration,
            'message' => 'Inscription annulée.',
        ]);
    }

    /**
     * Move to waitlist.
     */
    public function moveToWaitlist(Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $registration = $this->registrationService->moveToWaitlist($registration);

        return response()->json([
            'data' => $registration,
            'message' => 'Inscription déplacée sur liste d\'attente.',
        ]);
    }

    /**
     * Promote from waitlist.
     */
    public function promoteFromWaitlist(Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        try {
            $registration = $this->registrationService->promoteFromWaitlist($registration);

            return response()->json([
                'data' => $registration,
                'message' => 'Inscription promue depuis la liste d\'attente.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Transfer registration to another person.
     */
    public function transfer(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
        ]);

        // Check if new person is already registered
        if ($this->registrationService->isAlreadyRegistered($event, $validated['email'])) {
            return response()->json([
                'error' => 'Cette personne est déjà inscrite à l\'événement.',
            ], 422);
        }

        $registration = $this->registrationService->transfer($registration, $validated);

        return response()->json([
            'data' => $registration,
            'message' => 'Inscription transférée avec succès.',
        ]);
    }

    /**
     * Record a payment.
     */
    public function recordPayment(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,transfer,check,online',
            'payment_provider' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = $this->registrationService->recordPayment($registration, $validated);

        return response()->json([
            'data' => $payment,
            'registration' => $registration->fresh(),
            'message' => 'Paiement enregistré.',
        ]);
    }

    /**
     * Get current user's registrations.
     */
    public function myRegistrations(Request $request): JsonResponse
    {
        $user = Auth::user();

        $registrations = EventRegistration::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->with(['event', 'ticket'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $registrations->items(),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'per_page' => $registrations->perPage(),
                'total' => $registrations->total(),
            ],
        ]);
    }

    /**
     * Export registrations.
     */
    public function export(Request $request, Event $event): JsonResponse
    {
        $filters = $request->only(['status']);
        $data = $this->registrationService->exportRegistrations($event, $filters);

        return response()->json([
            'data' => $data,
            'event' => [
                'title' => $event->title,
                'date' => $event->start_date->format('Y-m-d'),
            ],
            'exported_at' => now()->toISOString(),
        ]);
    }

    /**
     * Bulk confirm registrations.
     */
    public function bulkConfirm(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:event_registrations,id',
        ]);

        $count = $this->registrationService->bulkUpdateStatus(
            $validated['registration_ids'],
            RegistrationStatus::CONFIRMED
        );

        return response()->json([
            'message' => "{$count} inscription(s) confirmée(s).",
            'count' => $count,
        ]);
    }

    /**
     * Bulk cancel registrations.
     */
    public function bulkCancel(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:event_registrations,id',
        ]);

        $count = $this->registrationService->bulkUpdateStatus(
            $validated['registration_ids'],
            RegistrationStatus::CANCELLED
        );

        return response()->json([
            'message' => "{$count} inscription(s) annulée(s).",
            'count' => $count,
        ]);
    }

    /**
     * Get registration statistics.
     */
    public function stats(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->registrationService->getStats($event),
        ]);
    }
}
