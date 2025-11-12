<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $priority
 * @property numeric|null $estimated_hours
 * @property numeric|null $actual_hours
 * @property string|null $notes
 * @property int|null $status_id
 * @property int|null $program_id
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $program_step_id
 * @property-read \App\Models\User|null $assignedUser
 * @property-read string $assignee_name
 * @property-read float $completion_percentage
 * @property-read int|null $days_until_due
 * @property-read float $hours_variance
 * @property-read string $priority_label
 * @property-read \App\Models\Program|null $program
 * @property-read \App\Models\ProgramStep|null $programStep
 * @property-read \App\Models\Status|null $status
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task assignedTo($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task completed()
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task priority($priority)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereActualHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereEstimatedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProgramId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProgramStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 * @property string $uuid
 * @property string|null $key
 * @property string|null $image
 * @property string $type
 * @property int|null $story_points
 * @property array<array-key, mixed>|null $labels
 * @property array<array-key, mixed>|null $custom_fields
 * @property int $position
 * @property int|null $project_id
 * @property string|null $taskable_type
 * @property int|null $taskable_id
 * @property int|null $parent_id
 * @property int|null $reporter_id
 * @property int|null $sprint_id
 * @property int|null $epic_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read Task|null $epic
 * @property-read Task|null $parent
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\User|null $reporter
 * @property-read \App\Models\Sprint|null $sprint
 * @property-read Model|\Eloquent|null $taskable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCustomFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereEpicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereLabels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereReporterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereSprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStoryPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUuid($value)
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['status'];

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'key',
        'description',
        'due_date',
        'priority',
        'estimated_hours',
        'actual_hours',
        'notes',
        'status_id',
        'program_id',
        'program_step_id',
        'project_id',
        'assigned_to',
        'image',
        'taskable_type',
        'taskable_id',
        'parent_id',
        'reporter_id',
        'type',
        'story_points',
        'sprint_id',
        'epic_id',
        'labels',
        'custom_fields',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'labels' => 'array',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Get the status of the task.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the program that owns the task.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the program step that owns the task.
     */
    public function programStep(): BelongsTo
    {
        return $this->belongsTo(ProgramStep::class);
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Alias for assignedUser for backward compatibility with ProjectTask.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who reported/created the task.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the parent task.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the epic task.
     */
    public function epic(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'epic_id');
    }

    /**
     * Get the sprint this task belongs to.
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sprint::class);
    }

    /**
     * Get the parent taskable model (Project, Program, etc.).
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all attachments for this task.
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Check if the task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               ! $this->isCompleted() &&
               now()->isAfter($this->due_date);
    }

    /**
     * Check if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status && $this->status->name === 'completed';
    }

    /**
     * Check if the task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status && $this->status->name === 'in_progress';
    }

    /**
     * Check if the task is pending.
     */
    public function isPending(): bool
    {
        return $this->status && $this->status->name === 'pending';
    }

    /**
     * Get the days until due date.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        $diffInDays = now()->diffInDays($this->due_date, false);

        return (int) $diffInDays;
    }

    /**
     * Get the hours variance (actual vs estimated).
     */
    public function getHoursVarianceAttribute(): float
    {
        if (! $this->estimated_hours || ! $this->actual_hours) {
            return 0;
        }

        return $this->actual_hours - $this->estimated_hours;
    }

    /**
     * Get the completion percentage based on hours.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (! $this->estimated_hours || ! $this->actual_hours) {
            return $this->isCompleted() ? 100 : 0;
        }

        return min(100, ($this->actual_hours / $this->estimated_hours) * 100);
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'High Priority',
            'medium' => 'Medium Priority',
            'low' => 'Low Priority',
            default => 'Normal'
        };
    }

    /**
     * Get the assigned user's full name.
     */
    public function getAssigneeNameAttribute(): string
    {
        return $this->assignedUser ? $this->assignedUser->full_name : 'Unassigned';
    }

    /**
     * Scope a query to only include overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('name', '!=', 'completed');
        })
            ->where('due_date', '<', now());
    }

    /**
     * Scope a query to only include completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('name', 'completed');
        });
    }

    /**
     * Scope a query to only include pending tasks.
     */
    public function scopePending($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('name', 'pending');
        });
    }

    /**
     * Scope a query to only include in-progress tasks.
     */
    public function scopeInProgress($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('name', 'in_progress');
        });
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to filter by assigned user.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
