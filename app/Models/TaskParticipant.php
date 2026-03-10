<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskParticipant whereUserId($value)
 * @mixin \Eloquent
 */
class TaskParticipant extends Model
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
        'task_id',
        'user_id',
        'role',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
