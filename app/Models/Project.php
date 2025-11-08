<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Priority $priority
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property numeric|null $budget
 * @property int|null $project_manager_id
 * @property int|null $reviewer_id
 * @property bool $is_template
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectComment> $comments
 * @property-read int|null $comments_count
 * @property-read float $progress
 * @property-read \App\Models\User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\User|null $reviewer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sprint> $sprints
 * @property-read int|null $sprints_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project active()
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project forUser(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory, HasUuid, SoftDeletes, LogsActivity, ClearsCache;

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
        'name',
        'slug',
        'description',
        'status',
        'priority',
        'color',
        'start_date',
        'end_date',
        'budget',
        'project_manager_id',
        'reviewer_id',
        'is_template',
        'settings',
        'image',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'priority' => Priority::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'is_template' => 'boolean',
        'settings' => 'json',
    ];

    // Relations
    /**
     * Get all tasks associated with this project.
     * Uses polymorphic relation to the unified Task model.
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderBy('start_date');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['is_lead', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProjectParticipant::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', ProjectStatus::ACTIVE);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    // Accessors
    public function getProgressAttribute(): float
    {
        // Use the withCount result if available to avoid N+1 queries
        if (isset($this->attributes['tasks_count'])) {
            $totalTasks = $this->attributes['tasks_count'];
            if ($totalTasks === 0) {
                return 0;
            }

            // If completed_tasks_count was loaded via withCount, use it
            if (isset($this->attributes['completed_tasks_count'])) {
                $completedTasks = $this->attributes['completed_tasks_count'];
            } else {
                // Fall back to query if not loaded
                $completedTasks = $this->tasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'completed');
                    })
                    ->count();
            }

            return round(($completedTasks / $totalTasks) * 100, 2);
        }

        // Fall back to query if tasks_count not loaded
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()
            ->whereHas('status', function ($query) {
                $query->where('name', 'completed');
            })
            ->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    public function isOverdue(): bool
    {
        return $this->end_date &&
               $this->end_date->isPast() &&
               $this->status !== ProjectStatus::COMPLETED;
    }
}
