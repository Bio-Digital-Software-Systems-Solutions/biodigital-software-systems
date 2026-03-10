<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DepartmentMeeting;
use App\Notifications\DepartmentMeetingCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class DepartmentMeetingController extends Controller
{
    /**
     * Display a listing of meetings for a department.
     */
    public function index(Department $department): JsonResponse
    {
        $meetings = $department->meetings()
            ->with([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                          ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map($this->formatMeeting(...));

        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }

    /**
     * Store a newly created meeting.
     */
    public function store(Request $request, Department $department): JsonResponse
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

            // Create the appointment first
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
                'appointmentable_type' => Department::class,
                'appointmentable_id' => $department->id,
            ]);

            // Add participants if specified
            $participantIds = $validated['participant_ids'] ?? [];
            $organizerId = auth()->id();

            if (!empty($participantIds)) {
                $pivotData = [];
                foreach ($participantIds as $userId) {
                    // If this is the organizer, mark as accepted instead of pending
                    if ($userId == $organizerId) {
                        $pivotData[$userId] = [
                            'status' => 'accepted',
                            'responded_at' => now(),
                        ];
                    } else {
                        $pivotData[$userId] = [
                            'status' => 'pending',
                            'confirmation_token' => \Illuminate\Support\Str::random(64),
                        ];
                    }
                }
                $appointment->participants()->attach($pivotData);
            }

            // Add organizer as accepted participant if not already added
            if (!in_array($organizerId, $participantIds)) {
                $appointment->participants()->attach($organizerId, [
                    'status' => 'accepted',
                    'responded_at' => now(),
                ]);
            }

            // Create the department meeting pivot
            $notifyAllMembers = $validated['notify_all_members'] ?? true;
            $meeting = DepartmentMeeting::create([
                'department_id' => $department->id,
                'appointment_id' => $appointment->id,
                'created_by' => auth()->id(),
                'notify_all_members' => $notifyAllMembers,
                'is_mandatory' => $validated['is_mandatory'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            // Send notifications
            $this->sendMeetingNotifications($meeting, $participantIds);

            // Reload with relationships
            $meeting->load([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                          ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email',
                'department:id,uuid,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réunion créée avec succès.',
                'data' => $this->formatMeeting($meeting),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('DepartmentMeeting creation error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified meeting.
     */
    public function show(Department $department, DepartmentMeeting $meeting): JsonResponse
    {
        // Ensure meeting belongs to department
        if ($meeting->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réunion n\'appartient pas à ce département.',
            ], 404);
        }

        $meeting->load([
            'appointment' => function ($query): void {
                $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                      ->withCount('participants');
            },
            'creator:id,uuid,first_name,last_name,email',
            'department:id,uuid,name'
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatMeeting($meeting),
        ]);
    }

    /**
     * Update the specified meeting.
     */
    public function update(Request $request, Department $department, DepartmentMeeting $meeting): JsonResponse
    {
        // Ensure meeting belongs to department
        if ($meeting->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réunion n\'appartient pas à ce département.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'sometimes|required|date',
            'end_datetime' => 'sometimes|required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'type' => ['sometimes', 'required', Rule::in(['individual', 'group', 'consultation', 'meeting'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'notify_all_members' => 'boolean',
            'is_mandatory' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update appointment fields
            $appointmentFields = ['title', 'description', 'start_datetime', 'end_datetime', 'location', 'type', 'visibility', 'status'];
            $appointmentData = array_intersect_key($validated, array_flip($appointmentFields));
            if ($appointmentData !== []) {
                $meeting->appointment->update($appointmentData);
            }

            // Update meeting fields
            $meetingFields = ['notify_all_members', 'is_mandatory', 'notes'];
            $meetingData = array_intersect_key($validated, array_flip($meetingFields));
            if ($meetingData !== []) {
                $meeting->update($meetingData);
            }

            DB::commit();

            // Reload with relationships
            $meeting->load([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                          ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email',
                'department:id,uuid,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réunion mise à jour avec succès.',
                'data' => $this->formatMeeting($meeting),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la réunion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified meeting.
     */
    public function destroy(Department $department, DepartmentMeeting $meeting): JsonResponse
    {
        // Ensure meeting belongs to department
        if ($meeting->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réunion n\'appartient pas à ce département.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete the appointment (will cascade delete the meeting due to foreign key)
            $meeting->appointment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée avec succès.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réunion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get meetings for a specific month.
     */
    public function byMonth(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $startDate = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $meetings = $department->meetings()
            ->whereHas('appointment', function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('start_datetime', [$startDate, $endDate]);
            })
            ->with([
                'appointment' => function ($query): void {
                    $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                          ->withCount('participants');
                },
                'creator:id,uuid,first_name,last_name,email'
            ])
            ->get()
            ->map($this->formatMeeting(...));

        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }

    /**
     * Send meeting notifications to appropriate members.
     */
    protected function sendMeetingNotifications(DepartmentMeeting $meeting, array $specifiedParticipantIds = []): void
    {
        $meeting->load(['department.users', 'appointment.participants', 'creator']);

        $membersToNotify = collect();

        // If notify_all_members is true AND no participants specified, notify all department members
        if ($meeting->notify_all_members && $specifiedParticipantIds === []) {
            $membersToNotify = $meeting->department->users;
        } elseif ($specifiedParticipantIds !== []) {
            // Only notify specified participants (they already received invite via AppointmentInvitation)
            // So we don't send DepartmentMeetingCreated to them
            // Instead, notify remaining department members if notify_all_members is true
            if ($meeting->notify_all_members) {
                $membersToNotify = $meeting->department->users
                    ->whereNotIn('id', $specifiedParticipantIds);
            }
        }

        // Exclude the creator from notifications
        $membersToNotify = $membersToNotify->reject(fn($member): bool => $member->id === $meeting->created_by);

        if ($membersToNotify->isNotEmpty()) {
            Notification::send($membersToNotify, new DepartmentMeetingCreated($meeting));
            $meeting->markAsNotified();
        }
    }

    /**
     * Format meeting for API response.
     */
    protected function formatMeeting(DepartmentMeeting $meeting): array
    {
        $appointment = $meeting->appointment;

        return [
            'uuid' => $meeting->uuid,
            'department_id' => $meeting->department_id,
            'appointment_id' => $meeting->appointment_id,
            'notify_all_members' => $meeting->notify_all_members,
            'is_mandatory' => $meeting->is_mandatory,
            'notes' => $meeting->notes,
            'notified_at' => $meeting->notified_at?->toISOString(),
            'created_at' => $meeting->created_at->toISOString(),
            'updated_at' => $meeting->updated_at->toISOString(),
            'creator' => $meeting->creator ? [
                'id' => $meeting->creator->id,
                'uuid' => $meeting->creator->uuid,
                'name' => $meeting->creator->first_name . ' ' . $meeting->creator->last_name,
                'email' => $meeting->creator->email,
            ] : null,
            'appointment' => $appointment ? [
                'uuid' => $appointment->uuid,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'type' => $appointment->type,
                'status' => $appointment->status,
                'visibility' => $appointment->visibility,
                'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                'location' => $appointment->location,
                'formatted_date' => $appointment->formatted_date,
                'formatted_time_range' => $appointment->formatted_time_range,
                'participants_count' => $appointment->participants_count,
                'organizer' => $appointment->organizer ? [
                    'id' => $appointment->organizer->id,
                    'uuid' => $appointment->organizer->uuid,
                    'name' => $appointment->organizer->first_name . ' ' . $appointment->organizer->last_name,
                    'email' => $appointment->organizer->email,
                ] : null,
                'participants' => $appointment->participants->map(fn($participant): array => [
                    'id' => $participant->id,
                    'uuid' => $participant->uuid,
                    'name' => $participant->first_name . ' ' . $participant->last_name,
                    'email' => $participant->email,
                    'status' => $participant->pivot->status ?? 'pending',
                ]),
            ] : null,
        ];
    }
}
