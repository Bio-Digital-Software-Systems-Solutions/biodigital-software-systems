<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectComment;
use App\Models\ProjectParticipant;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectCommentAdded;
use App\Notifications\ProjectParticipantAdded;
use App\Notifications\UserMentionedInComment;
use App\Services\Comment\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['manager', 'members']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()->cannot('viewAny', Project::class)) {
            $query->forUser($request->user());
        }

        $projects = $query->latest()->paginate(20);

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'project_manager_id' => 'nullable|exists:users,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $project = Project::create($validated);

        // Add creator as member
        $project->members()->attach($request->user()->id, [
            'is_lead' => true,
            'started_at' => now(),
        ]);

        return response()->json($project->load(['manager', 'members']), 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load(['manager', 'members', 'tasks', 'sprints']);

        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'sometimes|in:lowest,low,medium,high,highest',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'project_manager_id' => 'nullable|exists:users,id',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $project->update($validated);

        return response()->json($project->load(['manager', 'members']));
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function tasks(Project $project): JsonResponse
    {
        $tasks = $project->tasks()
            ->with(['assignee', 'reporter', 'sprint', 'status'])
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
        ]);

        $project->update($validated);

        return response()->json($project->load(['manager', 'members']));
    }

    public function addParticipant(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,contributor,observer',
        ]);

        // Check if already a participant
        $existingParticipant = $project->participants()
            ->where('user_id', $validated['user_id'])
            ->first();

        $participant = $project->participants()->updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['role' => $validated['role']]
        );

        // Send notification only if this is a new participant
        if (! $existingParticipant) {
            $user = User::find($validated['user_id']);
            $addedBy = Auth::user();

            if ($user && $addedBy && $user->id !== $addedBy->id) {
                $user->notify(new ProjectParticipantAdded($project, $validated['role'], $addedBy));
            }
        }

        return response()->json($participant->load('user'), 201);
    }

    public function removeParticipant(Request $request, Project $project, ProjectParticipant $participant): JsonResponse
    {
        if ($participant->project_id !== $project->id) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        $participant->delete();

        return response()->json(['message' => 'Participant removed successfully']);
    }

    public function storeComment(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:project_comments,id',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $mentionService = new MentionService;
        $currentUser = $request->user();

        // Parse mentions from content and merge with explicit mentions
        $parsedMentions = $mentionService->parseMentions($validated['content']);
        $explicitMentions = $validated['mentions'] ?? [];
        $allMentions = array_unique(array_merge($parsedMentions, $explicitMentions));

        // Validate mentions against mentionable users
        $mentionableUsers = $mentionService->getMentionableUsersForProject($project->id);
        $validMentions = $mentionService->validateMentionedUsers($allMentions, $mentionableUsers);

        $comment = $project->comments()->create([
            'user_id' => $currentUser->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'mentions' => ! empty($validMentions) ? $validMentions : null,
        ]);

        // Send notifications to all project participants
        $this->notifyProjectParticipants($project, $comment, $currentUser);

        // Send mention notifications
        $this->notifyMentionedUsersOnProject($project, $comment, $currentUser, $validMentions);

        return response()->json($comment->load(['user', 'replies']), 201);
    }

    /**
     * Notify users that were mentioned in a project comment.
     *
     * @param  array<int>  $mentionedUserIds
     */
    private function notifyMentionedUsersOnProject(Project $project, ProjectComment $comment, User $mentionedBy, array $mentionedUserIds): void
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
            $user->notify(new UserMentionedInComment('project', $project, $comment, $mentionedBy));
        }
    }

    /**
     * Get users that can be mentioned in a project comment.
     */
    public function getMentionableUsers(Project $project): JsonResponse
    {
        $mentionService = new MentionService;
        $users = $mentionService->getMentionableUsersForProject($project->id);

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

    /**
     * Notify all project participants about a new comment.
     */
    private function notifyProjectParticipants(Project $project, ProjectComment $comment, User $commentedBy): void
    {
        $project->load(['participants.user', 'members', 'manager']);

        // Collect all users to notify
        $usersToNotify = collect();

        // Add participants
        foreach ($project->participants as $participant) {
            if ($participant->user) {
                $usersToNotify->push($participant->user);
            }
        }

        // Add members
        foreach ($project->members as $member) {
            $usersToNotify->push($member);
        }

        // Add manager if exists
        if ($project->manager) {
            $usersToNotify->push($project->manager);
        }

        // Remove duplicates and the user who commented
        $usersToNotify = $usersToNotify
            ->unique('id')
            ->filter(fn ($user) => $user->id !== $commentedBy->id);

        // Send notifications
        foreach ($usersToNotify as $user) {
            $user->notify(new ProjectCommentAdded($project, $comment, $commentedBy));
        }
    }

    public function deleteComment(Request $request, Project $project, ProjectComment $comment): JsonResponse
    {
        if ($comment->project_id !== $project->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== $request->user()->id && ! $request->user()->can('manage programs')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function uploadAttachment(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Determine file type
        $fileType = 'document';
        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $fileType = 'video';
        }

        // Store file
        $filePath = $file->store('project-attachments', 'public');

        $attachment = $project->attachments()->create([
            'user_id' => $request->user()->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return response()->json($attachment->load('user'), 201);
    }

    public function deleteAttachment(Request $request, Project $project, ProjectAttachment $attachment): JsonResponse
    {
        if ($attachment->project_id !== $project->id) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        if ($attachment->user_id !== $request->user()->id && ! $request->user()->can('manage programs')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    /**
     * Create a task associated with a project.
     */
    public function storeTask(StoreProjectTaskRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'due_date' => $validated['due_date'] ?? null,
            'priority' => $validated['priority'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'status_id' => $validated['status_id'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'reporter_id' => $request->user()->id,
            'project_id' => $project->id,
            'taskable_type' => 'App\\Models\\Project',
            'taskable_id' => $project->id,
        ]);

        $task->load(['status', 'assignee', 'reporter']);

        return response()->json([
            'success' => true,
            'message' => 'Tâche créée avec succès',
            'task' => $task,
        ], 201);
    }
}
