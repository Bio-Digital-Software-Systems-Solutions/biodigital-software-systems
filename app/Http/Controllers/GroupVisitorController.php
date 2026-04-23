<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupVisitorRequest;
use App\Models\Group;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use App\Models\VisitorVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupVisitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view visitors');
    }

    public function index(Group $group): JsonResponse
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('visitor_visits')) {
            return response()->json(['visitors' => []]);
        }

        $visits = VisitorVisit::with(['visitor', 'invitedBy', 'suggestion'])
            ->withCount(['attendances', 'attendances as present_count' => function ($q): void {
                $q->where('status', 'present');
            }])
            ->where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (VisitorVisit $visit) => [
                'uuid' => $visit->uuid,
                'visitor' => [
                    'id' => $visit->visitor->id,
                    'uuid' => $visit->visitor->uuid,
                    'first_name' => $visit->visitor->first_name,
                    'last_name' => $visit->visitor->last_name,
                    'name' => $visit->visitor->name,
                    'email' => $visit->visitor->email,
                    'phone' => $visit->visitor->phone,
                    'photo' => $visit->visitor->photo,
                    'source' => $visit->visitor->source,
                    'status' => $visit->visitor->status,
                ],
                'first_visited_at' => $visit->first_visited_at?->format('Y-m-d'),
                'integration_score' => (float) $visit->integration_score,
                'integration_status' => $visit->integration_status,
                'attendance_count' => $visit->attendances_count,
                'present_count' => $visit->present_count,
                'invited_by' => $visit->invitedBy ? [
                    'id' => $visit->invitedBy->id,
                    'name' => $visit->invitedBy->full_name,
                ] : null,
                'notes' => $visit->notes,
                'has_pending_suggestion' => $visit->suggestion && $visit->suggestion->status === 'pending',
            ]);

        return response()->json(['visitors' => $visits]);
    }

    public function store(StoreGroupVisitorRequest $request, Group $group): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['visitor_id'])) {
            $visitor = Visitor::findOrFail($validated['visitor_id']);
        } else {
            $visitor = Visitor::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'source' => $validated['source'] ?? null,
                'first_visit_date' => $validated['first_visited_at'],
                'created_by' => Auth::id(),
            ]);
        }

        $existingVisit = VisitorVisit::where('visitor_id', $visitor->id)
            ->where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->exists();

        if ($existingVisit) {
            return response()->json(['message' => 'Ce visiteur est déjà associé à ce groupe.'], 422);
        }

        VisitorVisit::create([
            'visitor_id' => $visitor->id,
            'visitable_type' => Group::class,
            'visitable_id' => $group->id,
            'first_visited_at' => $validated['first_visited_at'],
            'invited_by' => $validated['invited_by'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Visiteur ajouté au groupe avec succès.']);
    }

    public function recordAttendance(Request $request, Group $group, Visitor $visitor): JsonResponse
    {
        $validated = $request->validate([
            'attendable_type' => ['required', 'string'],
            'attendable_id' => ['required', 'integer'],
            'attended_at' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,excused,late'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit = VisitorVisit::where('visitor_id', $visitor->id)
            ->where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->firstOrFail();

        VisitorAttendance::create([
            'visitor_id' => $visitor->id,
            'visitor_visit_id' => $visit->id,
            'attendable_type' => $validated['attendable_type'],
            'attendable_id' => $validated['attendable_id'],
            'attended_at' => $validated['attended_at'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Présence enregistrée avec succès.']);
    }

    public function bulkRecordAttendance(Request $request, Group $group): JsonResponse
    {
        $validated = $request->validate([
            'attendable_type' => ['required', 'string'],
            'attendable_id' => ['required', 'integer'],
            'attended_at' => ['required', 'date'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.visitor_id' => ['required', 'exists:visitors,id'],
            'attendances.*.status' => ['required', 'in:present,absent,excused,late'],
            'attendances.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($validated['attendances'] as $attendance) {
            $visit = VisitorVisit::where('visitor_id', $attendance['visitor_id'])
                ->where('visitable_type', Group::class)
                ->where('visitable_id', $group->id)
                ->first();

            if (! $visit) {
                continue;
            }

            VisitorAttendance::create([
                'visitor_id' => $attendance['visitor_id'],
                'visitor_visit_id' => $visit->id,
                'attendable_type' => $validated['attendable_type'],
                'attendable_id' => $validated['attendable_id'],
                'attended_at' => $validated['attended_at'],
                'status' => $attendance['status'],
                'notes' => $attendance['notes'] ?? null,
                'recorded_by' => Auth::id(),
            ]);
        }

        return response()->json(['message' => 'Présences enregistrées avec succès.']);
    }

    public function removeVisitor(Group $group, Visitor $visitor): JsonResponse
    {
        $visit = VisitorVisit::where('visitor_id', $visitor->id)
            ->where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->firstOrFail();

        $visit->attendances()->delete();
        $visit->integrationProgress()->delete();
        $visit->suggestion()?->delete();
        $visit->delete();

        return response()->json(['message' => 'Visiteur retiré du groupe avec succès.']);
    }

    public function integrationDashboard(Group $group): JsonResponse
    {
        $visits = VisitorVisit::with(['visitor', 'attendances', 'integrationProgress.step'])
            ->where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->get();

        $stats = [
            'total_visitors' => $visits->count(),
            'visiting' => $visits->where('integration_status', 'visiting')->count(),
            'progressing' => $visits->where('integration_status', 'progressing')->count(),
            'ready' => $visits->where('integration_status', 'ready')->count(),
            'integrated' => $visits->where('integration_status', 'integrated')->count(),
            'average_score' => round($visits->avg('integration_score'), 1),
        ];

        return response()->json([
            'stats' => $stats,
            'visitors' => $visits->map(fn (VisitorVisit $visit) => [
                'visitor_name' => $visit->visitor->name,
                'score' => (float) $visit->integration_score,
                'status' => $visit->integration_status,
                'attendances' => $visit->attendances->map(fn ($a) => [
                    'date' => $a->attended_at->format('Y-m-d'),
                    'status' => $a->status,
                    'type' => class_basename($a->attendable_type),
                ]),
                'progress' => $visit->integrationProgress->map(fn ($p) => [
                    'step_name' => $p->step->name,
                    'step_type' => $p->step->type,
                    'progress' => (float) $p->progress_value,
                    'status' => $p->status,
                ]),
            ]),
        ]);
    }
}
