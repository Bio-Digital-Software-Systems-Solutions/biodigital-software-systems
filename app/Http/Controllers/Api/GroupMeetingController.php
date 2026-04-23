<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Group;
use App\Models\GroupMeeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GroupMeetingController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $meetings = $group->meetings()
            ->with([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                        ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($meeting) => $this->formatMeeting($meeting));

        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }

    public function store(Request $request, Group $group): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'type' => ['required', Rule::in(['individual', 'group', 'consultation', 'meeting'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'notify_all_members' => 'boolean',
            'is_mandatory' => 'boolean',
            'notes' => 'nullable|string',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $appointment = Appointment::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_datetime' => $validated['start_datetime'],
                'end_datetime' => $validated['end_datetime'],
                'location' => $validated['location'] ?? null,
                'type' => $validated['type'],
                'visibility' => $validated['visibility'] ?? 'private',
                'status' => 'confirmed',
                'user_id' => auth()->id(),
                'appointmentable_type' => Group::class,
                'appointmentable_id' => $group->id,
            ]);

            $participantIds = $validated['participant_ids'] ?? [];
            $organizerId = auth()->id();

            if (! empty($participantIds)) {
                $pivotData = [];
                foreach ($participantIds as $userId) {
                    if ($userId == $organizerId) {
                        $pivotData[$userId] = [
                            'status' => 'accepted',
                            'responded_at' => now(),
                        ];
                    } else {
                        $pivotData[$userId] = [
                            'status' => 'pending',
                            'confirmation_token' => Str::random(64),
                        ];
                    }
                }
                $appointment->participants()->attach($pivotData);
            }

            if (! in_array($organizerId, $participantIds)) {
                $appointment->participants()->attach($organizerId, [
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);
            }

            $meeting = GroupMeeting::create([
                'group_id' => $group->id,
                'appointment_id' => $appointment->id,
                'created_by' => auth()->id(),
                'notify_all_members' => $validated['notify_all_members'] ?? true,
                'is_mandatory' => $validated['is_mandatory'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            $meeting->load([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                        ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réunion créée avec succès.',
                'data' => $this->formatMeeting($meeting),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion.',
            ], 500);
        }
    }

    public function destroy(Group $group, GroupMeeting $meeting): JsonResponse
    {
        if ($meeting->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réunion n\'appartient pas à ce groupe.',
            ], 403);
        }

        $meeting->appointment?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réunion supprimée avec succès.',
        ]);
    }

    protected function formatMeeting(GroupMeeting $meeting): array
    {
        $appointment = $meeting->appointment;

        return [
            'uuid' => $meeting->uuid,
            'is_mandatory' => $meeting->is_mandatory,
            'notify_all_members' => $meeting->notify_all_members,
            'notes' => $meeting->notes,
            'notified_at' => $meeting->notified_at?->toISOString(),
            'created_at' => $meeting->created_at->toISOString(),
            'creator' => $meeting->creator ? [
                'id' => $meeting->creator->id,
                'uuid' => $meeting->creator->uuid,
                'name' => $meeting->creator->name,
            ] : null,
            'appointment' => $appointment ? [
                'uuid' => $appointment->uuid,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'type' => $appointment->type,
                'status' => $appointment->status,
                'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                'location' => $appointment->location,
                'formatted_time_range' => $appointment->formatted_time_range,
                'participants_count' => $appointment->participants_count ?? $appointment->participants()->count(),
                'organizer' => $appointment->organizer ? [
                    'id' => $appointment->organizer->id,
                    'uuid' => $appointment->organizer->uuid,
                    'name' => $appointment->organizer->name,
                ] : null,
            ] : null,
        ];
    }
}
