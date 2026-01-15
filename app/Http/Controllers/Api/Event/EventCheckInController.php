<?php

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Event\EventCheckin;
use App\Models\Event\EventRegistration;
use App\Models\Event\EventSession;
use App\Services\Event\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventCheckInController extends Controller
{
    protected CheckInService $checkInService;

    public function __construct(CheckInService $checkInService)
    {
        $this->checkInService = $checkInService;
        $this->middleware('can:view events');
        $this->middleware('can:edit events')->except(['stats', 'search']);
    }

    /**
     * Check in by QR code.
     */
    public function checkInByQR(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
            'session_id' => 'nullable|exists:event_sessions,id',
            'device_id' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $session = null;
        if (!empty($validated['session_id'])) {
            $session = EventSession::find($validated['session_id']);
            if ($session && $session->event_id !== $event->id) {
                return response()->json(['error' => 'Session invalide.'], 422);
            }
        }

        $result = $this->checkInService->checkInByQRCode(
            $validated['qr_code'],
            Auth::user(),
            $session,
            [
                'method' => 'qr_code',
                'device_id' => $validated['device_id'] ?? null,
                'location' => $validated['location'] ?? null,
            ]
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Check in by registration number.
     */
    public function checkInByNumber(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => 'required|string',
            'session_id' => 'nullable|exists:event_sessions,id',
            'device_id' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $session = null;
        if (!empty($validated['session_id'])) {
            $session = EventSession::find($validated['session_id']);
            if ($session && $session->event_id !== $event->id) {
                return response()->json(['error' => 'Session invalide.'], 422);
            }
        }

        $result = $this->checkInService->checkInByNumber(
            $validated['registration_number'],
            Auth::user(),
            $session,
            [
                'method' => 'manual',
                'device_id' => $validated['device_id'] ?? null,
                'location' => $validated['location'] ?? null,
            ]
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Manual check-in by registration ID.
     */
    public function checkInManual(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|exists:event_sessions,id',
            'device_id' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $session = null;
        if (!empty($validated['session_id'])) {
            $session = EventSession::find($validated['session_id']);
        }

        $result = $this->checkInService->checkInByQRCode(
            $registration->qr_code,
            Auth::user(),
            $session,
            [
                'method' => 'manual',
                'device_id' => $validated['device_id'] ?? null,
                'location' => $validated['location'] ?? null,
            ]
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Check out a registration.
     */
    public function checkOut(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $session = null;
        if ($request->filled('session_id')) {
            $session = EventSession::find($request->input('session_id'));
        }

        $result = $this->checkInService->checkOut($registration, $session);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Undo a check-in.
     */
    public function undoCheckIn(Event $event, EventCheckin $checkin): JsonResponse
    {
        if ($checkin->registration->event_id !== $event->id) {
            return response()->json(['error' => 'Check-in not found'], 404);
        }

        $this->checkInService->undoCheckIn($checkin);

        return response()->json([
            'message' => 'Check-in annulé.',
        ]);
    }

    /**
     * Get check-in statistics.
     */
    public function stats(Event $event): JsonResponse
    {
        return response()->json([
            'data' => $this->checkInService->getStats($event),
        ]);
    }

    /**
     * Get recent check-ins.
     */
    public function recent(Request $request, Event $event): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $checkins = $this->checkInService->getRecentCheckIns($event, min($limit, 50));

        return response()->json([
            'data' => $checkins,
        ]);
    }

    /**
     * Get live feed (for real-time updates).
     */
    public function liveFeed(Request $request, Event $event): JsonResponse
    {
        $since = $request->input('since', 0);
        $data = $this->checkInService->getLiveFeed($event, $since);

        return response()->json($data);
    }

    /**
     * Search attendees for check-in.
     */
    public function search(Request $request, Event $event): JsonResponse
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json(['data' => []]);
        }

        $attendees = $this->checkInService->searchAttendees($event, $search);

        return response()->json([
            'data' => $attendees,
        ]);
    }

    /**
     * Get check-in history for a registration.
     */
    public function history(Event $event, EventRegistration $registration): JsonResponse
    {
        if ($registration->event_id !== $event->id) {
            return response()->json(['error' => 'Registration not found'], 404);
        }

        $history = $this->checkInService->getCheckInHistory($registration);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Get session attendance.
     */
    public function sessionAttendance(Event $event, EventSession $session): JsonResponse
    {
        if ($session->event_id !== $event->id) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        return response()->json([
            'data' => $this->checkInService->getSessionAttendance($session),
        ]);
    }

    /**
     * Mark no-shows after event.
     */
    public function markNoShows(Event $event): JsonResponse
    {
        if (!$event->hasEnded()) {
            return response()->json([
                'error' => 'L\'événement n\'est pas encore terminé.',
            ], 422);
        }

        $count = $this->checkInService->markNoShows($event);

        return response()->json([
            'message' => "{$count} inscription(s) marquée(s) comme absent(s).",
            'count' => $count,
        ]);
    }

    /**
     * Get all check-ins for an event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $query = EventCheckin::whereHas('registration', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })
            ->with(['registration.ticket', 'checkedInBy', 'session'])
            ->where('check_type', 'entry');

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->input('session_id'));
        } else {
            $query->whereNull('session_id');
        }

        $checkins = $query->latest('checked_in_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $checkins->items(),
            'meta' => [
                'current_page' => $checkins->currentPage(),
                'last_page' => $checkins->lastPage(),
                'per_page' => $checkins->perPage(),
                'total' => $checkins->total(),
            ],
        ]);
    }
}
