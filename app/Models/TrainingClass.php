<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string $uuid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Quiz> $allQuizzes
 * @property-read int|null $all_quizzes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClassMaterial> $materials
 * @property-read int|null $materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Quiz> $quizzes
 * @property-read int|null $quizzes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $students
 * @property-read int|null $students_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingClass whereUuid($value)
 * @mixin \Eloquent
 */
class TrainingClass extends Model
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

    public function materials(): HasMany
    {
        return $this->hasMany(TrainingClassMaterial::class)->ordered();
    }

    /**
     * Get the quizzes assigned to this training class.
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class')
            ->withPivot(['assigned_at', 'available_from', 'available_until', 'is_active'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * Get all quizzes (including inactive associations).
     */
    public function allQuizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class')
            ->withPivot(['assigned_at', 'available_from', 'available_until', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get students enrolled in this training class.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'training_enrollments', 'training_class_id', 'user_id')
            ->where('training_id', $this->training_id)
            ->withPivot(['status', 'progress', 'grade', 'attendance_rate', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Get the available quizzes for this class (considering date restrictions).
     */
    public function getAvailableQuizzes()
    {
        $now = now();

        return $this->quizzes()
            ->where(function ($query) use ($now) {
                $query->whereNull('quiz_training_class.available_from')
                      ->orWhere('quiz_training_class.available_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('quiz_training_class.available_until')
                      ->orWhere('quiz_training_class.available_until', '>=', $now);
            });
    }

    /**
     * Check if a quiz is available for this class.
     */
    public function hasQuiz(Quiz $quiz): bool
    {
        return $this->quizzes()->where('quiz_id', $quiz->id)->exists();
    }

    /**
     * Get completion statistics for all quizzes in this class.
     */
    public function getQuizCompletionStats(): array
    {
        $quizzes = $this->quizzes()->get();
        $totalStudents = $this->students()->count();
        $stats = [];

        foreach ($quizzes as $quiz) {
            $stats[] = array_merge(
                ['quiz' => $quiz],
                $quiz->getClassCompletionStats($this)
            );
        }

        return [
            'total_students' => $totalStudents,
            'total_quizzes' => $quizzes->count(),
            'quiz_stats' => $stats,
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
