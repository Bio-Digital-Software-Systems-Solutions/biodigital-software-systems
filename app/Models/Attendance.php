<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $training_class_id
 * @property int $student_id
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $training_class_schedule_id
 * @property-read \App\Models\User $student
 * @property-read \App\Models\TrainingClass $trainingClass
 * @property-read \App\Models\TrainingClassSchedule|null $trainingClassSchedule
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereTrainingClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereTrainingClassScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Attendance extends Model
{
    use LogsActivity, ClearsCache;

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
        'training_class_schedule_id',
        'student_id',
        'status',
        'notes',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    public function trainingClassSchedule(): BelongsTo
    {
        return $this->belongsTo(TrainingClassSchedule::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
