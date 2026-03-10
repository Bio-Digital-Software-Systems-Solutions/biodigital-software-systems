<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AppointmentCancellation;
use App\Notifications\AppointmentInvitation;
use App\Notifications\AppointmentUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectAppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all appointments for a project (including task appointments).
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        // Get project appointments
        $projectAppointments = Appointment::where('appointmentable_type', \App\Models\Project::class)
            ->where('appointmentable_id', $project->id)
            ->with(['organizer:id,first_name,last_name', 'participants:id,first_name,last_name'])
            ->get();

        // Get task appointments for this project
        $taskIds = $project->tasks()->pluck('id');
        $taskAppointments = Appointment::where('appointmentable_type', \App\Models\Task::class)
            ->whereIn('appointmentable_id', $taskIds)
            ->with(['organizer:id,first_name,last_name', 'participants:id,first_name,last_name', 'appointmentable'])
            ->get();

        $allAppointments = $projectAppointments->merge($taskAppointments)
            ->sortBy('start_datetime')
            ->values()
            ->map($this->formatAppointment(...));

        return response()->json([
            'success' => true,
            'data' => $allAppointments,
        ]);
    }

    /**
     * Get appointments for a specific month.
     */
    public function month(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $startOfMonth = now()->setYear((int) $validated['year'])->setMonth((int) $validated['month'])->startOfMonth()->toDateTimeString();
        $endOfMonth = now()->setYear((int) $validated['year'])->setMonth((int) $validated['month'])->endOfMonth()->toDateTimeString();

        // Get project appointments
        $projectAppointments = Appointment::where('appointmentable_type', \App\Models\Project::class)
            ->where('appointmentable_id', $project->id)
            ->betweenDates($startOfMonth, $endOfMonth)
            ->with(['organizer:id,first_name,last_name', 'participants:id,first_name,last_name'])
            ->get();

        // Get task appointments for this project
        $taskIds = $project->tasks()->pluck('id');
        $taskAppointments = Appointment::where('appointmentable_type', \App\Models\Task::class)
            ->whereIn('appointmentable_id', $taskIds)
            ->betweenDates($startOfMonth, $endOfMonth)
            ->with(['organizer:id,first_name,last_name', 'participants:id,first_name,last_name', 'appointmentable'])
            ->get();

        $allAppointments = $projectAppointments->merge($taskAppointments)
            ->sortBy('start_datetime')
            ->values()
            ->map($this->formatAppointment(...));

        return response()->json([
            'success' => true,
            'data' => $allAppointments,
        ]);
    }

    /**
     * Create a new appointment for the project or a task.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'location' => 'nullable|string|max:500',
            'type' => ['required', Rule::in(['individual', 'group', 'consultation', 'meeting'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'max_participants' => 'nullable|integer|min:1|max:1000',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
            'appointmentable_type' => ['required', Rule::in([\App\Models\Project::class, \App\Models\Task::class])],
            'appointmentable_id' => 'required|integer',
        ]);

        // Validate appointmentable
        if ($validated['appointmentable_type'] === \App\Models\Task::class) {
            $task = Task::findOrFail($validated['appointmentable_id']);
            // Ensure task belongs to this project
            if ($task->project_id !== $project->id && $task->taskable_id !== $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tâche n\'appartient pas à ce projet.',
                ], 422);
            }
        } elseif ($validated['appointmentable_id'] !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'ID de projet invalide.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $appointment = Appointment::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_datetime' => $validated['start_datetime'],
                'end_datetime' => $validated['end_datetime'],
                'location' => $validated['location'] ?? null,
                'type' => $validated['type'],
                'visibility' => $validated['visibility'],
                'max_participants' => $validated['max_participants'] ?? null,
                'status' => 'pending',
                'user_id' => Auth::id(),
                'appointmentable_type' => $validated['appointmentable_type'],
                'appointmentable_id' => $validated['appointmentable_id'],
            ]);

            // Add participants and send invitations
            $newParticipantIds = [];
            if (!empty($validated['participants'])) {
                $participantsData = [];
                foreach ($validated['participants'] as $userId) {
                    // Don't send invitation to the organizer
                    if ($userId == Auth::id()) {
                        continue;
                    }
                    $confirmationToken = Str::random(64);
                    $participantsData[$userId] = [
                        'status' => 'pending',
                        'confirmation_token' => $confirmationToken,
                        'invited_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $newParticipantIds[$userId] = $confirmationToken;
                }
                if ($participantsData !== []) {
                    $appointment->participants()->attach($participantsData);
                }
            }

            // Always add the organizer as accepted participant
            if (!in_array(Auth::id(), $validated['participants'] ?? [])) {
                $appointment->participants()->attach(Auth::id(), [
                    'status' => 'accepted',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // Send invitation emails to new participants (after commit)
            $appointment->load(['organizer:id,first_name,last_name,email']);
            foreach ($newParticipantIds as $userId => $token) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new AppointmentInvitation($appointment, $token));
                }
            }

            $appointment->load(['participants:id,first_name,last_name']);

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès.',
                'data' => $this->formatAppointment($appointment),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du rendez-vous: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an appointment.
     */
    public function update(Request $request, Project $project, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $project);

        // Verify appointment belongs to project or project's tasks
        if (!$this->appointmentBelongsToProject($appointment, $project)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous n\'appartient pas à ce projet.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_datetime' => 'sometimes|required|date',
            'end_datetime' => 'sometimes|required|date|after_or_equal:start_datetime',
            'location' => 'nullable|string|max:500',
            'type' => ['sometimes', 'required', Rule::in(['individual', 'group', 'consultation', 'meeting'])],
            'visibility' => ['sometimes', 'required', Rule::in(['public', 'private'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'max_participants' => 'nullable|integer|min:1|max:1000',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            // Track changes for notification
            $originalData = $appointment->getOriginal();
            $changes = [];

            // Get existing participants before update
            $existingParticipantIds = $appointment->participants()
                ->where('user_id', '!=', $appointment->user_id) // Exclude organizer
                ->pluck('user_id')
                ->toArray();

            $appointment->update($validated);

            // Track what changed
            foreach (['title', 'start_datetime', 'end_datetime', 'location', 'status'] as $field) {
                if (isset($validated[$field]) && $originalData[$field] != $appointment->$field) {
                    $changes[$field] = [
                        'old' => $originalData[$field],
                        'new' => $appointment->$field,
                    ];
                }
            }

            // Update participants if provided
            $newParticipantIds = [];
            if (isset($validated['participants'])) {
                $requestedParticipants = array_filter($validated['participants'], fn($id): bool => $id != $appointment->user_id);

                // Find new participants (not in existing list)
                $newParticipants = array_diff($requestedParticipants, $existingParticipantIds);

                // Prepare sync data
                $participantsData = [];
                foreach ($requestedParticipants as $userId) {
                    if (in_array($userId, $newParticipants)) {
                        // New participant - send invitation
                        $confirmationToken = Str::random(64);
                        $participantsData[$userId] = [
                            'status' => 'pending',
                            'confirmation_token' => $confirmationToken,
                            'invited_at' => now(),
                            'updated_at' => now(),
                        ];
                        $newParticipantIds[$userId] = $confirmationToken;
                    } else {
                        // Existing participant - keep their status
                        $participantsData[$userId] = [
                            'updated_at' => now(),
                        ];
                    }
                }
                $appointment->participants()->sync($participantsData);

                // Ensure organizer remains
                if (!in_array($appointment->user_id, $validated['participants'])) {
                    $appointment->participants()->attach($appointment->user_id, [
                        'status' => 'accepted',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            // Send notifications after commit
            $appointment->load(['organizer:id,first_name,last_name,email']);

            // Send invitations to new participants
            foreach ($newParticipantIds as $userId => $token) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new AppointmentInvitation($appointment, $token));
                }
            }

            // Check if appointment was cancelled
            $wasCancelled = isset($changes['status']) && $changes['status']['new'] === 'cancelled';

            if ($wasCancelled) {
                // Notify all participants about cancellation
                foreach ($appointment->participants as $participant) {
                    // Don't notify the user who cancelled (current authenticated user)
                    if ($participant->id === Auth::id()) {
                        continue;
                    }
                    $participant->notify(new AppointmentCancellation($appointment));
                }
            } elseif ($changes !== []) {
                // Notify existing participants about changes (if there were changes and participants exist)
                $existingParticipantsToNotify = array_diff($existingParticipantIds, array_keys($newParticipantIds));
                foreach ($existingParticipantsToNotify as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->notify(new AppointmentUpdated($appointment, $changes));
                    }
                }
            }

            $appointment->load(['participants:id,first_name,last_name']);

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous mis à jour avec succès.',
                'data' => $this->formatAppointment($appointment),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rendez-vous: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an appointment.
     */
    public function destroy(Project $project, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $project);

        // Verify appointment belongs to project or project's tasks
        if (!$this->appointmentBelongsToProject($appointment, $project)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous n\'appartient pas à ce projet.',
            ], 403);
        }

        try {
            $appointment->participants()->detach();
            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous supprimé avec succès.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du rendez-vous: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if an appointment belongs to a project or its tasks.
     */
    private function appointmentBelongsToProject(Appointment $appointment, Project $project): bool
    {
        if ($appointment->appointmentable_type === \App\Models\Project::class) {
            return $appointment->appointmentable_id === $project->id;
        }

        if ($appointment->appointmentable_type === \App\Models\Task::class) {
            $task = Task::find($appointment->appointmentable_id);
            if ($task) {
                return $task->project_id === $project->id || $task->taskable_id === $project->id;
            }
        }

        return false;
    }

    /**
     * Format appointment for API response.
     */
    private function formatAppointment(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'uuid' => $appointment->uuid,
            'title' => $appointment->title,
            'description' => $appointment->description,
            'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
            'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
            'location' => $appointment->location,
            'status' => $appointment->status,
            'type' => $appointment->type,
            'visibility' => $appointment->visibility,
            'max_participants' => $appointment->max_participants,
            'organizer' => $appointment->organizer ? [
                'id' => $appointment->organizer->id,
                'first_name' => $appointment->organizer->first_name,
                'last_name' => $appointment->organizer->last_name,
            ] : null,
            'participants_count' => $appointment->participants->count(),
            'participants' => $appointment->participants->map(fn($user): array => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'status' => $user->pivot->status ?? 'pending',
            ]),
            'appointmentable_type' => class_basename($appointment->appointmentable_type),
            'appointmentable' => $appointment->appointmentable ? [
                'id' => $appointment->appointmentable->id,
                'title' => $appointment->appointmentable->title ?? $appointment->appointmentable->name ?? null,
            ] : null,
            'created_at' => $appointment->created_at->format('Y-m-d\TH:i:s'),
            'updated_at' => $appointment->updated_at->format('Y-m-d\TH:i:s'),
        ];
    }
}
