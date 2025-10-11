<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Attachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectTask::with(['assignee', 'reporter', 'project', 'sprint']);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        $tasks = $query->latest()->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:project_tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'required|in:todo,in_progress,in_review,blocked,done,cancelled',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'type' => 'required|in:task,bug,feature,story,epic,subtask',
            'story_points' => 'nullable|integer|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'sprint_id' => 'nullable|exists:sprints,id',
            'epic_id' => 'nullable|exists:project_tasks,id',
            'labels' => 'nullable|array',
        ]);

        // Generate task key
        $project = Project::findOrFail($validated['project_id']);
        $counter = $project->tasks()->count() + 1;
        $prefix = strtoupper(Str::slug($project->name, ''));
        $validated['key'] = substr($prefix, 0, 4).'-'.$counter;
        $validated['reporter_id'] = $request->user()->id;

        $task = ProjectTask::create($validated);

        return response()->json($task->load(['assignee', 'reporter', 'project']), 201);
    }

    public function show(ProjectTask $task): JsonResponse
    {
        $task->load(['assignee', 'reporter', 'project', 'sprint', 'parent', 'children']);

        return response()->json($task);
    }

    public function update(Request $request, ProjectTask $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'reviewer_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:todo,in_progress,in_review,blocked,done,cancelled',
            'priority' => 'sometimes|in:lowest,low,medium,high,highest',
            'type' => 'sometimes|in:task,bug,feature,story,epic,subtask',
            'story_points' => 'nullable|integer|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'sprint_id' => 'nullable|exists:sprints,id',
            'epic_id' => 'nullable|exists:project_tasks,id',
            'labels' => 'nullable|array',
            'reviewed' => 'nullable|boolean',
            'reviewed_at' => 'nullable|date',
            'started_at' => 'nullable|date',
            'paused_at' => 'nullable|date',
            'stopped_at' => 'nullable|date',
        ]);

        $task->update($validated);

        return response()->json($task->load(['assignee', 'reporter', 'project']));
    }

    public function destroy(ProjectTask $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function updateStatus(Request $request, ProjectTask $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,in_review,blocked,done,cancelled',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    public function storeComment(Request $request, ProjectTask $task): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function deleteComment(Request $request, ProjectTask $task, TaskComment $comment): JsonResponse
    {
        if ($comment->task_id !== $task->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== $request->user()->id && ! $request->user()->can('delete programs')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function addParticipant(Request $request, ProjectTask $task): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string',
        ]);

        $participant = TaskParticipant::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $validated['user_id']],
            ['role' => $validated['role'] ?? 'participant']
        );

        return response()->json($participant->load('user'), 201);
    }

    public function removeParticipant(Request $request, ProjectTask $task, User $user): JsonResponse
    {
        $participant = TaskParticipant::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        $participant->delete();

        return response()->json(['message' => 'Participant removed successfully']);
    }

    public function uploadAttachment(Request $request, ProjectTask $task): JsonResponse
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
        $filePath = $file->store('task-attachments', 'public');

        $attachment = $task->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return response()->json($attachment->load('uploader'), 201);
    }

    public function deleteAttachment(Request $request, ProjectTask $task, Attachment $attachment): JsonResponse
    {
        // Verify attachment belongs to this task
        if ($attachment->attachable_type !== ProjectTask::class || $attachment->attachable_id !== $task->id) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        if ($attachment->uploaded_by !== $request->user()->id && ! $request->user()->can('delete programs')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }
}
