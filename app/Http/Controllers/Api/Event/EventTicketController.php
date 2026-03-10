<?php

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Event\EventTicket;
use App\Services\Event\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
        $this->middleware('can:view events');
        $this->middleware('can:edit events')->except(['index', 'show', 'available', 'validatePromoCode']);
    }

    /**
     * Get all tickets for an event.
     */
    public function index(Event $event): JsonResponse
    {
        $tickets = $this->ticketService->getTicketsWithStats($event);

        return response()->json([
            'data' => $tickets,
            'stats' => $this->ticketService->getTicketStats($event),
        ]);
    }

    /**
     * Get available tickets for purchase.
     */
    public function available(Event $event): JsonResponse
    {
        $tickets = $this->ticketService->getAvailableTickets($event);

        return response()->json([
            'data' => $tickets,
        ]);
    }

    /**
     * Get a specific ticket.
     */
    public function show(Event $event, EventTicket $ticket): JsonResponse
    {
        if ($ticket->event_id !== $event->id) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        return response()->json([
            'data' => $ticket->load(['registrations' => function ($q): void {
                $q->whereIn('status', ['confirmed', 'checked_in'])->limit(10);
            }]),
        ]);
    }

    /**
     * Create a new ticket type.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:free,paid,donation,early_bird,vip,group,student,member',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'quantity_total' => 'nullable|integer|min:1',
            'min_per_order' => 'nullable|integer|min:1',
            'max_per_order' => 'nullable|integer|min:1',
            'sales_start' => 'nullable|date',
            'sales_end' => 'nullable|date|after:sales_start',
            'benefits' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'is_visible' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $ticket = $this->ticketService->createTicket($event, $validated);

        return response()->json([
            'data' => $ticket,
            'message' => 'Billet créé avec succès.',
        ], 201);
    }

    /**
     * Update a ticket.
     */
    public function update(Request $request, Event $event, EventTicket $ticket): JsonResponse
    {
        if ($ticket->event_id !== $event->id) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:free,paid,donation,early_bird,vip,group,student,member',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'quantity_total' => 'nullable|integer|min:1',
            'min_per_order' => 'nullable|integer|min:1',
            'max_per_order' => 'nullable|integer|min:1',
            'sales_start' => 'nullable|date',
            'sales_end' => 'nullable|date',
            'benefits' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'is_visible' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $ticket = $this->ticketService->updateTicket($ticket, $validated);

        return response()->json([
            'data' => $ticket,
            'message' => 'Billet mis à jour avec succès.',
        ]);
    }

    /**
     * Delete a ticket.
     */
    public function destroy(Event $event, EventTicket $ticket): JsonResponse
    {
        if ($ticket->event_id !== $event->id) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        // Check if ticket has registrations
        if ($ticket->registrations()->whereNotIn('status', ['cancelled'])->exists()) {
            return response()->json([
                'error' => 'Ce billet ne peut pas être supprimé car il a des inscriptions actives.',
            ], 422);
        }

        $this->ticketService->deleteTicket($ticket);

        return response()->json([
            'message' => 'Billet supprimé avec succès.',
        ]);
    }

    /**
     * Check ticket availability.
     */
    public function checkAvailability(Request $request, Event $event, EventTicket $ticket): JsonResponse
    {
        if ($ticket->event_id !== $event->id) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $quantity = $request->input('quantity', 1);
        $availability = $this->ticketService->checkAvailability($ticket, $quantity);

        return response()->json($availability);
    }

    /**
     * Validate a promo code.
     */
    public function validatePromoCode(Request $request, Event $event): JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(['error' => 'Code promo requis.'], 422);
        }

        $promoCode = $this->ticketService->validatePromoCode($event, $code);

        if (!$promoCode instanceof \App\Models\Event\EventPromoCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Code promo invalide ou expiré.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'code' => $promoCode->code,
            'discount_type' => $promoCode->discount_type,
            'discount_value' => $promoCode->discount_value,
            'description' => $promoCode->description,
        ]);
    }

    /**
     * Calculate price with promo code.
     */
    public function calculatePrice(Request $request, Event $event, EventTicket $ticket): JsonResponse
    {
        if ($ticket->event_id !== $event->id) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $quantity = $request->input('quantity', 1);
        $promoCode = null;

        if ($request->filled('promo_code')) {
            $promoCode = $this->ticketService->validatePromoCode($event, $request->input('promo_code'));
        }

        $pricing = $this->ticketService->calculatePrice($ticket, $quantity, $promoCode);

        return response()->json($pricing);
    }

    /**
     * Duplicate tickets to another event.
     */
    public function duplicate(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'target_event_id' => 'required|exists:events,id',
        ]);

        $targetEvent = Event::findOrFail($validated['target_event_id']);

        $duplicated = $this->ticketService->duplicateTickets($event, $targetEvent);

        return response()->json([
            'message' => count($duplicated) . ' billet(s) dupliqué(s) avec succès.',
            'data' => $duplicated,
        ]);
    }

    /**
     * Reorder tickets.
     */
    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:event_tickets,id',
        ]);

        foreach ($validated['order'] as $index => $ticketId) {
            EventTicket::where('id', $ticketId)
                ->where('event_id', $event->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'message' => 'Ordre des billets mis à jour.',
        ]);
    }
}
