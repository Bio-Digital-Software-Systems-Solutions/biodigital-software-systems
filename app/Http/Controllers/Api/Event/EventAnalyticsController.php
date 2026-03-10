<?php

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Event\EventAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventAnalyticsController extends Controller
{
    public function __construct(protected EventAnalyticsService $analyticsService)
    {
        $this->middleware('can:view events');
    }

    /**
     * Get full dashboard data.
     */
    public function dashboard(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getDashboardData($event),
        ]);
    }

    /**
     * Get event overview.
     */
    public function overview(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getOverview($event),
        ]);
    }

    /**
     * Get registration statistics.
     */
    public function registrations(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getRegistrationStats($event),
        ]);
    }

    /**
     * Get revenue statistics.
     */
    public function revenue(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getRevenueStats($event),
        ]);
    }

    /**
     * Get feedback statistics.
     */
    public function feedback(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getFeedbackStats($event),
        ]);
    }

    /**
     * Get registration trends.
     */
    public function trends(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getRegistrationTrends($event),
        ]);
    }

    /**
     * Get session analytics.
     */
    public function sessions(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getSessionAnalytics($event),
        ]);
    }

    /**
     * Get sponsor analytics.
     */
    public function sponsors(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getSponsorAnalytics($event),
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request, Event $event): JsonResponse
    {
        $format = $request->input('format', 'json');

        return response()->json([
            'data' => $this->analyticsService->exportAnalytics($event, $format),
        ]);
    }

    /**
     * Compare two events.
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_a_id' => 'required|exists:events,id',
            'event_b_id' => 'required|exists:events,id',
        ]);

        $eventA = Event::findOrFail($validated['event_a_id']);
        $eventB = Event::findOrFail($validated['event_b_id']);

        return response()->json([
            'data' => $this->analyticsService->compareEvents($eventA, $eventB),
        ]);
    }

    /**
     * Get real-time data (lightweight, for frequent polling).
     */
    public function realtime(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->analyticsService->getRealTimeData($event),
        ]);
    }

    /**
     * Clear analytics cache.
     */
    public function clearCache(Event $event): JsonResponse
    {
        $this->analyticsService->clearCache($event);

        return response()->json([
            'message' => 'Cache analytique vidé.',
        ]);
    }
}
