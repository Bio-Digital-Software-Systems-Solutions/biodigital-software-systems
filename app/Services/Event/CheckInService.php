<?php

namespace App\Services\Event;

use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventCheckin;
use App\Models\Event\EventRegistration;
use App\Models\Event\EventSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    /**
     * Check in a registration by QR code.
     */
    public function checkInByQRCode(
        string $qrCode,
        ?User $checkedInBy = null,
        ?EventSession $session = null,
        array $metadata = []
    ): array {
        $registration = EventRegistration::where('qr_code', $qrCode)
            ->with(['event', 'ticket'])
            ->first();

        if (! $registration) {
            return [
                'success' => false,
                'message' => 'QR code invalide ou inscription introuvable.',
                'registration' => null,
            ];
        }

        return $this->processCheckIn($registration, $checkedInBy, $session, $metadata);
    }

    /**
     * Check in a registration by registration number.
     */
    public function checkInByNumber(
        string $registrationNumber,
        ?User $checkedInBy = null,
        ?EventSession $session = null,
        array $metadata = []
    ): array {
        $registration = EventRegistration::where('registration_number', $registrationNumber)
            ->with(['event', 'ticket'])
            ->first();

        if (! $registration) {
            return [
                'success' => false,
                'message' => 'Numéro d\'inscription invalide.',
                'registration' => null,
            ];
        }

        return $this->processCheckIn($registration, $checkedInBy, $session, $metadata);
    }

    /**
     * Check in directly by registration model (for manual check-in).
     */
    public function checkInByRegistration(
        EventRegistration $registration,
        ?User $checkedInBy = null,
        ?EventSession $session = null,
        array $metadata = []
    ): array {
        // Load relationships if not already loaded
        if (! $registration->relationLoaded('event')) {
            $registration->load(['event', 'ticket']);
        }

        return $this->processCheckIn($registration, $checkedInBy, $session, $metadata);
    }

    /**
     * Process the check-in.
     */
    protected function processCheckIn(
        EventRegistration $registration,
        ?User $checkedInBy = null,
        ?EventSession $session = null,
        array $metadata = []
    ): array {
        // Validate registration status
        if ($registration->status === RegistrationStatus::CANCELLED) {
            return [
                'success' => false,
                'message' => 'Cette inscription a été annulée.',
                'registration' => $registration,
            ];
        }

        if ($registration->status === RegistrationStatus::PENDING) {
            return [
                'success' => false,
                'message' => 'Cette inscription est en attente de confirmation.',
                'registration' => $registration,
            ];
        }

        if ($registration->status === RegistrationStatus::WAITLISTED) {
            return [
                'success' => false,
                'message' => 'Cette inscription est sur liste d\'attente.',
                'registration' => $registration,
            ];
        }

        // Check if already checked in for this session (if session provided)
        if ($session instanceof \App\Models\Event\EventSession) {
            $existingCheckin = EventCheckin::where('registration_id', $registration->id)
                ->where('session_id', $session->id)
                ->where('check_type', 'entry')
                ->whereNull('checked_out_at')
                ->first();

            if ($existingCheckin) {
                return [
                    'success' => false,
                    'message' => 'Déjà enregistré pour cette session.',
                    'registration' => $registration,
                    'checkin' => $existingCheckin,
                ];
            }
        } else {
            // Check if already checked in for the event (main entry)
            $existingCheckin = EventCheckin::where('registration_id', $registration->id)
                ->whereNull('session_id')
                ->where('check_type', 'entry')
                ->whereNull('checked_out_at')
                ->first();

            if ($existingCheckin && $registration->status === RegistrationStatus::CHECKED_IN) {
                return [
                    'success' => false,
                    'message' => 'Déjà enregistré pour cet événement.',
                    'registration' => $registration,
                    'checkin' => $existingCheckin,
                ];
            }
        }

        // Create the check-in record
        return DB::transaction(function () use ($registration, $checkedInBy, $session, $metadata): array {
            $checkin = EventCheckin::create([
                'registration_id' => $registration->id,
                'session_id' => $session?->id,
                'checked_in_by' => $checkedInBy?->id,
                'check_type' => 'entry',
                'method' => $metadata['method'] ?? 'qr_code',
                'device_id' => $metadata['device_id'] ?? null,
                'location' => $metadata['location'] ?? null,
                'metadata' => $metadata,
                'checked_in_at' => now(),
            ]);

            // Update registration status only for main event check-in
            if (! $session && $registration->status !== RegistrationStatus::CHECKED_IN) {
                $registration->update([
                    'status' => RegistrationStatus::CHECKED_IN,
                ]);
            }

            // Clear cache
            $this->clearCheckInCache($registration->event_id);

            return [
                'success' => true,
                'message' => 'Check-in réussi !',
                'registration' => $registration->fresh(),
                'checkin' => $checkin,
            ];
        });
    }

    /**
     * Check out a registration.
     */
    public function checkOut(
        EventRegistration $registration,
        ?EventSession $session = null
    ): array {
        $query = EventCheckin::where('registration_id', $registration->id)
            ->where('check_type', 'entry')
            ->whereNull('checked_out_at');

        if ($session instanceof \App\Models\Event\EventSession) {
            $query->where('session_id', $session->id);
        } else {
            $query->whereNull('session_id');
        }

        $checkin = $query->latest('checked_in_at')->first();

        if (! $checkin) {
            return [
                'success' => false,
                'message' => 'Aucun check-in trouvé à clôturer.',
            ];
        }

        $checkin->update([
            'checked_out_at' => now(),
        ]);

        $this->clearCheckInCache($registration->event_id);

        return [
            'success' => true,
            'message' => 'Check-out réussi.',
            'checkin' => $checkin->fresh(),
        ];
    }

    /**
     * Get check-in statistics for an event.
     */
    public function getStats(Event $event): array
    {
        return Cache::remember(
            "event.{$event->id}.checkin_stats",
            now()->addMinutes(2),
            function () use ($event): array {
                $totalConfirmed = $event->registrations()
                    ->whereIn('status', [RegistrationStatus::CONFIRMED, RegistrationStatus::CHECKED_IN])
                    ->sum('quantity');

                $checkedIn = $event->registrations()
                    ->where('status', RegistrationStatus::CHECKED_IN)
                    ->sum('quantity');

                return [
                    'total_expected' => $totalConfirmed,
                    'checked_in' => $checkedIn,
                    'not_checked_in' => $totalConfirmed - $checkedIn,
                    'attendance_rate' => $totalConfirmed > 0
                        ? round(($checkedIn / $totalConfirmed) * 100, 1)
                        : 0,
                    'by_hour' => $this->getCheckInsByHour($event),
                    'by_ticket' => $this->getCheckInsByTicket($event),
                ];
            }
        );
    }

    /**
     * Get check-ins grouped by hour.
     */
    protected function getCheckInsByHour(Event $event): array
    {
        $checkins = EventCheckin::whereHas('registration', function ($q) use ($event): void {
            $q->where('event_id', $event->id);
        })
            ->whereNull('session_id')
            ->where('check_type', 'entry')
            ->get()
            ->groupBy(fn ($c) => $c->checked_in_at->format('H:00'));

        return $checkins->map->count()->toArray();
    }

    /**
     * Get check-ins grouped by ticket type.
     */
    protected function getCheckInsByTicket(Event $event): array
    {
        return $event->registrations()
            ->where('status', RegistrationStatus::CHECKED_IN)
            ->with('ticket')
            ->get()
            ->groupBy('ticket.name')
            ->map(fn ($group): array => [
                'count' => $group->sum('quantity'),
                'ticket_id' => $group->first()->ticket_id,
            ])
            ->toArray();
    }

    /**
     * Get recent check-ins for an event.
     */
    public function getRecentCheckIns(Event $event, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return EventCheckin::whereHas('registration', function ($q) use ($event): void {
            $q->where('event_id', $event->id);
        })
            ->with(['registration.ticket', 'checkedInBy'])
            ->whereNull('session_id')
            ->where('check_type', 'entry')
            ->latest('checked_in_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get session attendance.
     */
    public function getSessionAttendance(EventSession $session): array
    {
        $checkins = EventCheckin::where('session_id', $session->id)
            ->where('check_type', 'entry')
            ->with('registration')
            ->get();

        return [
            'session_id' => $session->id,
            'session_name' => $session->title,
            'capacity' => $session->capacity,
            'registered' => $session->attendees()->count(),
            'checked_in' => $checkins->count(),
            'attendance_rate' => $session->attendees_count > 0
                ? round(($checkins->count() / $session->attendees_count) * 100, 1)
                : 0,
        ];
    }

    /**
     * Mark registrations as no-show after event ends.
     */
    public function markNoShows(Event $event): int
    {
        if (! $event->hasEnded()) {
            return 0;
        }

        return $event->registrations()
            ->where('status', RegistrationStatus::CONFIRMED)
            ->update(['status' => RegistrationStatus::NO_SHOW]);
    }

    /**
     * Search attendees for check-in.
     */
    public function searchAttendees(Event $event, string $search): \Illuminate\Database\Eloquent\Collection
    {
        return $event->registrations()
            ->whereIn('status', [
                RegistrationStatus::CONFIRMED,
                RegistrationStatus::CHECKED_IN,
            ])
            ->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            })
            ->with(['ticket', 'checkins'])
            ->limit(20)
            ->get();
    }

    /**
     * Get check-in log for a registration.
     */
    public function getCheckInHistory(EventRegistration $registration): \Illuminate\Database\Eloquent\Collection
    {
        return $registration->checkins()
            ->with(['session', 'checkedInBy'])
            ->orderBy('checked_in_at', 'desc')
            ->get();
    }

    /**
     * Undo a check-in.
     */
    public function undoCheckIn(EventCheckin $checkin): bool
    {
        return DB::transaction(function () use ($checkin): true {
            $registration = $checkin->registration;

            // Delete the check-in record
            $checkin->delete();

            // If this was the main event check-in, revert status
            if (! $checkin->session_id) {
                // Check if there are other main check-ins
                $hasOtherCheckins = EventCheckin::where('registration_id', $registration->id)
                    ->whereNull('session_id')
                    ->where('check_type', 'entry')
                    ->exists();

                if (! $hasOtherCheckins) {
                    $registration->update([
                        'status' => RegistrationStatus::CONFIRMED,
                    ]);
                }
            }

            $this->clearCheckInCache($registration->event_id);

            return true;
        });
    }

    /**
     * Clear check-in related cache.
     */
    protected function clearCheckInCache(int $eventId): void
    {
        Cache::forget("event.{$eventId}.checkin_stats");
    }

    /**
     * Get live check-in feed for an event (for real-time updates).
     */
    public function getLiveFeed(Event $event, int $since = 0): array
    {
        $query = EventCheckin::whereHas('registration', function ($q) use ($event): void {
            $q->where('event_id', $event->id);
        })
            ->with(['registration.ticket', 'checkedInBy', 'session'])
            ->where('check_type', 'entry');

        if ($since > 0) {
            $query->where('id', '>', $since);
        }

        $checkins = $query->latest('checked_in_at')
            ->limit(50)
            ->get();

        return [
            'checkins' => $checkins,
            'last_id' => $checkins->max('id') ?? $since,
            'stats' => $this->getStats($event),
        ];
    }
}
