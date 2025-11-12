<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $program_id
 * @property string $name
 * @property string|null $description
 * @property int $order_index
 * @property \Illuminate\Support\Carbon $start_datetime
 * @property \Illuminate\Support\Carbon $end_datetime
 * @property int $duration_minutes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Participant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\Program $program
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\ProgramStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereEndDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereOrderIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereProgramId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereStartDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgramStep whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @mixin \Eloquent
 */
class ProgramStep extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

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
        'program_id',
        'name',
        'description',
        'order_index',
        'start_datetime',
        'end_datetime',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'duration_minutes' => 'integer',
        'order_index' => 'integer',
    ];

    /**
     * Get the program that owns the step.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the tasks for this step.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the participants for this step.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'program_step_participants')
            ->withPivot('role_in_step')
            ->withTimestamps();
    }

    /**
     * Get the users (participants) for this step.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_step_users')
            ->withPivot('role_in_step')
            ->withTimestamps();
    }

    /**
     * Check if the step is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the step is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the step is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope to order by order index.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}
