<?php

namespace App\Models;

use App\Enums\Workflow\ApprovalType;
use App\Enums\Workflow\StepType;
use App\Enums\Workflow\TimeoutAction;
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
 * @property int $workflow_id
 * @property string $name
 * @property string|null $description
 * @property StepType $type
 * @property array<array-key, mixed>|null $config
 * @property string $position_x
 * @property string $position_y
 * @property bool $is_start
 * @property bool $is_end
 * @property ApprovalType|null $approval_type
 * @property array<array-key, mixed>|null $approvers
 * @property int|null $min_approvals
 * @property int|null $timeout_minutes
 * @property int|null $timeout_hours
 * @property TimeoutAction|null $timeout_action
 * @property int|null $escalation_user_id
 * @property int $retry_count
 * @property int|null $retry_delay_minutes
 * @property array<array-key, mixed>|null $conditions
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $timeout_config
 * @property int|null $reminder_interval_minutes
 * @property int|null $max_reminders
 * @property int|null $form_id
 * @property int|null $subprocess_workflow_id
 * @property string|null $action_type
 * @property string|null $action_config
 * @property string|null $notification_config
 * @property int|null $wait_minutes
 * @property string|null $wait_until_event
 * @property int $order
 * @property int $is_optional
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $escalationUser
 * @property-read \App\Models\DepartmentForm|null $form
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowTransition> $incomingTransitions
 * @property-read int|null $incoming_transitions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowStepInstance> $instances
 * @property-read int|null $instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowTransition> $outgoingTransitions
 * @property-read int|null $outgoing_transitions_count
 * @property-read \App\Models\DepartmentWorkflow $workflow
 * @method static \Database\Factories\WorkflowStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereActionConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereApprovalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereApprovers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereEscalationUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereFormId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereIsEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereIsOptional($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereIsStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereMaxReminders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereMinApprovals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereNotificationConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep wherePositionX($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep wherePositionY($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereReminderIntervalMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereRetryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereRetryDelayMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereSubprocessWorkflowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereTimeoutAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereTimeoutConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereTimeoutHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereTimeoutMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereWaitMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereWaitUntilEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowStep whereWorkflowId($value)
 * @mixin \Eloquent
 */
class WorkflowStep extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'form_id',
        'name',
        'description',
        'type',
        'order',
        'config',
        'position_x',
        'position_y',
        'is_start',
        'is_end',
        'approval_type',
        'approvers',
        'min_approvals',
        'timeout_hours',
        'timeout_action',
        'escalation_user_id',
        'retry_count',
        'retry_delay_minutes',
        'conditions',
        'metadata',
    ];

    protected $casts = [
        'type' => StepType::class,
        'approval_type' => ApprovalType::class,
        'timeout_action' => TimeoutAction::class,
        'config' => 'array',
        'approvers' => 'array',
        'conditions' => 'array',
        'metadata' => 'array',
        'is_start' => 'boolean',
        'is_end' => 'boolean',
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

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DepartmentWorkflow::class, 'workflow_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DepartmentForm::class, 'form_id');
    }

    public function escalationUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'from_step_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'to_step_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowStepInstance::class, 'workflow_step_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isApprovalStep(): bool
    {
        return $this->type === StepType::APPROVAL;
    }

    public function isFormStep(): bool
    {
        return $this->type === StepType::FORM;
    }

    public function isConditionStep(): bool
    {
        return $this->type === StepType::CONDITION;
    }

    public function requiresUserAction(): bool
    {
        return in_array($this->type, [
            StepType::APPROVAL,
            StepType::FORM,
            StepType::ACTION,
        ]);
    }

    public function getApproverUsers(): \Illuminate\Support\Collection
    {
        if (empty($this->approvers)) {
            return collect();
        }

        $userIds = [];
        $roleNames = [];

        foreach ($this->approvers as $approver) {
            if ($approver['type'] === 'user') {
                $userIds[] = $approver['id'];
            } elseif ($approver['type'] === 'role') {
                $roleNames[] = $approver['name'];
            }
        }

        $users = User::whereIn('id', $userIds)->get();

        if ($roleNames !== []) {
            $roleUsers = User::role($roleNames)->get();
            $users = $users->merge($roleUsers)->unique('id');
        }

        return $users;
    }
}
