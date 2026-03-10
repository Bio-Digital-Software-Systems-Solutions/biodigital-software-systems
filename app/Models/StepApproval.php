<?php

namespace App\Models;

use App\Enums\Workflow\ApprovalDecision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $step_instance_id
 * @property int $approver_id
 * @property ApprovalDecision|null $decision
 * @property string|null $comments
 * @property array<array-key, mixed>|null $requested_changes
 * @property int|null $delegated_to
 * @property string|null $delegation_reason
 * @property int $order
 * @property bool $is_required
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User $approver
 * @property-read \App\Models\User|null $delegatedUser
 * @property-read \App\Models\WorkflowStepInstance $stepInstance
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereDecision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereDelegatedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereDelegationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereRequestedChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereStepInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StepApproval whereUuid($value)
 * @mixin \Eloquent
 */
class StepApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'step_instance_id',
        'approver_id',
        'decision',
        'comments',
        'requested_changes',
        'delegated_to',
        'delegation_reason',
        'order',
        'is_required',
        'notified_at',
        'decided_at',
        'due_at',
    ];

    protected $casts = [
        'decision' => ApprovalDecision::class,
        'requested_changes' => 'array',
        'is_required' => 'boolean',
        'notified_at' => 'datetime',
        'decided_at' => 'datetime',
        'due_at' => 'datetime',
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

    public function stepInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepInstance::class, 'step_instance_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function delegatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        return $this->decision === null;
    }

    public function isApproved(): bool
    {
        return $this->decision === ApprovalDecision::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->decision === ApprovalDecision::REJECTED;
    }

    public function hasRequestedChanges(): bool
    {
        return $this->decision === ApprovalDecision::REQUESTED_CHANGES;
    }

    public function isDelegated(): bool
    {
        return $this->decision === ApprovalDecision::DELEGATED;
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->isPending();
    }

    public function approve(?string $comments = null): self
    {
        $this->update([
            'decision' => ApprovalDecision::APPROVED,
            'comments' => $comments,
            'decided_at' => now(),
        ]);

        return $this;
    }

    public function reject(?string $comments = null): self
    {
        $this->update([
            'decision' => ApprovalDecision::REJECTED,
            'comments' => $comments,
            'decided_at' => now(),
        ]);

        return $this;
    }

    public function requestChanges(array $changes, ?string $comments = null): self
    {
        $this->update([
            'decision' => ApprovalDecision::REQUESTED_CHANGES,
            'requested_changes' => $changes,
            'comments' => $comments,
            'decided_at' => now(),
        ]);

        return $this;
    }

    public function delegate(int $userId, ?string $reason = null): self
    {
        $this->update([
            'decision' => ApprovalDecision::DELEGATED,
            'delegated_to' => $userId,
            'delegation_reason' => $reason,
            'decided_at' => now(),
        ]);

        return $this;
    }

    public function abstain(?string $comments = null): self
    {
        $this->update([
            'decision' => ApprovalDecision::ABSTAINED,
            'comments' => $comments,
            'decided_at' => now(),
        ]);

        return $this;
    }

    public function notify(): self
    {
        $this->update(['notified_at' => now()]);

        return $this;
    }
}
