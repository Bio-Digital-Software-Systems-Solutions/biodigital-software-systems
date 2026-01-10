<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use Illuminate\Http\Request;
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
    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $task->update(['status_id' => $validated['status_id']]);

        return response()->json($task->load('status'));
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Request $request, Task $task)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->update(['progress' => $validated['progress']]);

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
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return response()->json($comment->load('user'), 201);
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

    // ==========================================
    // Task Participants
    // ==========================================

    /**
     * Add a participant to a task.
     */
    public function addParticipant(Request $request, Task $task)
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

        return response()->json($participant->load('user'), 201);
    }

    /**
     * Remove a participant from a task.
     */
    public function removeParticipant(Task $task, $userId)
    {
        $participant = TaskParticipant::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'Participant not found.'], 404);
        }

        $participant->delete();

        return response()->json(null, 204);
    }

    // ==========================================
    // Task Attachments
    // ==========================================

    /**
     * Upload an attachment to a task.
     */
    public function uploadAttachment(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments/' . $task->id, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json($attachment->load('user'), 201);
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(Task $task, TaskAttachment $attachment)
    {
        if ($attachment->user_id !== auth()->id() && ! auth()->user()->can('delete attachments')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
