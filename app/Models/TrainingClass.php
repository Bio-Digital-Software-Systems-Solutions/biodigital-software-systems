<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $training_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property string|null $room
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $teacher_id
 * @property int|null $max_students
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClassSchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \App\Models\User|null $teacher
 * @property-read \App\Models\Training $training
 * @method static \Database\Factories\TrainingClassFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereMaxStudents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereRoom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereTeacherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TrainingClass extends Model
{
    use HasFactory, HasUuid, LogsActivity;

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
        'uuid',
        'training_id',
        'teacher_id',
        'name',
        'date',
        'start_time',
        'end_time',
        'room',
        'notes',
        'max_students',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TrainingClassSchedule::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
