<?php

namespace App\Models;

use App\Enums\Workflow\WorkflowScope;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\WorkflowTriggerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DepartmentWorkflow extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'department_id',
        'created_by',
        'name',
        'description',
        'status',
        'trigger_type',
        'scope',
        'trigger_config',
        'variables',
        'settings',
        'version',
        'is_template',
        'parent_workflow_id',
        'activated_at',
        'deprecated_at',
    ];

    protected $casts = [
        'status' => WorkflowStatus::class,
        'trigger_type' => WorkflowTriggerType::class,
        'scope' => WorkflowScope::class,
        'trigger_config' => 'array',
        'variables' => 'array',
        'settings' => 'array',
        'is_template' => 'boolean',
        'activated_at' => 'datetime',
        'deprecated_at' => 'datetime',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentWorkflow(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_workflow_id');
    }

    public function childWorkflows(): HasMany
    {
        return $this->hasMany(self::class, 'parent_workflow_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_id')->orderBy('order');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'workflow_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'workflow_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return $this->status === WorkflowStatus::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status === WorkflowStatus::DRAFT;
    }

    public function isDeprecated(): bool
    {
        return $this->status === WorkflowStatus::DEPRECATED;
    }

    public function activate(): self
    {
        $this->update([
            'status' => WorkflowStatus::ACTIVE,
            'activated_at' => now(),
        ]);

        return $this;
    }

    public function deprecate(): self
    {
        $this->update([
            'status' => WorkflowStatus::DEPRECATED,
            'deprecated_at' => now(),
        ]);

        return $this;
    }

    public function duplicate(): self
    {
        $clone = $this->replicate(['uuid', 'activated_at', 'deprecated_at']);
        $clone->uuid = (string) Str::uuid();
        $clone->status = WorkflowStatus::DRAFT;
        $clone->name = $this->name . ' (Copy)';
        $clone->version = 1;
        $clone->parent_workflow_id = $this->id;
        $clone->save();

        // Duplicate steps
        $stepMapping = [];
        foreach ($this->steps as $step) {
            $newStep = $step->replicate(['uuid']);
            $newStep->uuid = (string) Str::uuid();
            $newStep->workflow_id = $clone->id;
            $newStep->save();
            $stepMapping[$step->id] = $newStep->id;
        }

        // Duplicate transitions with updated step references
        foreach ($this->transitions as $transition) {
            $newTransition = $transition->replicate(['uuid']);
            $newTransition->uuid = (string) Str::uuid();
            $newTransition->workflow_id = $clone->id;
            $newTransition->from_step_id = $stepMapping[$transition->from_step_id] ?? null;
            $newTransition->to_step_id = $stepMapping[$transition->to_step_id] ?? null;
            $newTransition->save();
        }

        return $clone;
    }
}
