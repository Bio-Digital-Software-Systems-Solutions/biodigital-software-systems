<?php

namespace App\Services\Need;

use App\Enums\Need\NeedStatus;
use App\Models\DepartmentNeed;
use App\Models\NeedAttachment;
use App\Models\NeedComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NeedService
{
    /**
     * Create a new need.
     */
    public function createNeed(array $data): DepartmentNeed
    {
        return DepartmentNeed::create($data);
    }

    /**
     * Update a need.
     */
    public function updateNeed(DepartmentNeed $need, array $data): DepartmentNeed
    {
        // Cannot update completed or cancelled needs
        if ($need->isCompleted() || $need->isCancelled()) {
            throw new \Exception('Cannot update a completed or cancelled need.');
        }

        $need->update($data);
        return $need->fresh();
    }

    /**
     * Submit a need for review.
     */
    public function submitNeed(DepartmentNeed $need): DepartmentNeed
    {
        if (!$need->isDraft()) {
            throw new \Exception('Only draft needs can be submitted.');
        }

        if (empty($need->title) || empty($need->description)) {
            throw new \Exception('Title and description are required before submitting.');
        }

        return $need->submit();
    }

    /**
     * Start review of a need.
     */
    public function startReview(DepartmentNeed $need, int $reviewerId): DepartmentNeed
    {
        if (!$need->canTransitionTo(NeedStatus::UNDER_REVIEW)) {
            throw new \Exception('Need cannot be moved to review.');
        }

        $need->update([
            'status' => NeedStatus::UNDER_REVIEW,
            'assigned_to' => $reviewerId,
        ]);

        return $need->fresh();
    }

    /**
     * Approve a need.
     */
    public function approveNeed(DepartmentNeed $need, int $approverId, ?float $approvedBudget = null): DepartmentNeed
    {
        if (!$need->canTransitionTo(NeedStatus::APPROVED)) {
            throw new \Exception('Need cannot be approved from current status.');
        }

        return $need->approve($approverId, $approvedBudget);
    }

    /**
     * Reject a need.
     */
    public function rejectNeed(DepartmentNeed $need, int $rejecterId, string $reason): DepartmentNeed
    {
        if (!$need->canTransitionTo(NeedStatus::REJECTED)) {
            throw new \Exception('Need cannot be rejected from current status.');
        }

        if ($reason === '' || $reason === '0') {
            throw new \Exception('Rejection reason is required.');
        }

        return $need->reject($rejecterId, $reason);
    }

    /**
     * Mark need as ordered.
     */
    public function markOrdered(DepartmentNeed $need): DepartmentNeed
    {
        return $need->order();
    }

    /**
     * Mark need as in progress.
     */
    public function markInProgress(DepartmentNeed $need): DepartmentNeed
    {
        if (!$need->canTransitionTo(NeedStatus::IN_PROGRESS)) {
            throw new \Exception('Need cannot be moved to in progress.');
        }

        $need->update(['status' => NeedStatus::IN_PROGRESS]);
        return $need->fresh();
    }

    /**
     * Mark need as delivered.
     */
    public function markDelivered(DepartmentNeed $need, ?float $actualCost = null): DepartmentNeed
    {
        if ($actualCost !== null) {
            $need->update(['actual_cost' => $actualCost]);
        }

        return $need->markDelivered();
    }

    /**
     * Complete a need.
     */
    public function completeNeed(DepartmentNeed $need): DepartmentNeed
    {
        return $need->complete();
    }

    /**
     * Cancel a need.
     */
    public function cancelNeed(DepartmentNeed $need): DepartmentNeed
    {
        return $need->cancel();
    }

    /**
     * Withdraw a submitted need back to draft.
     */
    public function withdrawNeed(DepartmentNeed $need): DepartmentNeed
    {
        if (!$need->isSubmitted()) {
            throw new \Exception('Only submitted needs can be withdrawn.');
        }

        return $need->withdraw();
    }

    /**
     * Assign a need to a user.
     */
    public function assignNeed(DepartmentNeed $need, int $userId): DepartmentNeed
    {
        return $need->assign($userId);
    }

    /**
     * Add attachment to a need.
     */
    public function addAttachment(DepartmentNeed $need, UploadedFile $file, int $uploaderId, string $type = 'document', ?string $description = null): NeedAttachment
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "needs/{$need->id}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file));

        return NeedAttachment::create([
            'need_id' => $need->id,
            'uploaded_by' => $uploaderId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'disk' => 'public',
            'type' => $type,
            'description' => $description,
        ]);
    }

    /**
     * Remove attachment from a need.
     */
    public function removeAttachment(NeedAttachment $attachment): void
    {
        $attachment->delete();
    }

    /**
     * Add comment to a need.
     */
    public function addComment(DepartmentNeed $need, int $userId, string $content, bool $isInternal = false, ?int $parentId = null): NeedComment
    {
        return NeedComment::create([
            'need_id' => $need->id,
            'user_id' => $userId,
            'content' => $content,
            'is_internal' => $isInternal,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Update a comment.
     */
    public function updateComment(NeedComment $comment, string $content): NeedComment
    {
        $comment->update(['content' => $content]);
        return $comment->fresh();
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(NeedComment $comment): void
    {
        $comment->delete();
    }

    /**
     * Get needs by status for Kanban view.
     */
    public function getNeedsForKanban(int $departmentId, ?int $userId = null): array
    {
        $query = DepartmentNeed::where('department_id', $departmentId)
            ->with(['requester', 'assignee']);

        if ($userId) {
            $query->where(function ($q) use ($userId): void {
                $q->where('requester_id', $userId)
                    ->orWhere('assigned_to', $userId);
            });
        }

        $needs = $query->get();

        // Group by Kanban column
        $columns = [];
        foreach (NeedStatus::cases() as $status) {
            $column = $status->kanbanColumn();
            if (!isset($columns[$column])) {
                $columns[$column] = [
                    'id' => $column,
                    'title' => ucfirst(str_replace('_', ' ', $column)),
                    'items' => [],
                ];
            }
        }

        foreach ($needs as $need) {
            $column = $need->getKanbanColumn();
            $columns[$column]['items'][] = $need;
        }

        return array_values($columns);
    }

    /**
     * Get need statistics for a department.
     */
    public function getDepartmentStats(int $departmentId): array
    {
        $needs = DepartmentNeed::where('department_id', $departmentId);

        $totalBudget = $needs->clone()->whereNotNull('approved_budget')->sum('approved_budget');
        $spentBudget = $needs->clone()->whereNotNull('actual_cost')->sum('actual_cost');

        return [
            'total_needs' => $needs->clone()->count(),
            'pending_needs' => $needs->clone()->whereIn('status', [
                NeedStatus::SUBMITTED,
                NeedStatus::UNDER_REVIEW,
            ])->count(),
            'approved_needs' => $needs->clone()->where('status', NeedStatus::APPROVED)->count(),
            'completed_needs' => $needs->clone()->where('status', NeedStatus::COMPLETED)->count(),
            'rejected_needs' => $needs->clone()->where('status', NeedStatus::REJECTED)->count(),
            'total_budget' => $totalBudget,
            'spent_budget' => $spentBudget,
            'remaining_budget' => $totalBudget - $spentBudget,
            'overdue_needs' => $needs->clone()
                ->whereNotNull('needed_by')
                ->where('needed_by', '<', now())
                ->whereNotIn('status', [
                    NeedStatus::COMPLETED,
                    NeedStatus::CANCELLED,
                    NeedStatus::REJECTED,
                ])
                ->count(),
        ];
    }

    /**
     * Get user's needs statistics.
     */
    public function getUserStats(int $userId): array
    {
        $requested = DepartmentNeed::where('requester_id', $userId);
        $assigned = DepartmentNeed::where('assigned_to', $userId);

        return [
            'requested' => [
                'total' => $requested->clone()->count(),
                'pending' => $requested->clone()->whereIn('status', [
                    NeedStatus::SUBMITTED,
                    NeedStatus::UNDER_REVIEW,
                ])->count(),
                'approved' => $requested->clone()->where('status', NeedStatus::APPROVED)->count(),
                'rejected' => $requested->clone()->where('status', NeedStatus::REJECTED)->count(),
            ],
            'assigned' => [
                'total' => $assigned->clone()->count(),
                'active' => $assigned->clone()->whereNotIn('status', [
                    NeedStatus::COMPLETED,
                    NeedStatus::CANCELLED,
                    NeedStatus::REJECTED,
                ])->count(),
            ],
        ];
    }

    /**
     * Search needs.
     */
    public function searchNeeds(int $departmentId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DepartmentNeed::where('department_id', $departmentId)
            ->with(['requester', 'assignee', 'department']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Duplicate a need.
     */
    public function duplicateNeed(DepartmentNeed $need, int $userId): DepartmentNeed
    {
        $newNeed = $need->replicate([
            'uuid',
            'status',
            'approved_by',
            'rejected_by',
            'rejection_reason',
            'workflow_instance_id',
            'form_submission_id',
            'submitted_at',
            'approved_at',
            'rejected_at',
            'ordered_at',
            'delivered_at',
            'completed_at',
            'actual_cost',
            'actual_delivery',
        ]);

        $newNeed->requester_id = $userId;
        $newNeed->status = NeedStatus::DRAFT;
        $newNeed->title = $need->title . ' (Copy)';
        $newNeed->save();

        return $newNeed;
    }
}
