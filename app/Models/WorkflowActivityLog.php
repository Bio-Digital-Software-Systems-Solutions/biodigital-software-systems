<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $workflow_instance_id
 * @property int|null $step_instance_id
 * @property int|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property int $entity_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $entity
 * @property-read \App\Models\WorkflowStepInstance|null $stepInstance
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\WorkflowInstance|null $workflowInstance
 * @method static \Database\Factories\WorkflowActivityLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereStepInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowActivityLog whereWorkflowInstanceId($value)
 * @mixin \Eloquent
 */
class WorkflowActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'step_instance_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function stepInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepInstance::class, 'step_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        ?int $workflowInstanceId = null,
        ?int $stepInstanceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'workflow_instance_id' => $workflowInstanceId,
            'step_instance_id' => $stepInstanceId,
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function workflowStarted(WorkflowInstance $instance): self
    {
        return self::log(
            'started',
            WorkflowInstance::class,
            $instance->id,
            $instance->id,
            null,
            null,
            ['status' => $instance->status->value],
            ['workflow_name' => $instance->workflow->name]
        );
    }

    public static function workflowCompleted(WorkflowInstance $instance): self
    {
        return self::log(
            'completed',
            WorkflowInstance::class,
            $instance->id,
            $instance->id,
            null,
            ['status' => 'active'],
            ['status' => $instance->status->value],
            ['output_data' => $instance->output_data]
        );
    }

    public static function stepStarted(WorkflowStepInstance $stepInstance): self
    {
        return self::log(
            'step_started',
            WorkflowStepInstance::class,
            $stepInstance->id,
            $stepInstance->workflow_instance_id,
            $stepInstance->id,
            null,
            ['status' => $stepInstance->status->value],
            ['step_name' => $stepInstance->step->name]
        );
    }

    public static function stepCompleted(WorkflowStepInstance $stepInstance): self
    {
        return self::log(
            'step_completed',
            WorkflowStepInstance::class,
            $stepInstance->id,
            $stepInstance->workflow_instance_id,
            $stepInstance->id,
            ['status' => 'active'],
            ['status' => $stepInstance->status->value],
            ['output_data' => $stepInstance->output_data]
        );
    }

    public static function approvalDecision(StepApproval $approval, string $decision): self
    {
        return self::log(
            $decision,
            StepApproval::class,
            $approval->id,
            $approval->stepInstance->workflow_instance_id,
            $approval->step_instance_id,
            null,
            ['decision' => $decision, 'comments' => $approval->comments],
            ['approver_name' => $approval->approver->full_name]
        );
    }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            'started' => 'Workflow Started',
            'completed' => 'Workflow Completed',
            'cancelled' => 'Workflow Cancelled',
            'failed' => 'Workflow Failed',
            'paused' => 'Workflow Paused',
            'resumed' => 'Workflow Resumed',
            'step_started' => 'Step Started',
            'step_completed' => 'Step Completed',
            'step_skipped' => 'Step Skipped',
            'step_failed' => 'Step Failed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'delegated' => 'Delegated',
            'requested_changes' => 'Requested Changes',
            'escalated' => 'Escalated',
            'assigned' => 'Assigned',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIcon(): string
    {
        return match ($this->action) {
            'started' => 'play',
            'completed' => 'check-circle',
            'cancelled' => 'x-circle',
            'failed' => 'exclamation-circle',
            'paused' => 'pause',
            'resumed' => 'play',
            'step_started' => 'arrow-right',
            'step_completed' => 'check',
            'step_skipped' => 'forward',
            'step_failed' => 'x-mark',
            'approved' => 'hand-thumb-up',
            'rejected' => 'hand-thumb-down',
            'delegated' => 'arrow-path',
            'requested_changes' => 'pencil',
            'escalated' => 'arrow-up',
            'assigned' => 'user-plus',
            default => 'information-circle',
        };
    }

    public function getActionColor(): string
    {
        return match ($this->action) {
            'started', 'resumed' => 'blue',
            'completed', 'step_completed', 'approved' => 'green',
            'cancelled', 'rejected' => 'red',
            'failed', 'step_failed' => 'red',
            'paused' => 'yellow',
            'step_skipped' => 'gray',
            'delegated', 'escalated', 'assigned' => 'purple',
            'requested_changes' => 'orange',
            default => 'gray',
        };
    }
}
