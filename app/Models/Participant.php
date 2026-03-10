<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $phone
 * @property array<array-key, mixed>|null $skills
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgramStep> $programSteps
 * @property-read int|null $program_steps_count
 * @method static \Database\Factories\ParticipantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Participant extends Model
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
        'name',
        'email',
        'role',
        'phone',
        'skills',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    /**
     * Get the program steps this participant is involved in.
     */
    public function programSteps(): BelongsToMany
    {
        return $this->belongsToMany(ProgramStep::class, 'program_step_participants')
            ->withPivot('role_in_step')
            ->withTimestamps();
    }
}
