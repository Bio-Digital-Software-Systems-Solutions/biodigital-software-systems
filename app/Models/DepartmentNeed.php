<?php

namespace App\Models;

use App\Enums\Need\NeedCategory;
use App\Enums\Need\NeedPriority;
use App\Enums\Need\NeedStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $requester_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string|null $description
 * @property NeedCategory $category
 * @property NeedPriority $priority
 * @property NeedStatus $status
 * @property numeric|null $estimated_cost
 * @property numeric|null $approved_budget
 * @property numeric|null $actual_cost
 * @property string $currency
 * @property int $quantity
 * @property string|null $unit
 * @property string|null $justification
 * @property array<array-key, mixed>|null $specifications
 * @property array<array-key, mixed>|null $vendor_info
 * @property int|null $approved_by
 * @property int|null $rejected_by
 * @property string|null $rejection_reason
 * @property int|null $workflow_instance_id
 * @property int|null $form_submission_id
 * @property \Illuminate\Support\Carbon|null $needed_by
 * @property \Illuminate\Support\Carbon|null $expected_delivery
 * @property \Illuminate\Support\Carbon|null $actual_delivery
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $ordered_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NeedAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NeedComment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\DepartmentFormSubmission|null $formSubmission
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NeedComment> $internalComments
 * @property-read int|null $internal_comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NeedComment> $publicComments
 * @property-read int|null $public_comments_count
 * @property-read \App\Models\User|null $rejecter
 * @property-read \App\Models\User $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NeedStatusHistory> $statusHistory
 * @property-read int|null $status_history_count
 * @property-read \App\Models\WorkflowInstance|null $workflowInstance
 * @method static \Database\Factories\DepartmentNeedFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereActualCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereActualDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereApprovedBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereExpectedDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereFormSubmissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereJustification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereNeededBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereOrderedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereSpecifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereVendorInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed whereWorkflowInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentNeed withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentNeed extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'department_id',
        'requester_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'estimated_cost',
        'approved_budget',
        'actual_cost',
        'currency',
        'quantity',
        'unit',
        'justification',
        'specifications',
        'vendor_info',
        'approved_by',
        'rejected_by',
        'rejection_reason',
        'workflow_instance_id',
        'form_submission_id',
        'needed_by',
        'expected_delivery',
        'actual_delivery',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'ordered_at',
        'delivered_at',
        'completed_at',
    ];

    protected $casts = [
        'category' => NeedCategory::class,
        'priority' => NeedPriority::class,
        'status' => NeedStatus::class,
        'estimated_cost' => 'decimal:2',
        'approved_budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'specifications' => 'array',
        'vendor_info' => 'array',
        'needed_by' => 'date',
        'expected_delivery' => 'date',
        'actual_delivery' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'ordered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $model): void {
            $originalValue = $model->getRawOriginal('status');
            $newValue = $model->status?->value ?? $model->status;
            if ($originalValue !== $newValue) {
                $model->recordStatusChange($originalValue);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(DepartmentFormSubmission::class, 'form_submission_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NeedAttachment::class, 'need_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NeedComment::class, 'need_id');
    }

    public function publicComments(): HasMany
    {
        return $this->hasMany(NeedComment::class, 'need_id')->where('is_internal', false);
    }

    public function internalComments(): HasMany
    {
        return $this->hasMany(NeedComment::class, 'need_id')->where('is_internal', true);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(NeedStatusHistory::class, 'need_id')->orderByDesc('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isDraft(): bool
    {
        return $this->status === NeedStatus::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === NeedStatus::SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === NeedStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === NeedStatus::REJECTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === NeedStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === NeedStatus::CANCELLED;
    }

    public function canTransitionTo(NeedStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function submit(): self
    {
        if ($this->canTransitionTo(NeedStatus::SUBMITTED)) {
            $this->update([
                'status' => NeedStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);
        }

        return $this;
    }

    public function approve(int $approverId, ?float $budget = null): self
    {
        if ($this->canTransitionTo(NeedStatus::APPROVED)) {
            $this->update([
                'status' => NeedStatus::APPROVED,
                'approved_by' => $approverId,
                'approved_budget' => $budget ?? $this->estimated_cost,
                'approved_at' => now(),
            ]);
        }

        return $this;
    }

    public function reject(int $rejecterId, ?string $reason = null): self
    {
        if ($this->canTransitionTo(NeedStatus::REJECTED)) {
            $this->update([
                'status' => NeedStatus::REJECTED,
                'rejected_by' => $rejecterId,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);
        }

        return $this;
    }

    public function order(): self
    {
        if ($this->canTransitionTo(NeedStatus::ORDERED)) {
            $this->update([
                'status' => NeedStatus::ORDERED,
                'ordered_at' => now(),
            ]);
        }

        return $this;
    }

    public function markDelivered(): self
    {
        if ($this->canTransitionTo(NeedStatus::DELIVERED)) {
            $this->update([
                'status' => NeedStatus::DELIVERED,
                'actual_delivery' => now()->toDateString(),
                'delivered_at' => now(),
            ]);
        }

        return $this;
    }

    public function complete(): self
    {
        if ($this->canTransitionTo(NeedStatus::COMPLETED)) {
            $this->update([
                'status' => NeedStatus::COMPLETED,
                'completed_at' => now(),
            ]);
        }

        return $this;
    }

    public function cancel(): self
    {
        if ($this->canTransitionTo(NeedStatus::CANCELLED)) {
            $this->update(['status' => NeedStatus::CANCELLED]);
        }

        return $this;
    }

    /**
     * Withdraw a submitted need back to draft status.
     * Only the requester can withdraw their own submitted need.
     */
    public function withdraw(): self
    {
        if ($this->canTransitionTo(NeedStatus::DRAFT)) {
            $this->update([
                'status' => NeedStatus::DRAFT,
                'submitted_at' => null,
            ]);
        }

        return $this;
    }

    public function assign(int $userId): self
    {
        $this->update(['assigned_to' => $userId]);

        return $this;
    }

    public function getKanbanColumn(): string
    {
        return $this->status->kanbanColumn();
    }

    public function getTotalCost(): float
    {
        return ($this->actual_cost ?? $this->approved_budget ?? $this->estimated_cost ?? 0) * $this->quantity;
    }

    public function isOverBudget(): bool
    {
        if (!$this->approved_budget || !$this->actual_cost) {
            return false;
        }

        return $this->actual_cost > $this->approved_budget;
    }

    public function isOverdue(): bool
    {
        if (!$this->needed_by || $this->isCompleted() || $this->isCancelled()) {
            return false;
        }

        return $this->needed_by->isPast();
    }

    protected function recordStatusChange($fromStatus): void
    {
        if (!$this->exists) {
            return;
        }

        NeedStatusHistory::create([
            'need_id' => $this->id,
            'changed_by' => auth()->id(),
            'from_status' => $fromStatus,
            'to_status' => $this->status->value,
        ]);
    }
}
