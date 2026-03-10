<?php

namespace App\Services\Event;

use App\Enums\Event\TicketType;
use App\Models\Event;
use App\Models\Event\EventTicket;
use App\Models\Event\EventPromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    /**
     * Create a new ticket type for an event.
     */
    public function createTicket(Event $event, array $data): EventTicket
    {
        return DB::transaction(function () use ($event, $data) {
            $ticket = EventTicket::create([
                'event_id' => $event->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? TicketType::PAID,
                'price' => $data['price'] ?? 0,
                'original_price' => $data['original_price'] ?? null,
                'currency' => $data['currency'] ?? 'EUR',
                'quantity_total' => $data['quantity_total'] ?? null,
                'min_per_order' => $data['min_per_order'] ?? 1,
                'max_per_order' => $data['max_per_order'] ?? null,
                'sales_start' => $data['sales_start'] ?? null,
                'sales_end' => $data['sales_end'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'restrictions' => $data['restrictions'] ?? null,
                'is_visible' => $data['is_visible'] ?? true,
                'requires_approval' => $data['requires_approval'] ?? false,
                'sort_order' => $data['sort_order'] ?? EventTicket::where('event_id', $event->id)->max('sort_order') + 1,
            ]);

            $this->clearTicketCache($event->id);

            return $ticket;
        });
    }

    /**
     * Update an existing ticket.
     */
    public function updateTicket(EventTicket $ticket, array $data): EventTicket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->update($data);
            $this->clearTicketCache($ticket->event_id);

            return $ticket->fresh();
        });
    }

    /**
     * Delete a ticket (soft delete).
     */
    public function deleteTicket(EventTicket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            $eventId = $ticket->event_id;
            $result = $ticket->delete();
            $this->clearTicketCache($eventId);

            return $result;
        });
    }

    /**
     * Get available tickets for an event.
     */
    public function getAvailableTickets(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(
            "event.{$event->id}.available_tickets",
            now()->addMinutes(5),
            fn () => $event->tickets()
                ->visible()
                ->onSale()
                ->available()
                ->ordered()
                ->get()
        );
    }

    /**
     * Check if tickets are available for purchase.
     */
    public function checkAvailability(EventTicket $ticket, int $quantity): array
    {
        $available = $ticket->available_quantity;

        if ($available === null) {
            return [
                'available' => true,
                'quantity' => $quantity,
                'message' => null,
            ];
        }

        if ($available >= $quantity) {
            return [
                'available' => true,
                'quantity' => $quantity,
                'message' => null,
            ];
        }

        if ($available > 0) {
            return [
                'available' => true,
                'quantity' => $available,
                'message' => "Seulement {$available} billets disponibles.",
            ];
        }

        return [
            'available' => false,
            'quantity' => 0,
            'message' => 'Ce billet est épuisé.',
        ];
    }

    /**
     * Reserve tickets temporarily (during checkout).
     */
    public function reserveTickets(EventTicket $ticket, int $quantity, int $minutes = 15): bool
    {
        if ($ticket->quantity_total === null) {
            return true;
        }

        return DB::transaction(function () use ($ticket, $quantity): bool {
            $ticket->lockForUpdate();

            if ($ticket->available_quantity < $quantity) {
                return false;
            }

            $ticket->increment('quantity_reserved', $quantity);
            $this->clearTicketCache($ticket->event_id);

            return true;
        });
    }

    /**
     * Release reserved tickets.
     */
    public function releaseReservation(EventTicket $ticket, int $quantity): void
    {
        DB::transaction(function () use ($ticket, $quantity): void {
            $ticket->decrement('quantity_reserved', min($quantity, $ticket->quantity_reserved));
            $this->clearTicketCache($ticket->event_id);
        });
    }

    /**
     * Confirm ticket purchase (convert reservation to sale).
     */
    public function confirmPurchase(EventTicket $ticket, int $quantity): void
    {
        DB::transaction(function () use ($ticket, $quantity): void {
            $ticket->lockForUpdate();

            // Release reservation and mark as sold
            $ticket->decrement('quantity_reserved', min($quantity, $ticket->quantity_reserved));
            $ticket->increment('quantity_sold', $quantity);

            $this->clearTicketCache($ticket->event_id);
        });
    }

    /**
     * Calculate ticket price with optional promo code.
     */
    public function calculatePrice(EventTicket $ticket, int $quantity, ?EventPromoCode $promoCode = null): array
    {
        $unitPrice = $ticket->current_price;
        $subtotal = $unitPrice * $quantity;
        $discount = 0;

        if ($promoCode && $promoCode->isValidForTicket($ticket)) {
            $discount = $promoCode->calculateDiscount($subtotal);
        }

        $total = max(0, $subtotal - $discount);

        return [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'currency' => $ticket->currency,
            'promo_code' => $promoCode?->code,
        ];
    }

    /**
     * Get ticket sales statistics.
     */
    public function getTicketStats(Event $event): array
    {
        return Cache::remember(
            "event.{$event->id}.ticket_stats",
            now()->addMinutes(10),
            function () use ($event): array {
                $tickets = $event->tickets;

                return [
                    'total_tickets' => $tickets->count(),
                    'total_capacity' => $tickets->sum('quantity_total'),
                    'total_sold' => $tickets->sum('quantity_sold'),
                    'total_reserved' => $tickets->sum('quantity_reserved'),
                    'total_available' => $tickets->sum(fn ($t) => $t->available_quantity ?? 0),
                    'revenue' => $tickets->sum(fn ($t): int|float => $t->quantity_sold * $t->price),
                    'currency' => $tickets->first()?->currency ?? 'EUR',
                    'by_type' => $tickets->groupBy('type')->map(fn ($group): array => [
                        'count' => $group->count(),
                        'sold' => $group->sum('quantity_sold'),
                        'revenue' => $group->sum(fn ($t): int|float => $t->quantity_sold * $t->price),
                    ])->toArray(),
                ];
            }
        );
    }

    /**
     * Validate promo code for an event.
     */
    public function validatePromoCode(Event $event, string $code): ?EventPromoCode
    {
        $promoCode = EventPromoCode::where('event_id', $event->id)
            ->where('code', strtoupper($code))
            ->active()
            ->first();

        if (!$promoCode || !$promoCode->is_valid) {
            return null;
        }

        return $promoCode;
    }

    /**
     * Create a promo code for an event.
     */
    public function createPromoCode(Event $event, array $data): EventPromoCode
    {
        return EventPromoCode::create([
            'event_id' => $event->id,
            'code' => strtoupper((string) $data['code']),
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_per_user' => $data['usage_per_user'] ?? 1,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'applicable_tickets' => $data['applicable_tickets'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Duplicate tickets from one event to another.
     */
    public function duplicateTickets(Event $sourceEvent, Event $targetEvent): array
    {
        $duplicated = [];

        foreach ($sourceEvent->tickets as $ticket) {
            $newTicket = $ticket->replicate();
            $newTicket->event_id = $targetEvent->id;
            $newTicket->quantity_sold = 0;
            $newTicket->quantity_reserved = 0;
            $newTicket->save();

            $duplicated[] = $newTicket;
        }

        return $duplicated;
    }

    /**
     * Clear ticket-related cache.
     */
    protected function clearTicketCache(int $eventId): void
    {
        Cache::forget("event.{$eventId}.available_tickets");
        Cache::forget("event.{$eventId}.ticket_stats");
    }

    /**
     * Get tickets with registration counts.
     */
    public function getTicketsWithStats(Event $event): \Illuminate\Database\Eloquent\Collection
    {
        return $event->tickets()
            ->withCount(['registrations as confirmed_count' => function ($query): void {
                $query->whereIn('status', ['confirmed', 'checked_in']);
            }])
            ->withCount(['registrations as pending_count' => function ($query): void {
                $query->where('status', 'pending');
            }])
            ->ordered()
            ->get();
    }
}
