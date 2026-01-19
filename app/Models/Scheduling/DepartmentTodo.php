<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DepartmentTodo extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'uuid',
        'department_id',
        'shift_id',
        'assigned_to',
        'backup_assignees',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'sort_order',
        'estimated_minutes',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'status' => ShiftTaskStatus::class,
        'priority' => TodoPriority::class,
        'due_date' => 'date',
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
        'completed_at' => 'datetime',
        'backup_assignees' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'priority', 'assigned_to', 'due_date'])
            ->logOnlyDirty()
            ->useLogName('department_todo');
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the backup assignees as User models
     */
    public function getBackupUsersAttribute(): \Illuminate\Support\Collection
    {
        if (empty($this->backup_assignees)) {
            return collect();
        }

        return User::whereIn('id', $this->backup_assignees)->get();
    }

    // Scopes
    public function scopeForDepartment(Builder $query, Department|int $department): Builder
    {
        $departmentId = $department instanceof Department ? $department->id : $department;

        return $query->where('department_id', $departmentId);
    }

    public function scopeForShift(Builder $query, Shift|int|null $shift): Builder
    {
        if (! $shift) {
            return $query->whereNull('shift_id');
        }
        $shiftId = $shift instanceof Shift ? $shift->id : $shift;

        return $query->where('shift_id', $shiftId);
    }

    public function scopeAssignedTo(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeByStatus(Builder $query, ShiftTaskStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, TodoPriority $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShiftTaskStatus::TODO,
            ShiftTaskStatus::IN_PROGRESS,
            ShiftTaskStatus::BLOCKED,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ShiftTaskStatus::COMPLETED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ShiftTaskStatus::COMPLETED,
            ShiftTaskStatus::CANCELLED,
        ]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->active()
            ->whereDate('due_date', now()->toDateString());
    }

    public function scopeDueThisWeek(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('due_date')
            ->orderBy('sort_order');
    }

    public function scopeWithoutShift(Builder $query): Builder
    {
        return $query->whereNull('shift_id');
    }

    public function scopeWithShift(Builder $query): Builder
    {
        return $query->whereNotNull('shift_id');
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        return $this->due_date && $this->due_date->lt(now()->startOfDay());
    }

    public function getIsDueTodayAttribute(): bool
    {
        if ($this->status->isFinal() || ! $this->due_date) {
            return false;
        }

        return $this->due_date->isToday();
    }

    // Methods
    public function complete(User $user): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update([
            'status' => ShiftTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        return true;
    }

    public function start(): bool
    {
        if ($this->status !== ShiftTaskStatus::TODO) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::IN_PROGRESS]);

        return true;
    }

    public function pause(): bool
    {
        if ($this->status !== ShiftTaskStatus::IN_PROGRESS) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::TODO]);

        return true;
    }

    public function block(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::BLOCKED]);

        return true;
    }

    public function cancel(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::CANCELLED]);

        return true;
    }

    public function reopen(): bool
    {
        if (! $this->status->isFinal()) {
            return false;
        }

        $this->update([
            'status' => ShiftTaskStatus::TODO,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return true;
    }

    public function assign(User $user): bool
    {
        $this->update(['assigned_to' => $user->id]);

        return true;
    }

    public function unassign(): bool
    {
        $this->update(['assigned_to' => null]);

        return true;
    }

    // Array representation for API responses
    public function toArrayForApi(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'estimated_minutes' => $this->estimated_minutes,
            'is_overdue' => $this->is_overdue,
            'is_due_today' => $this->is_due_today,
            'assignee' => $this->assignee ? [
                'uuid' => $this->assignee->uuid,
                'name' => $this->assignee->full_name ?? $this->assignee->name,
                'avatar_url' => $this->assignee->avatar_url ?? null,
            ] : null,
            'backup_assignees' => $this->backup_users->map(fn ($user) => [
                'uuid' => $user->uuid,
                'name' => $user->full_name ?? $user->name,
                'avatar_url' => $user->avatar_url ?? null,
            ])->values()->toArray(),
            'creator' => $this->creator ? [
                'uuid' => $this->creator->uuid,
                'name' => $this->creator->full_name ?? $this->creator->name,
            ] : null,
            'shift' => $this->shift ? [
                'uuid' => $this->shift->uuid,
                'date' => $this->shift->date->format('Y-m-d'),
                'time_range' => $this->shift->time_range ?? "{$this->shift->start_time} - {$this->shift->end_time}",
            ] : null,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'completed_by' => $this->completedBy ? [
                'uuid' => $this->completedBy->uuid,
                'name' => $this->completedBy->full_name ?? $this->completedBy->name,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
