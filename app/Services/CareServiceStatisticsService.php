<?php

namespace App\Services;

use App\Models\CareService;
use App\Models\CareServiceAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CareServiceStatisticsService
{
    /**
     * Get appointments distribution by pastor for a given period
     *
     * @param  string  $period  'week', 'month', 'quarter', 'year'
     * @return Collection<int, array{pastor_id: int, pastor_name: string, count: int, percentage: float}>
     */
    public function getAppointmentsByPastor(string $period = 'month'): Collection
    {
        $dates = $this->getPeriodDates($period);

        $appointments = CareService::query()
            ->select('pastor_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->groupBy('pastor_id')
            ->get();

        $total = $appointments->sum('count');

        return $appointments->map(function ($item) use ($total): array {
            $pastor = User::find($item->pastor_id);

            return [
                'pastor_id' => $item->pastor_id,
                'pastor_name' => $pastor ? "{$pastor->first_name} {$pastor->last_name}" : 'Inconnu',
                'count' => $item->count,
                'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * Get average duration of appointments for a given period
     */
    public function getAverageDuration(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        $stats = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->whereIn('status', ['completed', 'confirmed'])
            ->selectRaw('AVG(duration_minutes) as average, MIN(duration_minutes) as min, MAX(duration_minutes) as max, COUNT(*) as count')
            ->first();

        return [
            'average' => round($stats->average ?? 0, 1),
            'min' => $stats->min ?? 0,
            'max' => $stats->max ?? 0,
            'count' => $stats->count ?? 0,
            'formatted' => $this->formatDuration($stats->average ?? 0),
        ];
    }

    /**
     * Get distribution by theme for a given period
     *
     * @return Collection<int, array{theme: string, theme_label: string, count: int, percentage: float}>
     */
    public function getDistributionByTheme(string $period = 'month', ?int $agentId = null): Collection
    {
        $dates = $this->getPeriodDates($period);

        $appointments = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->select('theme', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->whereNotNull('theme')
            ->groupBy('theme')
            ->get();

        $total = $appointments->sum('count');

        return $appointments->map(fn ($item): array => [
            'theme' => $item->theme,
            'theme_label' => CareService::THEMES[$item->theme] ?? $item->theme,
            'count' => $item->count,
            'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
        ])->sortByDesc('count')->values();
    }

    /**
     * Get follow-up frequency statistics
     */
    public function getFollowUpFrequency(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        $totalAppointments = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $followUpCount = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->isFollowUp()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $initialCount = $totalAppointments - $followUpCount;

        return [
            'total' => $totalAppointments,
            'follow_ups' => $followUpCount,
            'initial' => $initialCount,
            'follow_up_rate' => $totalAppointments > 0 ? round(($followUpCount / $totalAppointments) * 100, 1) : 0,
            'average_follow_ups_per_initial' => $initialCount > 0 ? round($followUpCount / $initialCount, 2) : 0,
        ];
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        $totalAppointments = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $transferredCount = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->transferred()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        // Get transfers by destination
        $transfersByDestination = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->transferred()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->select('transferred_to_id', DB::raw('COUNT(*) as count'))
            ->groupBy('transferred_to_id')
            ->get()
            ->map(function ($item): array {
                $user = User::find($item->transferred_to_id);

                return [
                    'user_id' => $item->transferred_to_id,
                    'user_name' => $user ? "{$user->first_name} {$user->last_name}" : 'Inconnu',
                    'count' => $item->count,
                ];
            })
            ->sortByDesc('count')
            ->values();

        return [
            'total' => $totalAppointments,
            'transferred' => $transferredCount,
            'transfer_rate' => $totalAppointments > 0 ? round(($transferredCount / $totalAppointments) * 100, 1) : 0,
            'by_destination' => $transfersByDestination,
        ];
    }

    /**
     * Get incoming (new) appointments that need attention
     */
    public function getIncomingAppointments(int $limit = 20, ?int $agentId = null): Collection
    {
        return CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->pending()
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Get availabilities for a specific pastor
     */
    public function getPastorAvailabilities(int $pastorId): Collection
    {
        $availabilities = CareServiceAvailability::query()
            ->where('pastor_id', $pastorId)
            ->active()
            ->with('pastor')
            ->get();

        if ($availabilities->isEmpty()) {
            return collect([]);
        }

        $pastor = $availabilities->first()->pastor;

        return collect([[
            'pastor_id' => $pastorId,
            'pastor_name' => $pastor ? "{$pastor->first_name} {$pastor->last_name}" : 'Inconnu',
            'availabilities' => $availabilities->map(function (\App\Models\CareServiceAvailability $availability) use ($pastorId): array {
                $slotsWithStatus = $this->getTimeSlotsWithStatus($availability, $pastorId);

                return [
                    'id' => $availability->id,
                    'type' => $availability->type,
                    'day_of_week' => $availability->day_of_week,
                    'day_label' => $availability->day_name ?? null,
                    'specific_date' => $availability->specific_date?->toDateString(),
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'slot_duration' => $availability->slot_duration,
                    'consultation_mode' => $availability->consultation_mode,
                    'location' => $availability->location,
                    'room' => $availability->room,
                    'meeting_link' => $availability->meeting_link,
                    'notes' => $availability->notes,
                    'time_slots' => $slotsWithStatus,
                    'slots_count' => count($slotsWithStatus),
                ];
            })->values(),
            'total_slots_per_week' => $this->calculateWeeklySlots($availabilities),
        ]]);
    }

    /**
     * Get availabilities for a specific agent (User with any role).
     * This is a wrapper around getPastorAvailabilities that works for any agent type.
     */
    public function getAgentAvailabilities(int $agentId): Collection
    {
        // Check if the agent has availabilities defined (pastors/care-service agents)
        $hasAvailabilities = CareServiceAvailability::query()
            ->where('pastor_id', $agentId)
            ->active()
            ->exists();

        if (! $hasAvailabilities) {
            return collect([]);
        }

        // Reuse the pastor availabilities logic
        return $this->getPastorAvailabilities($agentId);
    }

    /**
     * Get all pastor availabilities overview
     */
    public function getAllAvailabilities(): Collection
    {
        $availabilities = CareServiceAvailability::query()
            ->active()
            ->with('pastor')
            ->get();

        // Group by pastor
        return $availabilities->groupBy('pastor_id')->map(function (\Illuminate\Support\Collection $items, int $pastorId): array {
            $pastor = $items->first()->pastor;

            return [
                'pastor_id' => $pastorId,
                'pastor_name' => $pastor ? "{$pastor->first_name} {$pastor->last_name}" : 'Inconnu',
                'availabilities' => $items->map(function (\App\Models\CareServiceAvailability $availability) use ($pastorId): array {
                    // Calculate time slots with status
                    $slotsWithStatus = $this->getTimeSlotsWithStatus($availability, $pastorId);

                    return [
                        'id' => $availability->id,
                        'type' => $availability->type,
                        'day_of_week' => $availability->day_of_week,
                        'day_label' => $availability->day_name ?? null,
                        'specific_date' => $availability->specific_date?->toDateString(),
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                        'slot_duration' => $availability->slot_duration,
                        'consultation_mode' => $availability->consultation_mode,
                        'location' => $availability->location,
                        'room' => $availability->room,
                        'meeting_link' => $availability->meeting_link,
                        'notes' => $availability->notes,
                        'time_slots' => $slotsWithStatus,
                        'slots_count' => count($slotsWithStatus),
                    ];
                })->values(),
                'total_slots_per_week' => $this->calculateWeeklySlots($items),
            ];
        })->values();
    }

    /**
     * Get time slots with their status (available, occupied, passed)
     *
     * @return array<int, array{time: string, status: string}>
     */
    protected function getTimeSlotsWithStatus(CareServiceAvailability $availability, int $pastorId): array
    {
        $slots = $availability->getTimeSlotsForDate(now());
        $slotsWithStatus = [];

        // Determine the date for these slots
        $date = $availability->type === 'specific_date'
            ? $availability->specific_date
            : $this->getNextDateForDayOfWeek($availability->day_of_week);

        if (! $date) {
            // No valid date, return all slots as available
            foreach ($slots as $slot) {
                $slotsWithStatus[] = [
                    'time' => $slot,
                    'status' => 'available',
                ];
            }

            return $slotsWithStatus;
        }

        // Get existing appointments for this pastor on this date
        $existingAppointments = CareService::query()
            ->where('pastor_id', $pastorId)
            ->where('appointment_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('appointment_time')
            ->map(fn (\DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $time): string =>
                // Normalize time format to H:i
                Carbon::parse($time)->format('H:i'))
            ->toArray();

        now();

        foreach ($slots as $slot) {
            $slotDateTime = Carbon::parse($date->toDateString().' '.$slot);

            if ($slotDateTime->isPast()) {
                $status = 'passed';
            } elseif (in_array($slot, $existingAppointments)) {
                $status = 'occupied';
            } else {
                $status = 'available';
            }

            $slotsWithStatus[] = [
                'time' => $slot,
                'status' => $status,
            ];
        }

        return $slotsWithStatus;
    }

    /**
     * Get the next occurrence of a given day of week
     */
    protected function getNextDateForDayOfWeek(?int $dayOfWeek): ?Carbon
    {
        if ($dayOfWeek === null) {
            return null;
        }

        $today = now();

        // If today is the same day of week, use today
        if ($today->dayOfWeek === $dayOfWeek) {
            return $today;
        }

        // Find next occurrence
        return $today->copy()->next($dayOfWeek);
    }

    /**
     * Get status distribution statistics
     */
    public function getStatusDistribution(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        $stats = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($stats);

        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent',
        ];

        $distribution = [];
        foreach ($statusLabels as $status => $label) {
            $count = $stats[$status] ?? 0;
            $distribution[] = [
                'status' => $status,
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return [
            'total' => $total,
            'distribution' => $distribution,
        ];
    }

    /**
     * Get trend data for charts (appointments over time)
     */
    public function getTrendData(string $period = 'month', string $groupBy = 'day', ?int $agentId = null): Collection
    {
        $dates = $this->getPeriodDates($period);

        $format = match ($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->select(
                DB::raw("DATE_FORMAT(appointment_date, '{$format}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed")
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get comprehensive care service dashboard statistics
     *
     * @param  string  $period  The period to calculate stats for
     * @param  int|null  $agentId  Optional agent ID to filter stats (for pastors/agents viewing their own data)
     */
    public function getCareServiceDashboardStats(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        // Build base query with optional pastor filter
        $baseQuery = fn () => CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId));

        // Global counts
        $total = (clone $baseQuery())
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $pending = (clone $baseQuery())
            ->pending()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $confirmed = (clone $baseQuery())
            ->confirmed()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $completed = (clone $baseQuery())
            ->completed()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $cancelled = (clone $baseQuery())
            ->cancelled()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        // This week stats
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $thisWeekCount = (clone $baseQuery())
            ->whereBetween('appointment_date', [$thisWeekStart, $thisWeekEnd])
            ->count();

        // Next week stats
        $nextWeekStart = now()->addWeek()->startOfWeek();
        $nextWeekEnd = now()->addWeek()->endOfWeek();
        $nextWeekCount = (clone $baseQuery())
            ->whereBetween('appointment_date', [$nextWeekStart, $nextWeekEnd])
            ->count();

        // Completion rate
        $completionRate = ($completed + $cancelled) > 0
            ? round(($completed / ($completed + $cancelled)) * 100, 1)
            : 0;

        return [
            'period' => [
                'start' => $dates['start']->toDateString(),
                'end' => $dates['end']->toDateString(),
                'label' => $this->getPeriodLabel($period),
            ],
            'overview' => [
                'total' => $total,
                'pending' => $pending,
                'confirmed' => $confirmed,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'this_week' => $thisWeekCount,
                'next_week' => $nextWeekCount,
                'completion_rate' => $completionRate,
            ],
            'average_duration' => $this->getAverageDuration($period, $agentId),
            'by_pastor' => $agentId ? [] : $this->getAppointmentsByPastor($period),
            'by_theme' => $this->getDistributionByTheme($period, $agentId),
            'by_status' => $this->getStatusDistribution($period, $agentId),
            'follow_ups' => $this->getFollowUpFrequency($period, $agentId),
            'transfers' => $this->getTransferStatistics($period, $agentId),
            'trend' => $this->getTrendData($period, 'day', $agentId),
            'incoming' => $this->getIncomingAppointments(10, $agentId),
            'availabilities' => $agentId ? $this->getAgentAvailabilities($agentId) : $this->getAllAvailabilities(),
            'analytics' => $this->getAnalyticsData($period, $agentId),
        ];
    }

    /**
     * Get analytics data for the dashboard charts
     */
    public function getAnalyticsData(string $period = 'month', ?int $agentId = null): array
    {
        $dates = $this->getPeriodDates($period);

        // Status colors
        $statusColors = [
            'pending' => '#F59E0B',
            'confirmed' => '#3B82F6',
            'completed' => '#10B981',
            'cancelled' => '#EF4444',
            'no_show' => '#6B7280',
        ];

        // Status labels
        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show' => 'Non présenté',
        ];

        // Appointments by status for donut chart
        $byStatus = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->groupBy('status')
            ->get()
            ->map(fn ($item): array => [
                'label' => $statusLabels[$item->status] ?? $item->status,
                'value' => (int) $item->count,
                'color' => $statusColors[$item->status] ?? '#6B7280',
            ])->values()->toArray();

        // Theme colors
        $themeColors = ['#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16', '#EF4444', '#06B6D4'];

        // Appointments by theme for donut chart
        $byTheme = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->select('theme', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->whereNotNull('theme')
            ->groupBy('theme')
            ->get()
            ->map(fn ($item, $index): array => [
                'label' => CareService::THEMES[$item->theme] ?? $item->theme,
                'value' => (int) $item->count,
                'color' => $themeColors[$index % count($themeColors)],
            ])->values()->toArray();

        // Pastor colors
        $pastorColors = ['#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];

        // Appointments by pastor for donut chart (skip if filtering by pastor)
        $byPastor = $agentId ? [] : CareService::query()
            ->select('pastor_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->groupBy('pastor_id')
            ->get()
            ->map(function ($item, $index) use ($pastorColors): array {
                $pastor = User::find($item->pastor_id);

                return [
                    'label' => $pastor ? "{$pastor->first_name} {$pastor->last_name}" : 'Inconnu',
                    'value' => (int) $item->count,
                    'color' => $pastorColors[$index % count($pastorColors)],
                ];
            })->sortByDesc('value')->values()->take(10)->toArray();

        // Mode colors
        $modeColors = [
            'in_person' => '#10B981',
            'zoom' => '#3B82F6',
            'hybrid' => '#8B5CF6',
        ];

        $modeLabels = [
            'in_person' => 'En présentiel',
            'zoom' => 'Visioconférence',
            'hybrid' => 'Hybride',
        ];

        // Appointments by mode for donut chart
        $byMode = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->select('location_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->groupBy('location_type')
            ->get()
            ->map(fn ($item): array => [
                'label' => $modeLabels[$item->location_type] ?? $item->location_type,
                'value' => (int) $item->count,
                'color' => $modeColors[$item->location_type] ?? '#6B7280',
            ])->values()->toArray();

        // Global progress
        $total = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $completed = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->completed()
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->count();

        $globalProgress = [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];

        // Velocity data
        $velocity = $this->calculateVelocity($agentId);

        // Evolution data for different periods
        $appointmentEvolution = [
            'weekly' => $this->getEvolutionData('weekly', $agentId),
            'monthly' => $this->getEvolutionData('monthly', $agentId),
            'quarterly' => $this->getEvolutionData('quarterly', $agentId),
        ];

        // Completion by pastor (skip if filtering by pastor)
        $completionByPastor = $agentId ? [] : $this->getCompletionByPastor($period);

        // Follow-up and transfer data
        $followUps = $this->getFollowUpFrequency($period, $agentId);
        $transfers = $this->getTransferStatistics($period, $agentId);

        return [
            'appointments_by_status' => $byStatus,
            'appointments_by_theme' => $byTheme,
            'appointments_by_pastor' => $byPastor,
            'appointments_by_mode' => $byMode,
            'global_progress' => $globalProgress,
            'velocity' => $velocity,
            'appointment_evolution' => $appointmentEvolution,
            'completion_by_pastor' => $completionByPastor,
            'follow_ups' => $followUps,
            'transfers' => $transfers,
        ];
    }

    /**
     * Calculate velocity metrics (completed appointments per period)
     */
    protected function calculateVelocity(?int $agentId = null): array
    {
        // Daily velocity (last 30 days)
        $dailyStart = now()->subDays(30);
        $dailyCompleted = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->completed()
            ->where('appointment_date', '>=', $dailyStart)
            ->count();
        $dailyValue = round($dailyCompleted / 30, 1);

        // Weekly velocity (last 8 weeks)
        $weeklyStart = now()->subWeeks(8);
        $weeklyCompleted = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->completed()
            ->where('appointment_date', '>=', $weeklyStart)
            ->count();
        $weeklyValue = round($weeklyCompleted / 8, 1);

        // Monthly velocity (last 12 months)
        $monthlyStart = now()->subMonths(12);
        $monthlyCompleted = CareService::query()
            ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
            ->completed()
            ->where('appointment_date', '>=', $monthlyStart)
            ->count();
        $monthlyValue = round($monthlyCompleted / 12, 1);

        return [
            'daily' => [
                'value' => $dailyValue,
                'total' => $dailyCompleted,
                'period_count' => 30,
                'max' => 100,
                'label' => 'jour',
            ],
            'weekly' => [
                'value' => $weeklyValue,
                'total' => $weeklyCompleted,
                'period_count' => 8,
                'max' => 100,
                'label' => 'semaine',
            ],
            'monthly' => [
                'value' => $monthlyValue,
                'total' => $monthlyCompleted,
                'period_count' => 12,
                'max' => 100,
                'label' => 'mois',
            ],
        ];
    }

    /**
     * Get evolution data for area chart
     */
    protected function getEvolutionData(string $groupBy, ?int $agentId = null): array
    {
        $data = [];

        switch ($groupBy) {
            case 'weekly':
                // Last 6 weeks
                for ($i = 5; $i >= 0; $i--) {
                    $start = now()->subWeeks($i)->startOfWeek();
                    $end = now()->subWeeks($i)->endOfWeek();

                    $created = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->whereBetween('created_at', [$start, $end])
                        ->count();

                    $completed = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->completed()
                        ->whereBetween('appointment_date', [$start, $end])
                        ->count();

                    $data[] = [
                        'label' => 'S'.($i === 0 ? now()->weekOfYear : now()->subWeeks($i)->weekOfYear),
                        'created' => $created,
                        'completed' => $completed,
                    ];
                }
                break;

            case 'monthly':
                // Last 6 months
                for ($i = 5; $i >= 0; $i--) {
                    $start = now()->subMonths($i)->startOfMonth();
                    $end = now()->subMonths($i)->endOfMonth();

                    $created = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->whereBetween('created_at', [$start, $end])
                        ->count();

                    $completed = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->completed()
                        ->whereBetween('appointment_date', [$start, $end])
                        ->count();

                    $data[] = [
                        'label' => $start->format('M'),
                        'created' => $created,
                        'completed' => $completed,
                    ];
                }
                break;

            case 'quarterly':
                // Last 4 quarters
                for ($i = 3; $i >= 0; $i--) {
                    $start = now()->subQuarters($i)->startOfQuarter();
                    $end = now()->subQuarters($i)->endOfQuarter();

                    $created = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->whereBetween('created_at', [$start, $end])
                        ->count();

                    $completed = CareService::query()
                        ->when($agentId, fn ($q) => $q->where('assigned_agent_id', $agentId))
                        ->completed()
                        ->whereBetween('appointment_date', [$start, $end])
                        ->count();

                    $data[] = [
                        'label' => 'T'.$start->quarter,
                        'created' => $created,
                        'completed' => $completed,
                    ];
                }
                break;
        }

        return $data;
    }

    /**
     * Get completion rate by pastor
     */
    protected function getCompletionByPastor(string $period): array
    {
        $dates = $this->getPeriodDates($period);
        $colors = ['#8B5CF6', '#10B981', '#F97316', '#3B82F6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];

        return CareService::query()
            ->select('pastor_id')
            ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
            ->groupBy('pastor_id')
            ->get()
            ->map(function ($item, $index) use ($dates, $colors): array {
                $pastor = User::find($item->pastor_id);

                $total = CareService::query()
                    ->where('pastor_id', $item->pastor_id)
                    ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
                    ->count();

                $completed = CareService::completed()
                    ->where('pastor_id', $item->pastor_id)
                    ->whereBetween('appointment_date', [$dates['start'], $dates['end']])
                    ->count();

                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

                return [
                    'name' => $pastor ? "{$pastor->first_name} {$pastor->last_name}" : 'Inconnu',
                    'value' => $rate,
                    'color' => $colors[$index % count($colors)],
                    'completed' => $completed,
                    'total' => $total,
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->toArray();
    }

    /**
     * Get period start and end dates
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function getPeriodDates(string $period): array
    {
        return match ($period) {
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [ // month
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }

    /**
     * Get human-readable period label
     */
    protected function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'week' => 'Cette semaine',
            'quarter' => 'Ce trimestre',
            'year' => 'Cette année',
            default => 'Ce mois',
        };
    }

    /**
     * Format duration in minutes to human-readable string
     */
    protected function formatDuration(float $minutes): string
    {
        if ($minutes < 60) {
            return round($minutes).' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = (int) round($minutes % 60);

        if ($remainingMinutes === 0) {
            return $hours.'h';
        }

        return $hours.'h '.$remainingMinutes.'min';
    }

    /**
     * Calculate total weekly slots for a collection of availabilities
     */
    protected function calculateWeeklySlots(Collection $availabilities): int
    {
        $totalSlots = 0;

        foreach ($availabilities as $availability) {
            if ($availability->type === 'weekly') {
                // Calculate slots per day
                $startTime = Carbon::parse($availability->start_time);
                $endTime = Carbon::parse($availability->end_time);
                $slotDuration = $availability->slot_duration ?? 60;

                // Use absolute difference to avoid negative values
                $minutesAvailable = abs($endTime->diffInMinutes($startTime));
                $slotsPerDay = floor($minutesAvailable / $slotDuration);

                $totalSlots += $slotsPerDay;
            }
        }

        return $totalSlots;
    }
}
