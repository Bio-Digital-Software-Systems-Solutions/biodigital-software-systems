<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupActivityController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $activities = $group->groupActivities()
            ->with(['assignee:id,uuid,first_name,last_name,email', 'creator:id,uuid,first_name,last_name,email'])
            ->orderBy('activity_date', 'desc')
            ->get()
            ->map(fn ($groupActivity) => $this->formatActivity($groupActivity));

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    public function store(Request $request, Group $group): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'type' => ['nullable', Rule::in(['meeting', 'task', 'event', 'other'])],
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $groupActivity = GroupActivity::create([
            ...$validated,
            'group_id' => $group->id,
            'created_by' => auth()->id(),
            'status' => $validated['status'] ?? 'planned',
            'type' => $validated['type'] ?? 'task',
        ]);

        $groupActivity->load(['assignee:id,uuid,first_name,last_name,email', 'creator:id,uuid,first_name,last_name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Activité créée avec succès.',
            'data' => $this->formatActivity($groupActivity),
        ], 201);
    }

    public function update(Request $request, Group $group, GroupActivity $groupActivity): JsonResponse
    {
        if ($groupActivity->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette activité n\'appartient pas à ce groupe.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'sometimes|required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'type' => ['nullable', Rule::in(['meeting', 'task', 'event', 'other'])],
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $groupActivity->update($validated);
        $groupActivity->load(['assignee:id,uuid,first_name,last_name,email', 'creator:id,uuid,first_name,last_name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour avec succès.',
            'data' => $this->formatActivity($groupActivity),
        ]);
    }

    public function destroy(Group $group, GroupActivity $groupActivity): JsonResponse
    {
        if ($groupActivity->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette activité n\'appartient pas à ce groupe.',
            ], 403);
        }

        $groupActivity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès.',
        ]);
    }

    protected function formatActivity(GroupActivity $groupActivity): array
    {
        return [
            'uuid' => $groupActivity->uuid,
            'title' => $groupActivity->title,
            'description' => $groupActivity->description,
            'activity_date' => $groupActivity->activity_date->format('Y-m-d'),
            'start_time' => $groupActivity->start_time?->format('H:i'),
            'end_time' => $groupActivity->end_time?->format('H:i'),
            'status' => $groupActivity->status,
            'type' => $groupActivity->type,
            'location' => $groupActivity->location,
            'notes' => $groupActivity->notes,
            'assignee' => $groupActivity->assignee ? [
                'id' => $groupActivity->assignee->id,
                'uuid' => $groupActivity->assignee->uuid,
                'name' => $groupActivity->assignee->name,
            ] : null,
            'creator' => $groupActivity->creator ? [
                'id' => $groupActivity->creator->id,
                'uuid' => $groupActivity->creator->uuid,
                'name' => $groupActivity->creator->name,
            ] : null,
            'created_at' => $groupActivity->created_at->toISOString(),
        ];
    }
}
