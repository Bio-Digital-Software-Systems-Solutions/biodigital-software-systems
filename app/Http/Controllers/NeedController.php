<?php

namespace App\Http\Controllers;

use App\Enums\Need\NeedCategory;
use App\Enums\Need\NeedPriority;
use App\Enums\Need\NeedStatus;
use App\Models\Department;
use App\Models\DepartmentNeed;
use App\Models\NeedAttachment;
use App\Models\NeedComment;
use App\Models\User;
use App\Services\Need\NeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NeedController extends Controller
{
    public function __construct(
        protected NeedService $needService
    ) {}

    /**
     * Display a listing of needs.
     */
    public function index(Request $request)
    {
        $query = DepartmentNeed::with(['department', 'requester', 'assignee'])
            ->withCount(['attachments', 'comments']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $needs = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Needs/Index', [
            'needs' => $needs,
            'departments' => Department::active()->ordered()->get(),
            'statuses' => NeedStatus::toSelectOptions(),
            'categories' => NeedCategory::toSelectOptions(),
            'priorities' => NeedPriority::toSelectOptions(),
            'filters' => $request->only(['department_id', 'status', 'category', 'priority', 'search']),
        ]);
    }

    /**
     * Display Kanban board view.
     */
    public function kanban(Request $request)
    {
        $departmentId = $request->department_id ?? Auth::user()->departments()->first()?->id;

        if (!$departmentId) {
            return redirect()->route('needs.index')
                ->with('error', 'Please select a department.');
        }

        $columns = $this->needService->getNeedsForKanban($departmentId);

        return Inertia::render('Needs/Kanban', [
            'columns' => $columns,
            'departmentId' => $departmentId,
            'departments' => Department::active()->ordered()->get(),
            'categories' => NeedCategory::toSelectOptions(),
            'priorities' => NeedPriority::toSelectOptions(),
        ]);
    }

    /**
     * Show the form for creating a new need.
     */
    public function create(Request $request)
    {
        return Inertia::render('Needs/Create', [
            'departments' => Department::active()->ordered()->get(),
            'users' => User::select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn($user): array => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]),
            'categories' => NeedCategory::toSelectOptions(),
            'priorities' => NeedPriority::toSelectOptions(),
            'departmentId' => $request->department_id,
        ]);
    }

    /**
     * Store a newly created need.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'priority' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'justification' => 'nullable|string',
            'specifications' => 'nullable|array',
            'vendor_info' => 'nullable|array',
            'needed_by' => 'nullable|date',
        ]);

        $validated['requester_id'] = Auth::id();
        $validated['status'] = NeedStatus::DRAFT;

        $need = $this->needService->createNeed($validated);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->needService->addAttachment($need, $file, Auth::id());
            }
        }

        return redirect()->route('needs.show', $need)
            ->with('success', 'Need created successfully.');
    }

    /**
     * Display the specified need.
     */
    public function show(DepartmentNeed $need)
    {
        $need->load([
            'department',
            'requester',
            'assignee',
            'approver',
            'rejecter',
            'attachments.uploader',
            'publicComments.user',
            'publicComments.replies.user',
            'statusHistory.changedBy',
        ]);

        return Inertia::render('Needs/Show', [
            'need' => $need,
            'canApprove' => $this->canApprove($need),
            'canEdit' => $this->canEdit($need),
            'canWithdraw' => $this->canWithdraw($need),
            'canCancel' => $this->canCancel($need),
        ]);
    }

    /**
     * Show the form for editing the need.
     */
    public function edit(DepartmentNeed $need)
    {
        if (!$this->canEdit($need)) {
            return back()->with('error', 'Cannot edit this need.');
        }

        $need->load(['attachments']);

        return Inertia::render('Needs/Edit', [
            'need' => $need,
            'departments' => Department::active()->ordered()->get(),
            'users' => User::select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn($user): array => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]),
            'categories' => NeedCategory::toSelectOptions(),
            'priorities' => NeedPriority::toSelectOptions(),
        ]);
    }

    /**
     * Update the specified need.
     */
    public function update(Request $request, DepartmentNeed $need)
    {
        if (!$this->canEdit($need)) {
            return back()->with('error', 'Cannot edit this need.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string',
            'priority' => 'sometimes|required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'justification' => 'nullable|string',
            'specifications' => 'nullable|array',
            'vendor_info' => 'nullable|array',
            'needed_by' => 'nullable|date',
        ]);

        try {
            $this->needService->updateNeed($need, $validated);
            return redirect()->route('needs.show', $need)
                ->with('success', 'Need updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit the need for review.
     */
    public function submit(DepartmentNeed $need)
    {
        try {
            $this->needService->submitNeed($need);
            return back()->with('success', 'Need submitted for review.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start review of the need.
     */
    public function startReview(DepartmentNeed $need)
    {
        try {
            $this->needService->startReview($need, Auth::id());
            return back()->with('success', 'Review started.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve the need.
     */
    public function approve(Request $request, DepartmentNeed $need)
    {
        // Check authorization
        if (!$this->canApprove($need)) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à approuver ce besoin.');
        }

        $validated = $request->validate([
            'approved_budget' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->needService->approveNeed($need, Auth::id(), $validated['approved_budget'] ?? null);
            return back()->with('success', 'Need approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject the need.
     */
    public function reject(Request $request, DepartmentNeed $need)
    {
        // Check authorization
        if (!$this->canApprove($need)) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à rejeter ce besoin.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->needService->rejectNeed($need, Auth::id(), $validated['reason']);
            return back()->with('success', 'Need rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark need as ordered.
     */
    public function markOrdered(DepartmentNeed $need)
    {
        try {
            $this->needService->markOrdered($need);
            return back()->with('success', 'Need marked as ordered.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark need as delivered.
     */
    public function markDelivered(Request $request, DepartmentNeed $need)
    {
        $validated = $request->validate([
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->needService->markDelivered($need, $validated['actual_cost'] ?? null);
            return back()->with('success', 'Need marked as delivered.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the need.
     */
    public function complete(DepartmentNeed $need)
    {
        try {
            $this->needService->completeNeed($need);
            return back()->with('success', 'Need completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the need.
     */
    public function cancel(DepartmentNeed $need)
    {
        // Check if user can cancel this need
        if (!$this->canCancel($need)) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à annuler ce besoin.');
        }

        try {
            $this->needService->cancelNeed($need);
            return back()->with('success', 'Besoin annulé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Withdraw a submitted need back to draft.
     * Only the requester can withdraw their own submitted need.
     */
    public function withdraw(DepartmentNeed $need)
    {
        // Only requester can withdraw their own need
        if ($need->requester_id !== Auth::id()) {
            return back()->with('error', 'Vous n\'êtes pas autorisé à retirer ce besoin.');
        }

        try {
            $this->needService->withdrawNeed($need);
            return back()->with('success', 'Besoin retiré et remis en brouillon.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the status of a need (generic method for Kanban board).
     * This method allows direct status updates for Kanban drag-and-drop.
     */
    public function updateStatus(Request $request, DepartmentNeed $need)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $newStatus = $validated['status'];

        try {
            // Map string status to enum
            $statusEnum = NeedStatus::tryFrom($newStatus);

            if (!$statusEnum) {
                throw new \Exception("Invalid status: {$newStatus}");
            }

            // Check authorization for approval-related status changes
            $approvalStatuses = [NeedStatus::APPROVED, NeedStatus::REJECTED, NeedStatus::UNDER_REVIEW];
            if (in_array($statusEnum, $approvalStatuses) && !$this->canApprove($need)) {
                $errorMsg = 'Vous n\'êtes pas autorisé à modifier ce statut.';
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 403);
                }
                return back()->with('error', $errorMsg);
            }

            // For Kanban, we allow direct status updates without strict workflow validation
            // This provides flexibility while still tracking the change
            $updateData = ['status' => $statusEnum];

            // Add timestamps for specific status changes
            switch ($statusEnum) {
                case NeedStatus::SUBMITTED:
                    $updateData['submitted_at'] = now();
                    break;
                case NeedStatus::APPROVED:
                    $updateData['approved_by'] = Auth::id();
                    $updateData['approved_at'] = now();
                    break;
                case NeedStatus::REJECTED:
                    $updateData['rejected_by'] = Auth::id();
                    $updateData['rejected_at'] = now();
                    $updateData['rejection_reason'] = $request->input('reason', 'Status changed via Kanban');
                    break;
                case NeedStatus::COMPLETED:
                    $updateData['completed_at'] = now();
                    break;
                case NeedStatus::CANCELLED:
                    // No cancelled_at field, just update status
                    break;
            }

            $need->update($updateData);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status updated successfully.',
                    'need' => $need->fresh(),
                ]);
            }

            return back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign the need to a user.
     */
    public function assign(Request $request, DepartmentNeed $need)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->needService->assignNeed($need, $validated['user_id']);
        return back()->with('success', 'Need assigned successfully.');
    }

    /**
     * Upload attachment.
     */
    public function uploadAttachment(Request $request, DepartmentNeed $need)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'nullable|string',
            'description' => 'nullable|string|max:255',
        ]);

        $attachment = $this->needService->addAttachment(
            $need,
            $request->file('file'),
            Auth::id(),
            $validated['type'] ?? 'document',
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'attachment' => $attachment,
        ]);
    }

    /**
     * Delete attachment.
     */
    public function deleteAttachment(NeedAttachment $attachment)
    {
        $this->needService->removeAttachment($attachment);
        return back()->with('success', 'Attachment deleted.');
    }

    /**
     * Add comment.
     */
    public function addComment(Request $request, DepartmentNeed $need)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'is_internal' => 'boolean',
            'parent_id' => 'nullable|exists:need_comments,id',
        ]);

        $this->needService->addComment(
            $need,
            Auth::id(),
            $validated['content'],
            $validated['is_internal'] ?? false,
            $validated['parent_id'] ?? null
        );

        return back()->with('success', 'Comment added.');
    }

    /**
     * Update comment.
     */
    public function updateComment(Request $request, NeedComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return back()->with('error', 'Cannot edit this comment.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $this->needService->updateComment($comment, $validated['content']);
        return back()->with('success', 'Comment updated.');
    }

    /**
     * Delete comment.
     */
    public function deleteComment(NeedComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return back()->with('error', 'Cannot delete this comment.');
        }

        $this->needService->deleteComment($comment);
        return back()->with('success', 'Comment deleted.');
    }

    /**
     * Duplicate the need.
     */
    public function duplicate(DepartmentNeed $need)
    {
        $newNeed = $this->needService->duplicateNeed($need, Auth::id());
        return redirect()->route('needs.edit', $newNeed)
            ->with('success', 'Need duplicated successfully.');
    }

    /**
     * Remove the specified need.
     */
    public function destroy(DepartmentNeed $need)
    {
        if (!$need->isDraft()) {
            return back()->with('error', 'Cannot delete non-draft need.');
        }

        $need->delete();
        return redirect()->route('needs.index')
            ->with('success', 'Need deleted successfully.');
    }

    /**
     * Get department statistics.
     */
    public function stats(Request $request)
    {
        $departmentId = $request->department_id ?? Auth::user()->departments()->first()?->id;

        if (!$departmentId) {
            return response()->json(['error' => 'No department selected'], 400);
        }

        return response()->json($this->needService->getDepartmentStats($departmentId));
    }

    /**
     * Get history for a specific need.
     */
    public function history(DepartmentNeed $need)
    {
        $history = $need->statusHistory()
            ->with('changedBy:id,first_name,last_name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($entry): array => [
                'id' => $entry->id,
                'from_status' => $entry->from_status?->value,
                'to_status' => $entry->to_status->value,
                'reason' => $entry->reason,
                'metadata' => $entry->metadata,
                'created_at' => $entry->created_at->toISOString(),
                'user' => $entry->changedBy ? [
                    'id' => $entry->changedBy->id,
                    'first_name' => $entry->changedBy->first_name,
                    'last_name' => $entry->changedBy->last_name,
                    'full_name' => $entry->changedBy->first_name . ' ' . $entry->changedBy->last_name,
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get comments for a specific need.
     */
    public function comments(DepartmentNeed $need)
    {
        $comments = $need->comments()
            ->with(['user:id,first_name,last_name,email', 'replies.user:id,first_name,last_name,email'])
            ->whereNull('parent_id') // Only top-level comments
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($comment): array => [
                'id' => $comment->id,
                'uuid' => $comment->uuid,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
                'created_at' => $comment->created_at->toISOString(),
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'first_name' => $comment->user->first_name,
                    'last_name' => $comment->user->last_name,
                    'full_name' => $comment->user->first_name . ' ' . $comment->user->last_name,
                ] : null,
                'replies' => $comment->replies->map(fn($reply): array => [
                    'id' => $reply->id,
                    'uuid' => $reply->uuid,
                    'content' => $reply->content,
                    'is_internal' => $reply->is_internal,
                    'created_at' => $reply->created_at->toISOString(),
                    'user' => $reply->user ? [
                        'id' => $reply->user->id,
                        'first_name' => $reply->user->first_name,
                        'last_name' => $reply->user->last_name,
                        'full_name' => $reply->user->first_name . ' ' . $reply->user->last_name,
                    ] : null,
                ]),
            ]);

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * Get my needs (requested and assigned).
     */
    public function myNeeds(Request $request)
    {
        $userStats = $this->needService->getUserStats(Auth::id());

        $requestedNeeds = DepartmentNeed::where('requester_id', Auth::id())
            ->with(['department'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $assignedNeeds = DepartmentNeed::where('assigned_to', Auth::id())
            ->with(['department', 'requester'])
            ->whereNotIn('status', [NeedStatus::COMPLETED, NeedStatus::CANCELLED, NeedStatus::REJECTED])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Needs/MyNeeds', [
            'stats' => $userStats,
            'requestedNeeds' => $requestedNeeds,
            'assignedNeeds' => $assignedNeeds,
        ]);
    }

    /**
     * Check if user can approve needs.
     * Rules:
     * - User cannot approve their own need (self-approval is forbidden)
     * - User must be department head OR have 'approve needs' permission
     */
    protected function canApprove(DepartmentNeed $need): bool
    {
        $user = Auth::user();

        // Self-approval is forbidden - creator cannot approve their own need
        if ($need->requester_id === $user->id) {
            return false;
        }

        // Check if user is department head
        if ($need->department->head_of_department === $user->id) {
            return true;
        }
        // Check for approval permission
        return (bool) $user->hasPermissionTo('approve needs');
    }

    /**
     * Check if user can edit the need.
     */
    protected function canEdit(DepartmentNeed $need): bool
    {
        $user = Auth::user();

        // Only drafts can be edited
        if (!$need->isDraft()) {
            return false;
        }

        // Only requester can edit
        return $need->requester_id === $user->id;
    }

    /**
     * Check if user can withdraw the need.
     * Only the requester can withdraw a submitted need.
     */
    protected function canWithdraw(DepartmentNeed $need): bool
    {
        $user = Auth::user();

        // Only submitted needs can be withdrawn
        if (!$need->isSubmitted()) {
            return false;
        }

        // Only requester can withdraw
        return $need->requester_id === $user->id;
    }

    /**
     * Check if user can cancel the need.
     * Requester can cancel their own need if it's in a cancellable status.
     */
    protected function canCancel(DepartmentNeed $need): bool
    {
        $user = Auth::user();

        // Cannot cancel completed, cancelled, or rejected needs
        if ($need->isCompleted() || $need->isCancelled() || $need->isRejected()) {
            return false;
        }

        // Only requester can cancel
        return $need->requester_id === $user->id;
    }
}
