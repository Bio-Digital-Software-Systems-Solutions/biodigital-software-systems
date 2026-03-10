<?php

namespace App\Services\Event;

use App\Enums\Event\PaymentStatus;
use App\Enums\Event\RegistrationStatus;
use App\Enums\Event\ParticipantRole;
use App\Models\Event;
use App\Models\Event\EventTicket;
use App\Models\Event\EventPromoCode;
use App\Models\Event\EventRegistration;
use App\Models\Event\RegistrationPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    /**
     * Register a participant for an event.
     */
    public function register(
        Event $event,
        array $data,
        ?EventTicket $ticket = null,
        ?EventPromoCode $promoCode = null,
        ?User $user = null
    ): EventRegistration {
        return DB::transaction(function () use ($event, $data, $ticket, $promoCode, $user) {
            // Calculate pricing
            $quantity = $data['quantity'] ?? 1;
            $pricing = $ticket instanceof \App\Models\Event\EventTicket
                ? $this->ticketService->calculatePrice($ticket, $quantity, $promoCode)
                : ['unit_price' => 0, 'discount' => 0, 'total' => 0, 'currency' => 'EUR'];

            // Determine initial status
            $status = $this->determineInitialStatus($event, $ticket, $pricing['total']);

            // Create registration
            $registration = EventRegistration::create([
                'registration_number' => $this->generateRegistrationNumber(),
                'event_id' => $event->id,
                'user_id' => $user?->id,
                'ticket_id' => $ticket?->id,
                'promo_code_id' => $promoCode?->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'status' => $status,
                'participant_role' => $data['participant_role'] ?? ParticipantRole::ATTENDEE,
                'quantity' => $quantity,
                'unit_price' => $pricing['unit_price'],
                'discount_amount' => $pricing['discount'],
                'total_amount' => $pricing['total'],
                'currency' => $pricing['currency'],
                'form_answers' => $data['form_answers'] ?? null,
                'dietary_requirements' => $data['dietary_requirements'] ?? null,
                'accessibility_needs' => $data['accessibility_needs'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'qr_code' => $this->generateQRCode(),
                'registered_at' => now(),
            ]);

            // Reserve tickets if applicable
            if ($ticket instanceof \App\Models\Event\EventTicket) {
                $this->ticketService->reserveTickets($ticket, $quantity);
            }

            // Increment promo code usage
            if ($promoCode instanceof \App\Models\Event\EventPromoCode) {
                $promoCode->increment('usage_count');
            }

            // Auto-confirm if free and no approval required
            if ($status === RegistrationStatus::PENDING && $pricing['total'] == 0 && !$event->requires_approval) {
                $this->confirm($registration);
            }

            return $registration;
        });
    }

    /**
     * Confirm a registration.
     */
    public function confirm(EventRegistration $registration): EventRegistration
    {
        return DB::transaction(function () use ($registration) {
            $registration->update([
                'status' => RegistrationStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            // Confirm ticket purchase
            if ($registration->ticket) {
                $this->ticketService->confirmPurchase($registration->ticket, $registration->quantity);
            }

            // TODO: Send confirmation email

            return $registration->fresh();
        });
    }

    /**
     * Cancel a registration.
     */
    public function cancel(
        EventRegistration $registration,
        ?string $reason = null,
        ?User $cancelledBy = null,
        bool $refund = false
    ): EventRegistration {
        return DB::transaction(function () use ($registration, $reason, $cancelledBy, $refund) {
            $previousStatus = $registration->status;

            $registration->update([
                'status' => RegistrationStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy?->id,
            ]);

            // Release ticket reservation if not yet confirmed
            if ($registration->ticket && $previousStatus === RegistrationStatus::PENDING) {
                $this->ticketService->releaseReservation($registration->ticket, $registration->quantity);
            }

            // Process refund if requested
            if ($refund && $registration->total_amount > 0) {
                $this->processRefund($registration, $reason);
            }

            // TODO: Send cancellation email

            return $registration->fresh();
        });
    }

    /**
     * Move registration to waitlist.
     */
    public function moveToWaitlist(EventRegistration $registration): EventRegistration
    {
        return DB::transaction(function () use ($registration) {
            $registration->update([
                'status' => RegistrationStatus::WAITLISTED,
            ]);

            // Release ticket reservation
            if ($registration->ticket) {
                $this->ticketService->releaseReservation($registration->ticket, $registration->quantity);
            }

            return $registration->fresh();
        });
    }

    /**
     * Promote from waitlist to confirmed.
     */
    public function promoteFromWaitlist(EventRegistration $registration): EventRegistration
    {
        if ($registration->status !== RegistrationStatus::WAITLISTED) {
            throw new \InvalidArgumentException('Registration is not on waitlist');
        }

        return DB::transaction(function () use ($registration) {
            // Check ticket availability
            if ($registration->ticket) {
                $availability = $this->ticketService->checkAvailability(
                    $registration->ticket,
                    $registration->quantity
                );

                if (!$availability['available']) {
                    throw new \RuntimeException('Tickets no longer available');
                }

                $this->ticketService->confirmPurchase($registration->ticket, $registration->quantity);
            }

            $registration->update([
                'status' => RegistrationStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            // TODO: Send promotion notification

            return $registration->fresh();
        });
    }

    /**
     * Transfer registration to another person.
     */
    public function transfer(EventRegistration $registration, array $newAttendeeData): EventRegistration
    {
        return DB::transaction(function () use ($registration, $newAttendeeData) {
            $registration->update([
                'first_name' => $newAttendeeData['first_name'],
                'last_name' => $newAttendeeData['last_name'],
                'email' => $newAttendeeData['email'],
                'phone' => $newAttendeeData['phone'] ?? $registration->phone,
                'company' => $newAttendeeData['company'] ?? $registration->company,
                'job_title' => $newAttendeeData['job_title'] ?? $registration->job_title,
                'user_id' => $newAttendeeData['user_id'] ?? null,
                'qr_code' => $this->generateQRCode(), // Generate new QR code
                'metadata' => array_merge($registration->metadata ?? [], [
                    'transferred_at' => now()->toISOString(),
                    'transferred_from' => $registration->full_name,
                ]),
            ]);

            // TODO: Send transfer notification to both parties

            return $registration->fresh();
        });
    }

    /**
     * Record a payment for a registration.
     */
    public function recordPayment(
        EventRegistration $registration,
        array $paymentData
    ): RegistrationPayment {
        return DB::transaction(function () use ($registration, $paymentData) {
            $payment = RegistrationPayment::create([
                'registration_id' => $registration->id,
                'payment_number' => $this->generatePaymentNumber(),
                'status' => PaymentStatus::COMPLETED,
                'payment_method' => $paymentData['payment_method'],
                'payment_provider' => $paymentData['payment_provider'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'amount' => $paymentData['amount'],
                'fee' => $paymentData['fee'] ?? 0,
                'net_amount' => $paymentData['amount'] - ($paymentData['fee'] ?? 0),
                'currency' => $paymentData['currency'] ?? $registration->currency,
                'provider_response' => $paymentData['provider_response'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'paid_at' => now(),
            ]);

            // If payment covers full amount, confirm registration
            $totalPaid = $registration->payments()->where('status', PaymentStatus::COMPLETED)->sum('amount');
            if ($totalPaid >= $registration->total_amount) {
                $this->confirm($registration);
            }

            return $payment;
        });
    }

    /**
     * Process a refund.
     */
    public function processRefund(
        EventRegistration $registration,
        ?string $reason = null,
        ?float $amount = null
    ): ?RegistrationPayment {
        $lastPayment = $registration->payments()
            ->where('status', PaymentStatus::COMPLETED)
            ->whereNull('refunded_at')
            ->latest()
            ->first();

        if (!$lastPayment) {
            return null;
        }

        $refundAmount = $amount ?? $lastPayment->amount;

        $lastPayment->update([
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);

        return $lastPayment;
    }

    /**
     * Get registration by QR code.
     */
    public function findByQRCode(string $qrCode): ?EventRegistration
    {
        return EventRegistration::where('qr_code', $qrCode)
            ->whereIn('status', [
                RegistrationStatus::CONFIRMED,
                RegistrationStatus::CHECKED_IN,
            ])
            ->first();
    }

    /**
     * Get registrations for an event with filters.
     */
    public function getRegistrations(Event $event, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $event->registrations()
            ->with(['user', 'ticket', 'payments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['ticket_id'])) {
            $query->where('ticket_id', $filters['ticket_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['participant_role'])) {
            $query->where('participant_role', $filters['participant_role']);
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Export registrations to array format.
     */
    public function exportRegistrations(Event $event, array $filters = []): array
    {
        $query = $event->registrations()->with(['ticket', 'payments']);

        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        return $query->get()->map(fn($reg): array => [
            'registration_number' => $reg->registration_number,
            'first_name' => $reg->first_name,
            'last_name' => $reg->last_name,
            'email' => $reg->email,
            'phone' => $reg->phone,
            'company' => $reg->company,
            'job_title' => $reg->job_title,
            'ticket' => $reg->ticket?->name,
            'quantity' => $reg->quantity,
            'total_amount' => $reg->total_amount,
            'status' => $reg->status->label(),
            'participant_role' => $reg->participant_role->label(),
            'registered_at' => $reg->registered_at?->format('Y-m-d H:i:s'),
            'confirmed_at' => $reg->confirmed_at?->format('Y-m-d H:i:s'),
        ])->toArray();
    }

    /**
     * Get registration statistics for an event.
     */
    public function getStats(Event $event): array
    {
        $registrations = $event->registrations;

        return [
            'total' => $registrations->count(),
            'by_status' => [
                'pending' => $registrations->where('status', RegistrationStatus::PENDING)->count(),
                'confirmed' => $registrations->where('status', RegistrationStatus::CONFIRMED)->count(),
                'waitlisted' => $registrations->where('status', RegistrationStatus::WAITLISTED)->count(),
                'cancelled' => $registrations->where('status', RegistrationStatus::CANCELLED)->count(),
                'checked_in' => $registrations->where('status', RegistrationStatus::CHECKED_IN)->count(),
                'no_show' => $registrations->where('status', RegistrationStatus::NO_SHOW)->count(),
            ],
            'by_role' => $registrations->groupBy('participant_role')->map->count()->toArray(),
            'revenue' => [
                'total' => $registrations->whereIn('status', [
                    RegistrationStatus::CONFIRMED,
                    RegistrationStatus::CHECKED_IN,
                ])->sum('total_amount'),
                'pending' => $registrations->where('status', RegistrationStatus::PENDING)->sum('total_amount'),
                'currency' => $event->tickets->first()?->currency ?? 'EUR',
            ],
            'attendance' => [
                'expected' => $registrations->whereIn('status', [
                    RegistrationStatus::CONFIRMED,
                    RegistrationStatus::CHECKED_IN,
                ])->sum('quantity'),
                'checked_in' => $registrations->where('status', RegistrationStatus::CHECKED_IN)->sum('quantity'),
            ],
        ];
    }

    /**
     * Generate a unique registration number.
     */
    protected function generateRegistrationNumber(): string
    {
        do {
            $number = 'REG-' . strtoupper(Str::random(8));
        } while (EventRegistration::where('registration_number', $number)->exists());

        return $number;
    }

    /**
     * Generate a unique QR code.
     */
    protected function generateQRCode(): string
    {
        do {
            $code = Str::uuid()->toString();
        } while (EventRegistration::where('qr_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique payment number.
     */
    protected function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . strtoupper(Str::random(10));
        } while (RegistrationPayment::where('payment_number', $number)->exists());

        return $number;
    }

    /**
     * Determine the initial status for a registration.
     */
    protected function determineInitialStatus(Event $event, ?EventTicket $ticket, float $amount): RegistrationStatus
    {
        // Check if approval is required
        if ($event->requires_approval || $ticket?->requires_approval) {
            return RegistrationStatus::PENDING;
        }

        // Check if event is full (should go to waitlist)
        if ($event->isFull() && $event->waitlist_enabled) {
            return RegistrationStatus::WAITLISTED;
        }

        // If free, auto-confirm
        if ($amount == 0) {
            return RegistrationStatus::CONFIRMED;
        }

        // Otherwise, pending payment
        return RegistrationStatus::PENDING;
    }

    /**
     * Bulk update registration statuses.
     */
    public function bulkUpdateStatus(array $registrationIds, RegistrationStatus $status): int
    {
        return EventRegistration::whereIn('id', $registrationIds)
            ->update([
                'status' => $status,
                'confirmed_at' => $status === RegistrationStatus::CONFIRMED ? now() : null,
            ]);
    }

    /**
     * Check if a user is already registered for an event.
     */
    public function isAlreadyRegistered(Event $event, string $email, ?int $userId = null): bool
    {
        $query = $event->registrations()
            ->whereNotIn('status', [RegistrationStatus::CANCELLED]);

        if ($userId) {
            return $query->where(function ($q) use ($email, $userId): void {
                $q->where('email', $email)->orWhere('user_id', $userId);
            })->exists();
        }

        return $query->where('email', $email)->exists();
    }
}
