<?php

namespace App\Services\Event;

use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventFeedback;
use App\Models\Event\EventRegistration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventAnalyticsService
{
    protected TicketService $ticketService;
    protected CheckInService $checkInService;
    protected BadgeService $badgeService;

    public function __construct(
        TicketService $ticketService,
        CheckInService $checkInService,
        BadgeService $badgeService
    ) {
        $this->ticketService = $ticketService;
        $this->checkInService = $checkInService;
        $this->badgeService = $badgeService;
    }

    /**
     * Get comprehensive dashboard data for an event.
     */
    public function getDashboardData(Event $event): array
    {
        return Cache::remember(
            "event.{$event->id}.dashboard",
            now()->addMinutes(5),
            fn () => [
                'overview' => $this->getOverview($event),
                'registrations' => $this->getRegistrationStats($event),
                'tickets' => $this->ticketService->getTicketStats($event),
                'checkins' => $this->checkInService->getStats($event),
                'badges' => $this->badgeService->getStats($event),
                'revenue' => $this->getRevenueStats($event),
                'feedback' => $this->getFeedbackStats($event),
                'trends' => $this->getRegistrationTrends($event),
            ]
        );
    }

    /**
     * Get event overview statistics.
     */
    public function getOverview(Event $event): array
    {
        $registrations = $event->registrations;

        $confirmed = $registrations->whereIn('status', [
            RegistrationStatus::CONFIRMED,
            RegistrationStatus::CHECKED_IN,
        ]);

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status->label(),
                'type' => $event->type?->label(),
                'visibility' => $event->visibility?->label(),
                'start_date' => $event->start_date->format('d/m/Y H:i'),
                'end_date' => $event->end_date->format('d/m/Y H:i'),
                'days_until' => $event->start_date->isFuture() ? $event->start_date->diffInDays(now()) : 0,
                'is_ongoing' => $event->isOngoing(),
                'has_ended' => $event->hasEnded(),
            ],
            'capacity' => [
                'max' => $event->max_participants,
                'registered' => $confirmed->sum('quantity'),
                'available' => $event->max_participants
                    ? max(0, $event->max_participants - $confirmed->sum('quantity'))
                    : null,
                'utilization' => $event->max_participants
                    ? round(($confirmed->sum('quantity') / $event->max_participants) * 100, 1)
                    : null,
            ],
            'waitlist' => [
                'enabled' => $event->waitlist_enabled,
                'count' => $event->waitlist()->active()->count(),
                'capacity' => $event->waitlist_capacity,
            ],
        ];
    }

    /**
     * Get detailed registration statistics.
     */
    public function getRegistrationStats(Event $event): array
    {
        $registrations = $event->registrations;

        return [
            'total' => $registrations->count(),
            'total_attendees' => $registrations->sum('quantity'),
            'by_status' => [
                'pending' => $registrations->where('status', RegistrationStatus::PENDING)->count(),
                'confirmed' => $registrations->where('status', RegistrationStatus::CONFIRMED)->count(),
                'waitlisted' => $registrations->where('status', RegistrationStatus::WAITLISTED)->count(),
                'cancelled' => $registrations->where('status', RegistrationStatus::CANCELLED)->count(),
                'checked_in' => $registrations->where('status', RegistrationStatus::CHECKED_IN)->count(),
                'no_show' => $registrations->where('status', RegistrationStatus::NO_SHOW)->count(),
            ],
            'by_role' => $registrations
                ->groupBy(fn ($r) => $r->participant_role->value)
                ->map->count()
                ->toArray(),
            'by_company' => $registrations
                ->whereNotNull('company')
                ->groupBy('company')
                ->map->count()
                ->sortDesc()
                ->take(10)
                ->toArray(),
            'conversion_rate' => $registrations->count() > 0
                ? round(($registrations->whereIn('status', [
                    RegistrationStatus::CONFIRMED,
                    RegistrationStatus::CHECKED_IN,
                ])->count() / $registrations->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get revenue statistics.
     */
    public function getRevenueStats(Event $event): array
    {
        $registrations = $event->registrations()->with('payments')->get();

        $confirmedRegistrations = $registrations->whereIn('status', [
            RegistrationStatus::CONFIRMED,
            RegistrationStatus::CHECKED_IN,
        ]);

        $allPayments = $registrations->flatMap->payments;
        $completedPayments = $allPayments->where('status', 'completed');

        return [
            'expected' => [
                'amount' => $confirmedRegistrations->sum('total_amount'),
                'currency' => $event->tickets->first()?->currency ?? 'EUR',
            ],
            'collected' => [
                'amount' => $completedPayments->sum('amount'),
                'net_amount' => $completedPayments->sum('net_amount'),
                'fees' => $completedPayments->sum('fee'),
            ],
            'pending' => [
                'amount' => $registrations->where('status', RegistrationStatus::PENDING)->sum('total_amount'),
            ],
            'refunded' => [
                'amount' => $allPayments->where('status', 'refunded')->sum('refund_amount'),
                'count' => $allPayments->where('status', 'refunded')->count(),
            ],
            'by_ticket' => $confirmedRegistrations
                ->groupBy('ticket_id')
                ->map(function ($group) {
                    $ticket = $group->first()->ticket;
                    return [
                        'ticket_name' => $ticket?->name ?? 'Sans billet',
                        'count' => $group->count(),
                        'quantity' => $group->sum('quantity'),
                        'revenue' => $group->sum('total_amount'),
                    ];
                })
                ->values()
                ->toArray(),
            'by_promo_code' => $confirmedRegistrations
                ->whereNotNull('promo_code_id')
                ->groupBy('promo_code_id')
                ->map(function ($group) {
                    return [
                        'code' => $group->first()->promoCode?->code,
                        'uses' => $group->count(),
                        'discount_total' => $group->sum('discount_amount'),
                    ];
                })
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Get feedback statistics.
     */
    public function getFeedbackStats(Event $event): array
    {
        $feedback = EventFeedback::where('event_id', $event->id)->get();

        if ($feedback->isEmpty()) {
            return [
                'count' => 0,
                'response_rate' => 0,
                'overall_rating' => null,
                'nps' => null,
                'ratings' => [],
            ];
        }

        $confirmedCount = $event->registrations()
            ->whereIn('status', [
                RegistrationStatus::CONFIRMED,
                RegistrationStatus::CHECKED_IN,
            ])
            ->count();

        return [
            'count' => $feedback->count(),
            'response_rate' => $confirmedCount > 0
                ? round(($feedback->count() / $confirmedCount) * 100, 1)
                : 0,
            'overall_rating' => round($feedback->avg('overall_rating'), 1),
            'ratings' => [
                'content' => round($feedback->whereNotNull('content_rating')->avg('content_rating'), 1),
                'speaker' => round($feedback->whereNotNull('speaker_rating')->avg('speaker_rating'), 1),
                'venue' => round($feedback->whereNotNull('venue_rating')->avg('venue_rating'), 1),
                'organization' => round($feedback->whereNotNull('organization_rating')->avg('organization_rating'), 1),
            ],
            'nps' => $this->calculateNPS($feedback),
            'would_recommend' => [
                'yes' => $feedback->where('would_recommend', true)->count(),
                'no' => $feedback->where('would_recommend', false)->count(),
                'percentage' => round($feedback->where('would_recommend', true)->count() / $feedback->count() * 100, 1),
            ],
            'rating_distribution' => $feedback
                ->groupBy('overall_rating')
                ->map->count()
                ->sortKeys()
                ->toArray(),
        ];
    }

    /**
     * Calculate Net Promoter Score.
     */
    protected function calculateNPS(\Illuminate\Support\Collection $feedback): ?float
    {
        $npsScores = $feedback->whereNotNull('nps_score');

        if ($npsScores->isEmpty()) {
            return null;
        }

        $promoters = $npsScores->filter(fn ($f) => $f->nps_score >= 9)->count();
        $detractors = $npsScores->filter(fn ($f) => $f->nps_score <= 6)->count();
        $total = $npsScores->count();

        return round((($promoters - $detractors) / $total) * 100, 1);
    }

    /**
     * Get registration trends over time.
     */
    public function getRegistrationTrends(Event $event): array
    {
        $registrations = $event->registrations()
            ->select(
                DB::raw('DATE(registered_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity) as attendees'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereNotNull('registered_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily' => $registrations->map(fn ($r) => [
                'date' => $r->date,
                'registrations' => $r->count,
                'attendees' => $r->attendees,
                'revenue' => $r->revenue,
            ])->toArray(),
            'cumulative' => $this->calculateCumulative($registrations),
        ];
    }

    /**
     * Calculate cumulative registration data.
     */
    protected function calculateCumulative($registrations): array
    {
        $cumulative = [];
        $totalRegistrations = 0;
        $totalAttendees = 0;
        $totalRevenue = 0;

        foreach ($registrations as $r) {
            $totalRegistrations += $r->count;
            $totalAttendees += $r->attendees;
            $totalRevenue += $r->revenue;

            $cumulative[] = [
                'date' => $r->date,
                'registrations' => $totalRegistrations,
                'attendees' => $totalAttendees,
                'revenue' => $totalRevenue,
            ];
        }

        return $cumulative;
    }

    /**
     * Get session analytics.
     */
    public function getSessionAnalytics(Event $event): array
    {
        $sessions = $event->sessions()->with(['speakers', 'attendees', 'checkins', 'feedback'])->get();

        return $sessions->map(function ($session) {
            $feedback = $session->feedback;

            return [
                'id' => $session->id,
                'title' => $session->title,
                'format' => $session->format->label(),
                'start_time' => $session->start_time->format('d/m/Y H:i'),
                'duration' => $session->duration_for_humans,
                'speakers' => $session->speakers->pluck('name')->toArray(),
                'capacity' => $session->capacity,
                'registered' => $session->attendees->count(),
                'checked_in' => $session->checkins->count(),
                'attendance_rate' => $session->getAttendanceRate(),
                'feedback' => [
                    'count' => $feedback->count(),
                    'average_rating' => $feedback->avg('overall_rating'),
                ],
            ];
        })->toArray();
    }

    /**
     * Get sponsor analytics.
     */
    public function getSponsorAnalytics(Event $event): array
    {
        $sponsors = $event->sponsors()->get();

        return [
            'total' => $sponsors->count(),
            'by_tier' => $sponsors->groupBy('tier')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount'),
                    'sponsors' => $group->pluck('name')->toArray(),
                ];
            })->toArray(),
            'total_sponsorship' => $sponsors->sum('amount'),
            'currency' => $sponsors->first()?->currency ?? 'EUR',
        ];
    }

    /**
     * Export analytics data.
     */
    public function exportAnalytics(Event $event, string $format = 'json'): array
    {
        $data = [
            'event' => [
                'title' => $event->title,
                'start_date' => $event->start_date->toISOString(),
                'end_date' => $event->end_date->toISOString(),
                'exported_at' => now()->toISOString(),
            ],
            'overview' => $this->getOverview($event),
            'registrations' => $this->getRegistrationStats($event),
            'revenue' => $this->getRevenueStats($event),
            'feedback' => $this->getFeedbackStats($event),
            'sessions' => $this->getSessionAnalytics($event),
            'sponsors' => $this->getSponsorAnalytics($event),
        ];

        return $data;
    }

    /**
     * Compare two events.
     */
    public function compareEvents(Event $eventA, Event $eventB): array
    {
        return [
            'event_a' => [
                'title' => $eventA->title,
                'stats' => $this->getOverview($eventA),
                'registrations' => $this->getRegistrationStats($eventA),
                'revenue' => $this->getRevenueStats($eventA),
            ],
            'event_b' => [
                'title' => $eventB->title,
                'stats' => $this->getOverview($eventB),
                'registrations' => $this->getRegistrationStats($eventB),
                'revenue' => $this->getRevenueStats($eventB),
            ],
            'comparison' => [
                'registration_diff' => $eventA->registrations->count() - $eventB->registrations->count(),
                'revenue_diff' => $eventA->registrations->sum('total_amount') - $eventB->registrations->sum('total_amount'),
                'attendance_rate_diff' => ($eventA->getAttendanceRate() ?? 0) - ($eventB->getAttendanceRate() ?? 0),
            ],
        ];
    }

    /**
     * Get real-time dashboard data (lighter, for frequent updates).
     */
    public function getRealTimeData(Event $event): array
    {
        return [
            'registrations' => [
                'total' => $event->registrations()->count(),
                'confirmed' => $event->registrations()
                    ->whereIn('status', [RegistrationStatus::CONFIRMED, RegistrationStatus::CHECKED_IN])
                    ->count(),
            ],
            'checkins' => [
                'total' => $event->registrations()->where('status', RegistrationStatus::CHECKED_IN)->count(),
            ],
            'last_registration' => $event->registrations()
                ->latest('registered_at')
                ->first(['first_name', 'last_name', 'registered_at']),
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Clear analytics cache for an event.
     */
    public function clearCache(Event $event): void
    {
        Cache::forget("event.{$event->id}.dashboard");
    }
}
