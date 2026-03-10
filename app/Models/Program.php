<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $progress_percentage
 * @property array<array-key, mixed>|null $metadata
 * @property numeric|null $budget
 * @property string $priority
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read float $actual_progress
 * @property-read int $completed_tasks
 * @property-read int $duration_in_days
 * @property-read int $in_progress_tasks
 * @property-read int $pending_tasks
 * @property-read string $priority_label
 * @property-read float $remaining_budget
 * @property-read int $total_tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $morphTasks
 * @property-read int|null $morph_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgramStep> $steps
 * @property-read int|null $steps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program completed()
 * @method static \Database\Factories\ProgramFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program priority($priority)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Program whereUuid($value)
 * @mixin \Eloquent
 */
class Program extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'start_date',
        'end_date',
        'budget',
        'status',
        'priority',
        'progress_percentage',
        'metadata',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'progress_percentage' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user who created the program.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the steps for the program.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ProgramStep::class)->orderBy('order_index');
    }

    /**
     * Get the tasks for the program (via program_id foreign key).
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get all tasks associated with this program (polymorphic).
     */
    public function morphTasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /**
     * Calculate progress based on completed steps.
     */
    public function calculateProgress(): int
    {
        $totalSteps = $this->steps()->count();

        if ($totalSteps === 0) {
            return 0;
        }

        $completedSteps = $this->steps()->where('status', 'completed')->count();

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Get the total number of tasks.
     */
    public function getTotalTasksAttribute(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get the number of completed tasks.
     */
    public function getCompletedTasksAttribute(): int
    {
        return $this->tasks()->whereHas('status', function ($query): void {
            $query->where('name', 'completed');
        })->count();
    }

    /**
     * Get the number of pending tasks.
     */
    public function getPendingTasksAttribute(): int
    {
        return $this->tasks()->whereHas('status', function ($query): void {
            $query->where('name', 'pending');
        })->count();
    }

    /**
     * Get the number of in-progress tasks.
     */
    public function getInProgressTasksAttribute(): int
    {
        return $this->tasks()->whereHas('status', function ($query): void {
            $query->where('name', 'in_progress');
        })->count();
    }

    /**
     * Calculate the actual progress percentage based on completed tasks.
     */
    public function getActualProgressAttribute(): float
    {
        if ($this->total_tasks === 0) {
            return 0;
        }

        return round(($this->completed_tasks / $this->total_tasks) * 100, 2);
    }

    /**
     * Get the program duration in days.
     */
    public function getDurationInDaysAttribute(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Check if the program is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && now()->isAfter($this->end_date);
    }

    /**
     * Check if the program is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'in_progress']);
    }

    /**
     * Get the remaining budget.
     */
    public function getRemainingBudgetAttribute(): float
    {
        // This would need to be calculated based on actual expenses
        // For now, we'll return the budget as placeholder
        return (float) $this->budget;
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
     * Scope a query to only include active programs.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'in_progress']);
    }

    /**
     * Scope a query to only include completed programs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include overdue programs.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('end_date', '<', now());
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
