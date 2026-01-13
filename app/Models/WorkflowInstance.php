<?php

namespace App\Models;

use App\Enums\Workflow\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WorkflowInstance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'department_id',
        'started_by',
        'name',
        'status',
        'context',
        'input_data',
        'output_data',
        'cancellation_reason',
        'failure_reason',
        'parent_instance_id',
        'parent_step_instance_id',
        'started_at',
        'completed_at',
        'cancelled_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => WorkflowInstanceStatus::class,
        'context' => 'array',
        'input_data' => 'array',
        'output_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'failed_at' => 'datetime',
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

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DepartmentWorkflow::class, 'workflow_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function parentInstance(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_instance_id');
    }

    public function childInstances(): HasMany
    {
        return $this->hasMany(self::class, 'parent_instance_id');
    }

    public function parentStepInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepInstance::class, 'parent_step_instance_id');
    }

    public function stepInstances(): HasMany
    {
        return $this->hasMany(WorkflowStepInstance::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(WorkflowActivityLog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        return $this->status === WorkflowInstanceStatus::PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === WorkflowInstanceStatus::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === WorkflowInstanceStatus::PAUSED;
    }

    public function isCompleted(): bool
    {
        return $this->status === WorkflowInstanceStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === WorkflowInstanceStatus::CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === WorkflowInstanceStatus::FAILED;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            WorkflowInstanceStatus::COMPLETED,
            WorkflowInstanceStatus::CANCELLED,
            WorkflowInstanceStatus::FAILED,
        ]);
    }

    public function start(): self
    {
        if ($this->status->canTransitionTo(WorkflowInstanceStatus::ACTIVE)) {
            $this->update([
                'status' => WorkflowInstanceStatus::ACTIVE,
                'started_at' => now(),
            ]);
        }

        return $this;
    }

    public function pause(): self
    {
        if ($this->status->canTransitionTo(WorkflowInstanceStatus::PAUSED)) {
            $this->update(['status' => WorkflowInstanceStatus::PAUSED]);
        }

        return $this;
    }

    public function resume(): self
    {
        if ($this->status === WorkflowInstanceStatus::PAUSED) {
            $this->update(['status' => WorkflowInstanceStatus::ACTIVE]);
        }

        return $this;
    }

    public function complete(array $outputData = []): self
    {
        if ($this->status->canTransitionTo(WorkflowInstanceStatus::COMPLETED)) {
            $this->update([
                'status' => WorkflowInstanceStatus::COMPLETED,
                'output_data' => $outputData,
                'completed_at' => now(),
            ]);
        }

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        if ($this->status->canTransitionTo(WorkflowInstanceStatus::CANCELLED)) {
            $this->update([
                'status' => WorkflowInstanceStatus::CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);
        }

        return $this;
    }

    public function fail(?string $reason = null): self
    {
        if ($this->status->canTransitionTo(WorkflowInstanceStatus::FAILED)) {
            $this->update([
                'status' => WorkflowInstanceStatus::FAILED,
                'failure_reason' => $reason,
                'failed_at' => now(),
            ]);
        }

        return $this;
    }

    public function getCurrentStep(): ?WorkflowStepInstance
    {
        return $this->stepInstances()
            ->whereIn('status', ['active', 'waiting'])
            ->first();
    }

    public function getCompletedSteps(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->stepInstances()
            ->where('status', 'completed')
            ->get();
    }

    public function getProgress(): float
    {
        $totalSteps = $this->workflow->steps()->count();
        if ($totalSteps === 0) {
            return 0;
        }

        $completedSteps = $this->stepInstances()->where('status', 'completed')->count();

        return round(($completedSteps / $totalSteps) * 100, 2);
    }

    public function updateContext(array $data): self
    {
        $context = $this->context ?? [];
        $this->update(['context' => array_merge($context, $data)]);

        return $this;
    }
}
