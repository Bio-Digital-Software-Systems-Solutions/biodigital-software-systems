<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $student_id
 * @property int $training_id
 * @property int|null $training_topic_id
 * @property string $type
 * @property numeric $grade
 * @property numeric $max_grade
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User $student
 * @property-read \App\Models\Training $training
 * @property-read \App\Models\TrainingTopic|null $trainingTopic
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereMaxGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereTrainingTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Evaluation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Evaluation extends Model
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
        'student_id',
        'training_id',
        'training_topic_id',
        'type',
        'grade',
        'max_grade',
        'comment',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'max_grade' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function trainingTopic(): BelongsTo
    {
        return $this->belongsTo(TrainingTopic::class, 'training_topic_id');
    }
}
