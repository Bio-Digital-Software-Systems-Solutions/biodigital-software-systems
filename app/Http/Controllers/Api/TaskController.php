<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use App\Notifications\TaskCommentAdded;
use App\Notifications\TaskParticipantAdded;
use App\Notifications\UserMentionedInComment;
use App\Services\Comment\MentionService;
use App\Services\TaskActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Task::with(['status', 'assignee', 'project', 'participants.user', 'comments.user', 'taskAttachments.user']);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'progress' => 'nullable|integer|min:0|max:100',
            'status_id' => 'required|exists:statuses,id',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task = Task::create($validated);

        return response()->json($task->load(['status', 'assignee', 'project']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $task->load([
            'status',
            'assignee',
            'project',
            'participants.user',
            'comments.user',
            'taskAttachments.user',
        ]);

        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'sometimes|required|in:lowest,low,medium,high,highest',
            'progress' => 'nullable|integer|min:0|max:100',
            'status_id' => 'sometimes|required|exists:statuses,id',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task->update($validated);

        return response()->json($task->load(['status', 'assignee', 'project']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * Update task status.
     */
    public function updateStatus(Request $request, Task $task, TaskActivityService $activityService)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $oldStatusId = $task->status_id;
        $newStatusId = (int) $validated['status_id'];

        // Only log if status actually changed
        if ($oldStatusId !== $newStatusId) {
            $task->update(['status_id' => $newStatusId]);
            $activityService->logStatusChange($task, $oldStatusId, $newStatusId);
        }

        return response()->json($task->load('status'));
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Request $request, Task $task, TaskActivityService $activityService)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $oldProgress = $task->progress;
        $newProgress = (int) $validated['progress'];

        // Only log if progress actually changed
        if ($oldProgress !== $newProgress) {
            $task->update(['progress' => $newProgress]);
            $activityService->logProgressChange($task, $oldProgress, $newProgress);
        }

        return response()->json($task);
    }

    // ==========================================
    // Task Comments
    // ==========================================

    /**
     * Add a comment to a task.
     */
    public function storeComment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $mentionService = new MentionService;
        $currentUser = auth()->user();

        // Parse mentions from content and merge with explicit mentions
        $parsedMentions = $mentionService->parseMentions($validated['content']);
        $explicitMentions = $validated['mentions'] ?? [];
        $allMentions = array_unique(array_merge($parsedMentions, $explicitMentions));

        // Validate mentions against mentionable users
        $mentionableUsers = $mentionService->getMentionableUsersForTask($task->id, $task->project_id);
        $validMentions = $mentionService->validateMentionedUsers($allMentions, $mentionableUsers);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $currentUser->id,
            'content' => $validated['content'],
            'mentions' => ! empty($validMentions) ? $validMentions : null,
        ]);

        // Send notifications to all participants and assignee
        $this->notifyTaskParticipants($task, $comment, $currentUser);

        // Send mention notifications
        $this->notifyMentionedUsers($task, $comment, $currentUser, $validMentions);

        return response()->json($comment->load('user'), 201);
    }

    /**
     * Notify users that were mentioned in a comment.
     *
     * @param  array<int>  $mentionedUserIds
     */
    private function notifyMentionedUsers(Task $task, TaskComment $comment, User $mentionedBy, array $mentionedUserIds): void
    {
        if (empty($mentionedUserIds)) {
            return;
        }

        // Don't notify the user who wrote the comment
        $mentionedUserIds = array_filter($mentionedUserIds, fn ($id) => $id !== $mentionedBy->id);

        if (empty($mentionedUserIds)) {
            return;
        }

        $users = User::whereIn('id', $mentionedUserIds)->get();

        foreach ($users as $user) {
            $user->notify(new UserMentionedInComment('task', $task, $comment, $mentionedBy));
        }
    }

    /**
     * Notify all task participants about a new comment.
     */
    private function notifyTaskParticipants(Task $task, TaskComment $comment, User $commentedBy): void
    {
        $task->load(['participants.user', 'assignee', 'project']);

        // Collect all users to notify (participants + assignee)
        $usersToNotify = collect();

        // Add participants
        foreach ($task->participants as $participant) {
            if ($participant->user) {
                $usersToNotify->push($participant->user);
            }
        }

        // Add assignee if exists
        if ($task->assignee) {
            $usersToNotify->push($task->assignee);
        }

        // Remove duplicates and the user who commented
        $usersToNotify = $usersToNotify
            ->unique('id')
            ->filter(fn ($user) => $user->id !== $commentedBy->id);

        // Send notifications
        foreach ($usersToNotify as $user) {
            $user->notify(new TaskCommentAdded($task, $comment, $commentedBy));
        }
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Task $task, TaskComment $comment)
    {
        if ($comment->user_id !== auth()->id() && ! auth()->user()->can('delete comments')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }

    /**
     * Get users that can be mentioned in a task comment.
     */
    public function getMentionableUsers(Task $task)
    {
        $mentionService = new MentionService;
        $users = $mentionService->getMentionableUsersForTask($task->id, $task->project_id);

        return response()->json($users->map(fn ($user) => [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->first_name.' '.$user->last_name,
            'email' => $user->email,
            'avatar' => $user->profile_photo_url ?? null,
        ]));
    }

    // ==========================================
    // Task Participants
    // ==========================================

    /**
     * Add a participant to a task.
     */
    public function addParticipant(Request $request, Task $task, TaskActivityService $activityService)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:50',
        ]);

        $existing = TaskParticipant::where('task_id', $task->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'User is already a participant on this task.'], 422);
        }

        $participant = TaskParticipant::create([
            'task_id' => $task->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
        ]);

        // Log the activity
        $activityService->logParticipantAdded($task, $participant);

        // Send notification to the new participant
        $user = User::find($validated['user_id']);
        $addedBy = Auth::user();

        if ($user && $addedBy && $user->id !== $addedBy->id) {
            $user->notify(new TaskParticipantAdded($task, $validated['role'], $addedBy));
        }

        return response()->json($participant->load('user'), 201);
    }

    /**
     * Remove a participant from a task.
     */
    public function removeParticipant(Task $task, $userId, TaskActivityService $activityService)
    {
        $participant = TaskParticipant::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'Participant not found.'], 404);
        }

        // Get the user info before deleting
        $user = User::find($userId);
        $role = $participant->role;

        $participant->delete();

        // Log the activity
        if ($user) {
            $activityService->logParticipantRemoved($task, $user, $role);
        }

        return response()->json(null, 204);
    }

    // ==========================================
    // Task Attachments
    // ==========================================

    /**
     * Upload an attachment to a task.
     */
    public function uploadAttachment(Request $request, Task $task, TaskActivityService $activityService)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments/'.$task->id, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        // Log the activity
        $activityService->logAttachmentAdded($task, $attachment);

        return response()->json($attachment->load('user'), 201);
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(Task $task, TaskAttachment $attachment, TaskActivityService $activityService)
    {
        if ($attachment->user_id !== auth()->id() && ! auth()->user()->can('delete attachments')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fileName = $attachment->file_name;

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        // Log the activity
        $activityService->logAttachmentRemoved($task, $fileName);

        return response()->json(null, 204);
    }

    /**
     * Get all activities for a task.
     */
    public function getActivities(Task $task, TaskActivityService $activityService)
    {
        $activities = $activityService->getTaskActivities($task);

        return response()->json($activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'properties' => $activity->properties,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->first_name.' '.$activity->causer->last_name,
                ] : ['id' => null, 'name' => 'Système'],
                'created_at' => $activity->created_at->toIso8601String(),
            ];
        }));
    }
}
