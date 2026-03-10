<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sprint;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SprintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get burn-down and burn-up chart data for a sprint.
     *
     * The burn-down chart shows the remaining work (story points) over time.
     * The burn-up chart shows the completed work (story points) over time.
     */
    public function burndownChart(Sprint $sprint): JsonResponse
    {
        $sprint->load(['tasks.status', 'tasks.activities']);

        $startDate = Carbon::parse($sprint->start_date)->startOfDay();
        $endDate = Carbon::parse($sprint->end_date)->endOfDay();
        $today = Carbon::now()->endOfDay();

        // Don't go beyond today for actual data
        $actualEndDate = $today->lt($endDate) ? $today : $endDate;

        // Calculate total story points at sprint start
        $totalStoryPoints = $this->calculateTotalStoryPointsAtDate($sprint, $startDate);

        // Generate date range for the sprint
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = collect($period)->map(fn ($date) => $date->format('Y-m-d'))->toArray();

        $chartData = [];

        foreach ($dates as $index => $date) {
            $dateCarbon = Carbon::parse($date)->endOfDay();
            $isInFuture = $dateCarbon->gt($today);
            $dayNumber = $index + 1;

            // Calculate ideal burn-down (linear from total to 0)
            $totalDays = count($dates);
            $idealRemaining = $totalStoryPoints - (($totalStoryPoints / ($totalDays - 1)) * $index);
            $idealRemaining = max(0, round($idealRemaining, 1));

            if ($isInFuture) {
                // For future dates, only show ideal line
                $chartData[] = [
                    'date' => $date,
                    'dayNumber' => $dayNumber,
                    'formattedDate' => Carbon::parse($date)->format('d/m'),
                    'ideal' => $idealRemaining,
                    'actual' => null,
                    'completed' => null,
                    'totalScope' => $totalStoryPoints,
                ];
            } else {
                // Calculate actual remaining and completed points for this date
                $completedAtDate = $this->calculateCompletedStoryPointsAtDate($sprint, $dateCarbon);
                $actualRemaining = $totalStoryPoints - $completedAtDate;

                $chartData[] = [
                    'date' => $date,
                    'dayNumber' => $dayNumber,
                    'formattedDate' => Carbon::parse($date)->format('d/m'),
                    'ideal' => $idealRemaining,
                    'actual' => max(0, $actualRemaining),
                    'completed' => $completedAtDate,
                    'totalScope' => $totalStoryPoints,
                ];
            }
        }

        // Calculate statistics
        $completedPoints = $this->calculateCompletedStoryPointsAtDate($sprint, $actualEndDate);
        $remainingPoints = $totalStoryPoints - $completedPoints;
        $progressPercentage = $totalStoryPoints > 0
            ? round(($completedPoints / $totalStoryPoints) * 100, 1)
            : 0;

        // Calculate velocity (points completed per day)
        $daysElapsed = (int) max(1, $startDate->diffInDays($actualEndDate) + 1);
        $velocity = round($completedPoints / $daysElapsed, 2);

        // Estimate completion date based on current velocity
        $estimatedCompletionDate = null;
        if ($velocity > 0 && $remainingPoints > 0) {
            $daysToComplete = ceil($remainingPoints / $velocity);
            $estimatedCompletionDate = $today->copy()->addDays($daysToComplete)->format('Y-m-d');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'chartData' => $chartData,
                'summary' => [
                    'totalStoryPoints' => $totalStoryPoints,
                    'completedPoints' => $completedPoints,
                    'remainingPoints' => $remainingPoints,
                    'progressPercentage' => $progressPercentage,
                    'velocity' => $velocity,
                    'daysElapsed' => $daysElapsed,
                    'totalDays' => count($dates),
                    'estimatedCompletionDate' => $estimatedCompletionDate,
                    'isOnTrack' => $this->isSprintOnTrack($chartData, $totalStoryPoints),
                ],
                'sprint' => [
                    'id' => $sprint->id,
                    'uuid' => $sprint->uuid,
                    'name' => $sprint->name,
                    'startDate' => $sprint->start_date->format('Y-m-d'),
                    'endDate' => $sprint->end_date->format('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Calculate total story points at a given date.
     * This accounts for tasks added or removed during the sprint.
     */
    private function calculateTotalStoryPointsAtDate(Sprint $sprint, Carbon $date): int
    {
        // Get all tasks currently in the sprint
        $currentTasks = $sprint->tasks;
        $totalPoints = 0;

        foreach ($currentTasks as $task) {
            // Check if task was part of sprint at this date
            // by looking at activity logs
            $taskCreatedAt = Carbon::parse($task->created_at);

            // If task was created before or on the date, include its points
            if ($taskCreatedAt->lte($date)) {
                $totalPoints += $task->story_points ?? 0;
            }
        }

        // If no story points are set, fallback to counting tasks
        if ($totalPoints === 0) {
            return $sprint->tasks->count();
        }

        return $totalPoints;
    }

    /**
     * Calculate completed story points at a given date.
     */
    private function calculateCompletedStoryPointsAtDate(Sprint $sprint, Carbon $date): int
    {
        $completedPoints = 0;

        foreach ($sprint->tasks as $task) {
            // Check if task was completed by this date
            if ($this->wasTaskCompletedByDate($task, $date)) {
                $completedPoints += $task->story_points ?? 1; // Default to 1 if no story points
            }
        }

        return $completedPoints;
    }

    /**
     * Check if a task was completed by a given date.
     */
    private function wasTaskCompletedByDate(Task $task, Carbon $date): bool
    {
        // First check current status
        $isCurrentlyCompleted = $task->status && strtolower($task->status->name) === 'completed';

        if (! $isCurrentlyCompleted) {
            return false;
        }

        // Check activity log to see when it was completed
        $completionActivity = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('description', 'updated')
            ->whereJsonContains('properties->attributes->status_id', $task->status_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($completionActivity) {
            return Carbon::parse($completionActivity->created_at)->lte($date);
        }

        // If no activity log, check if task was created before date and is completed
        return Carbon::parse($task->created_at)->lte($date);
    }

    /**
     * Determine if sprint is on track based on actual vs ideal burn-down.
     */
    private function isSprintOnTrack(array $chartData, int $totalPoints): bool
    {
        // Find the most recent actual data point
        $latestActual = collect($chartData)
            ->filter(fn ($point): bool => $point['actual'] !== null)
            ->last();

        if (! $latestActual) {
            return true; // No data yet, assume on track
        }

        // Compare actual remaining to ideal remaining
        // Allow 10% variance
        $variance = $totalPoints > 0 ? ($totalPoints * 0.1) : 1;

        return $latestActual['actual'] <= ($latestActual['ideal'] + $variance);
    }
}
