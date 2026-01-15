<?php

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Event\EventBadge;
use App\Models\Event\EventRegistration;
use App\Services\Event\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventBadgeController extends Controller
{
    protected BadgeService $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
        $this->middleware('can:view events');
        $this->middleware('can:edit events')->except(['templates', 'search']);
    }

    /**
     * Get all badges for an event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $query = EventBadge::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })
            ->with('registration');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $badges = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $badges->items(),
            'meta' => [
                'current_page' => $badges->currentPage(),
                'last_page' => $badges->lastPage(),
                'per_page' => $badges->perPage(),
                'total' => $badges->total(),
            ],
            'stats' => $this->badgeService->getStats($event),
        ]);
    }

    /**
     * Get a specific badge.
     */
    public function show(Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        return response()->json([
            'data' => $badge->load('registration'),
        ]);
    }

    /**
     * Generate a badge for a registration.
     */
    public function generate(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $validated = $request->validate([
            'template' => 'nullable|string|in:default,vip,speaker,staff,sponsor',
            'custom_fields' => 'nullable|array',
        ]);

        $badge = $this->badgeService->generateBadge($registration, $validated);

        return response()->json([
            'data' => $badge,
            'message' => 'Badge généré avec succès.',
        ], 201);
    }

    /**
     * Generate badges in bulk.
     */
    public function generateBulk(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'nullable|string|in:default,vip,speaker,staff,sponsor',
        ]);

        $result = $this->badgeService->generateBulkBadges($event, $validated);

        return response()->json([
            'message' => "{$result['generated']} badge(s) généré(s).",
            'generated' => $result['generated'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Mark badge as printed.
     */
    public function markPrinted(Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        $badge = $this->badgeService->markAsPrinted($badge, Auth::user());

        return response()->json([
            'data' => $badge,
            'message' => 'Badge marqué comme imprimé.',
        ]);
    }

    /**
     * Mark badges as printed in bulk.
     */
    public function markBulkPrinted(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'badge_ids' => 'required|array',
            'badge_ids.*' => 'exists:event_badges,id',
        ]);

        $count = $this->badgeService->markBulkAsPrinted($validated['badge_ids'], Auth::user());

        return response()->json([
            'message' => "{$count} badge(s) marqué(s) comme imprimé(s).",
            'count' => $count,
        ]);
    }

    /**
     * Mark badge as collected.
     */
    public function markCollected(Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        $badge = $this->badgeService->markAsCollected($badge);

        return response()->json([
            'data' => $badge,
            'message' => 'Badge marqué comme récupéré.',
        ]);
    }

    /**
     * Report lost and generate replacement.
     */
    public function reportLost(Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        $newBadge = $this->badgeService->reportLostAndReplace($badge, Auth::user());

        return response()->json([
            'data' => $newBadge,
            'message' => 'Badge de remplacement généré.',
        ]);
    }

    /**
     * Update badge information.
     */
    public function update(Request $request, Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'template' => 'nullable|string|in:default,vip,speaker,staff,sponsor',
            'custom_fields' => 'nullable|array',
        ]);

        $badge = $this->badgeService->updateBadge($badge, $validated);

        return response()->json([
            'data' => $badge,
            'message' => 'Badge mis à jour.',
        ]);
    }

    /**
     * Get badges pending printing.
     */
    public function pendingPrint(Event $event): JsonResponse
    {
        $badges = $this->badgeService->getPendingPrint($event);

        return response()->json([
            'data' => $badges,
            'count' => $badges->count(),
        ]);
    }

    /**
     * Get badges pending collection.
     */
    public function pendingCollection(Event $event): JsonResponse
    {
        $badges = $this->badgeService->getPendingCollection($event);

        return response()->json([
            'data' => $badges,
            'count' => $badges->count(),
        ]);
    }

    /**
     * Search badges.
     */
    public function search(Request $request, Event $event): JsonResponse
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json(['data' => []]);
        }

        $badges = $this->badgeService->search($event, $search);

        return response()->json([
            'data' => $badges,
        ]);
    }

    /**
     * Get available templates.
     */
    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => $this->badgeService->getTemplates(),
        ]);
    }

    /**
     * Get badge statistics.
     */
    public function stats(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->badgeService->getStats($event),
        ]);
    }

    /**
     * Get print data for a badge.
     */
    public function printData(Event $event, EventBadge $badge): JsonResponse
    {
        if ($badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge not found'], 404);
        }

        return response()->json([
            'data' => $this->badgeService->getBadgePrintData($badge),
        ]);
    }

    /**
     * Get bulk print data.
     */
    public function bulkPrintData(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'badge_ids' => 'required|array',
            'badge_ids.*' => 'exists:event_badges,id',
        ]);

        return response()->json([
            'data' => $this->badgeService->getBulkPrintData($validated['badge_ids']),
        ]);
    }

    /**
     * Find badge by QR data.
     */
    public function findByQR(Request $request, Event $event): JsonResponse
    {
        $qrData = $request->input('qr_data');

        if (!$qrData) {
            return response()->json(['error' => 'QR data requis.'], 422);
        }

        $badge = $this->badgeService->findByQRData($qrData);

        if (!$badge || $badge->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Badge non trouvé.'], 404);
        }

        return response()->json([
            'data' => $badge->load('registration'),
        ]);
    }
}
