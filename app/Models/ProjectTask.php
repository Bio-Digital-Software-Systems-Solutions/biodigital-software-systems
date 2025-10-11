<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $key
 * @property string|null $description
 * @property int $project_id
 * @property int|null $parent_id
 * @property int|null $assignee_id
 * @property int $reporter_id
 * @property TaskStatus $status
 * @property Priority $priority
 * @property TaskType $type
 * @property int|null $story_points
 * @property numeric|null $estimated_hours
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property int|null $sprint_id
 * @property int|null $epic_id
 * @property array<array-key, mixed>|null $labels
 * @property array<array-key, mixed>|null $custom_fields
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $reviewer_id
 * @property bool $reviewed
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $paused_at
 * @property \Illuminate\Support\Carbon|null $stopped_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectTask> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskComment> $comments
 * @property-read int|null $comments_count
 * @property-read ProjectTask|null $epic
 * @property-read ProjectTask|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $reporter
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Sprint|null $sprint
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask assignedTo($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask byProject($projectId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask byStatus($status)
 * @method static \Database\Factories\ProjectTaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereAssigneeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereCustomFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereEpicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereEstimatedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereLabels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask wherePausedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereReporterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereReviewed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereSprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereStoppedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereStoryPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectTask extends Model
{
    use HasFactory, HasUuid, SoftDeletes, LogsActivity;

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
    protected $fillable = [
        'title',
        'key',
        'description',
        'project_id',
        'parent_id',
        'assignee_id',
        'reporter_id',
        'reviewer_id',
        'status',
        'priority',
        'type',
        'story_points',
        'estimated_hours',
        'due_date',
        'sprint_id',
        'epic_id',
        'labels',
        'custom_fields',
        'position',
        'reviewed',
        'reviewed_at',
        'started_at',
        'paused_at',
        'stopped_at',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => Priority::class,
        'type' => TaskType::class,
        'due_date' => 'datetime',
        'story_points' => 'integer',
        'estimated_hours' => 'decimal:2',
        'labels' => 'array',
        'custom_fields' => 'json',
        'position' => 'integer',
        'reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    // Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'epic_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TaskParticipant::class, 'task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    // Methods
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               ! $this->status->isCompleted();
    }

    public function isBlocked(): bool
    {
        return $this->status === TaskStatus::BLOCKED;
    }
}
