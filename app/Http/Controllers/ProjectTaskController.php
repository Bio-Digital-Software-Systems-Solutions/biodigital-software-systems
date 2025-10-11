<?php

namespace App\Http\Controllers;

use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index()
    {
        $query = ProjectTask::with(['project', 'assignee', 'reporter', 'sprint']);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Filter by priority
        if (request('priority')) {
            $query->where('priority', request('priority'));
        }

        // Filter by type
        if (request('type')) {
            $query->where('type', request('type'));
        }

        // Filter by project
        if (request('project_id')) {
            $query->where('project_id', request('project_id'));
        }

        // Filter by assignee
        if (request('assignee_id')) {
            $query->where('assignee_id', request('assignee_id'));
        }

        $tasks = $query->latest()->paginate(20)->withQueryString();

        $projects = \App\Models\Project::select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        $users = User::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('ProjectTasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'filters' => request()->only(['search', 'status', 'priority', 'type', 'project_id', 'assignee_id']),
        ]);
    }

    public function show(ProjectTask $task)
    {
        $task->load([
            'project',
            'assignee',
            'reporter',
            'reviewer',
            'participants',
            'comments.user',
            'attachments.uploader',
        ]);

        $participants = $task->participants()->with('user')->get()->map(function ($participant) {
            return $participant->user;
        });

        $comments = $task->comments()->with('user')->latest()->get();

        $attachments = $task->attachments()->with('uploader')->latest()->get();

        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        // Get epics for the project (tasks with type='epic')
        $epics = ProjectTask::where('project_id', $task->project_id)
            ->where('type', 'epic')
            ->select('id', 'title', 'key')
            ->get();

        // Get sprints for the project
        $sprints = \App\Models\Sprint::where('project_id', $task->project_id)
            ->select('id', 'name')
            ->get();

        return Inertia::render('ProjectTasks/Show', [
            'task' => array_merge($task->toArray(), [
                'participants' => $participants,
                'comments' => $comments,
                'attachments' => $attachments,
            ]),
            'users' => $users,
            'epics' => $epics,
            'sprints' => $sprints,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:project_tasks,id',
            'status' => 'required|string|in:todo,in_progress,in_review,blocked,done,cancelled',
        ]);

        DB::beginTransaction();
        try {
            ProjectTask::whereIn('id', $validated['task_ids'])
                ->update(['status' => $validated['status']]);

            DB::commit();

            return redirect()->back()->with('success', 'Tâches mises à jour avec succès');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erreur lors de la mise à jour des tâches');
        }
    }

    public function storeComment(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $task->comments()->create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->back();
    }

    public function deleteComment(ProjectTask $task, $commentId)
    {
        $task->comments()->where('id', $commentId)->delete();

        return redirect()->back();
    }

    public function addParticipant(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $task->participants()->create([
            'user_id' => $validated['user_id'],
        ]);

        return redirect()->back();
    }

    public function removeParticipant(ProjectTask $task, $userId)
    {
        $task->participants()->where('user_id', $userId)->delete();

        return redirect()->back();
    }

    public function uploadAttachment(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments', 'public');

        // Determine file type
        $mimeType = $file->getMimeType();
        $fileType = 'document';

        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $fileType = 'video';
        }

        $task->attachments()->create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteAttachment(ProjectTask $task, $attachmentId)
    {
        $attachment = $task->attachments()->findOrFail($attachmentId);

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()->back();
    }
}
