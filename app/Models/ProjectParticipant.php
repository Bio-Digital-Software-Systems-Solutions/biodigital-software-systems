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
 * @property int $project_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectParticipant whereUserId($value)
 * @mixin \Eloquent
 */
class ProjectParticipant extends Model
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
        'project_id',
        'user_id',
        'role',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
