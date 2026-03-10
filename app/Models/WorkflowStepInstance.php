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

/**
 * @property int $id
 * @property string $uuid
 * @property int $workflow_instance_id
 * @property int $workflow_step_id
 * @property StepInstanceStatus $status
 * @property array<array-key, mixed>|null $input_data
 * @property array<array-key, mixed>|null $output_data
 * @property array<array-key, mixed>|null $context
 * @property int $attempt_count
 * @property int $max_attempts
 * @property string|null $error_message
 * @property array<array-key, mixed>|null $error_details
 * @property int|null $assigned_to
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property int|null $escalated_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowActivityLog> $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StepApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\User|null $assignedUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowInstance> $childWorkflowInstances
 * @property-read int|null $child_workflow_instances_count
 * @property-read \App\Models\User|null $completedByUser
 * @property-read \App\Models\User|null $escalatedToUser
 * @property-read \App\Models\WorkflowStep $step
 * @property-read \App\Models\WorkflowInstance $workflowInstance
 * @method static \Database\Factories\WorkflowStepInstanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereAttemptCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereErrorDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereEscalatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereEscalatedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereInputData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereMaxAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereOutputData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereWorkflowInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStepInstance whereWorkflowStepId($value)
 * @mixin \Eloquent
 */
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

        static::creating(function (self $model): void {
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

    public function fail(?string $message = null, array $details = []): self
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
