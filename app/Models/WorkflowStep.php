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
            StepType::TASK,
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

        if (!empty($roleNames)) {
            $roleUsers = User::role($roleNames)->get();
            $users = $users->merge($roleUsers)->unique('id');
        }

        return $users;
    }
}
