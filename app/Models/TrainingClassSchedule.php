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
 * @property int $training_class_id
 * @property string $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string|null $room
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \App\Models\TrainingClass $trainingClass
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule active()
 * @method static \Database\Factories\TrainingClassScheduleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule forDay(string $day)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereRoom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereTrainingClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClassSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TrainingClassSchedule extends Model
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
        'training_class_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the training class that owns the schedule
     */
    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    /**
     * Get all attendances for this schedule
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope to get active schedules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by day of week
     */
    public function scopeForDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }
}
