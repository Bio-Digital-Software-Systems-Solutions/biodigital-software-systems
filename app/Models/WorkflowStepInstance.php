<?php

namespace App\Models;

use App\Enums\Workflow\StepInstanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WorkflowStepInstance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'workflow_instance_id',
        'workflow_step_id',
        'status',
        'input_data',
        'output_data',
        'context',
        'attempt_count',
        'max_attempts',
        'error_message',
        'error_details',
        'assigned_to',
        'completed_by',
        'started_at',
        'completed_at',
        'due_at',
        'escalated_at',
        'escalated_to',
    ];

    protected $casts = [
        'status' => StepInstanceStatus::class,
        'input_data' => 'array',
        'output_data' => 'array',
        'context' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
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

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function escalatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StepApproval::class, 'step_instance_id');
    }

    public function childWorkflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'parent_step_instance_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(WorkflowActivityLog::class, 'step_instance_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        return $this->status === StepInstanceStatus::PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === StepInstanceStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === StepInstanceStatus::COMPLETED;
    }

    public function isSkipped(): bool
    {
        return $this->status === StepInstanceStatus::SKIPPED;
    }

    public function isFailed(): bool
    {
        return $this->status === StepInstanceStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === StepInstanceStatus::CANCELLED;
    }

    public function isWaiting(): bool
    {
        return $this->status === StepInstanceStatus::WAITING;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            StepInstanceStatus::COMPLETED,
            StepInstanceStatus::SKIPPED,
            StepInstanceStatus::FAILED,
            StepInstanceStatus::CANCELLED,
        ]);
    }

    public function canRetry(): bool
    {
        return $this->attempt_count < $this->max_attempts;
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !$this->isFinished();
    }

    public function start(): self
    {
        $this->update([
            'status' => StepInstanceStatus::ACTIVE,
            'started_at' => now(),
            'attempt_count' => $this->attempt_count + 1,
        ]);

        return $this;
    }

    public function complete(array $outputData = [], ?int $completedBy = null): self
    {
        $this->update([
            'status' => StepInstanceStatus::COMPLETED,
            'output_data' => $outputData,
            'completed_at' => now(),
            'completed_by' => $completedBy,
        ]);

        return $this;
    }

    public function skip(): self
    {
        $this->update([
            'status' => StepInstanceStatus::SKIPPED,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function fail(string $message = null, array $details = []): self
    {
        $this->update([
            'status' => StepInstanceStatus::FAILED,
            'error_message' => $message,
            'error_details' => $details,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function cancel(): self
    {
        $this->update([
            'status' => StepInstanceStatus::CANCELLED,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function waitForSubWorkflow(): self
    {
        $this->update(['status' => StepInstanceStatus::WAITING]);

        return $this;
    }

    public function escalate(int $userId): self
    {
        $this->update([
            'escalated_at' => now(),
            'escalated_to' => $userId,
        ]);

        return $this;
    }

    public function assign(int $userId): self
    {
        $this->update(['assigned_to' => $userId]);

        return $this;
    }

    public function updateContext(array $data): self
    {
        $context = $this->context ?? [];
        $this->update(['context' => array_merge($context, $data)]);

        return $this;
    }
}
