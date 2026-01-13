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

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $model) {
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

    public function reject(int $rejecterId, string $reason = null): self
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
