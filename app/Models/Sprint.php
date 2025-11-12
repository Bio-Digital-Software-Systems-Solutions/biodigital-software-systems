<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $goal
 * @property int $project_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property int|null $capacity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read int $velocity
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint active()
 * @method static \Database\Factories\SprintFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereUpdatedAt($value)
 * @property string $uuid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sprint whereUuid($value)
 * @mixin \Eloquent
 */
class Sprint extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache;

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
        'goal',
        'project_id',
        'start_date',
        'end_date',
        'status',
        'capacity',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity' => 'integer',
    ];

    // Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'planned')
            ->where('start_date', '>', now());
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getVelocityAttribute(): int
    {
        return $this->tasks()
            ->whereHas('status', function ($query) {
                $query->where('name', 'completed');
            })
            ->sum('story_points') ?? 0;
    }
}
