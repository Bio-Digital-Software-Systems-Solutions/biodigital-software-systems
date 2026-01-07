<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectComment;
use App\Models\ProjectParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $participant = $project->participants()->updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['role' => $validated['role']]
        );

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
        ]);

        $comment = $project->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json($comment->load(['user', 'replies']), 201);
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
}
