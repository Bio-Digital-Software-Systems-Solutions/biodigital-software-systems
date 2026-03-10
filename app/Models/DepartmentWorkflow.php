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

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $created_by
 * @property string $name
 * @property string|null $description
 * @property WorkflowStatus $status
 * @property WorkflowTriggerType $trigger_type
 * @property WorkflowScope $scope
 * @property array<array-key, mixed>|null $trigger_config
 * @property array<array-key, mixed>|null $variables
 * @property array<array-key, mixed>|null $settings
 * @property int $version
 * @property bool $is_template
 * @property int|null $parent_workflow_id
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $deprecated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DepartmentWorkflow> $childWorkflows
 * @property-read int|null $child_workflows_count
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowInstance> $instances
 * @property-read int|null $instances_count
 * @property-read DepartmentWorkflow|null $parentWorkflow
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowStep> $steps
 * @property-read int|null $steps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowTransition> $transitions
 * @property-read int|null $transitions_count
 * @method static \Database\Factories\DepartmentWorkflowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereActivatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereDeprecatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereIsTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereParentWorkflowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereTriggerConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereTriggerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereVariables($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentWorkflow withoutTrashed()
 * @mixin \Eloquent
 */
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
