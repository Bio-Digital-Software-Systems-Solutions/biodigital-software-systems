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
 * @property string $title
 * @property string|null $description
 * @property int $duration_minutes
 * @property int $max_score
 * @property int $passing_score
 * @property \Illuminate\Support\Carbon|null $available_from
 * @property \Illuminate\Support\Carbon|null $available_until
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizAttempt> $attempts
 * @property-read int|null $attempts_count
 * @property-read \App\Models\Training $training
 * @method static \Database\Factories\QuizFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereAvailableFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereAvailableUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereMaxScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz wherePassingScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereUpdatedAt($value)
 * @property string $uuid
 * @property int $max_attempts
 * @property string $score_display
 * @property string $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClass> $allTrainingClasses
 * @property-read int|null $all_training_classes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizQuestion> $questions
 * @property-read int|null $questions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClassMaterial> $trainingClassMaterials
 * @property-read int|null $training_class_materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClass> $trainingClasses
 * @property-read int|null $training_classes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereMaxAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereScoreDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereUuid($value)
 * @mixin \Eloquent
 */
class Quiz extends Model
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
        'training_id',
        'title',
        'description',
        'duration_minutes',
        'max_score',
        'passing_score',
        'available_from',
        'available_until',
        'is_active',
        'max_attempts',
        'score_display',
        'status',
    ];

    protected $casts = [
        'available_from' => 'date',
        'available_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the training classes that have access to this quiz.
     */
    public function trainingClasses(): BelongsToMany
    {
        return $this->belongsToMany(TrainingClass::class, 'quiz_training_class')
            ->withPivot(['assigned_at', 'available_from', 'available_until', 'is_active'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * Get all training classes (including inactive associations).
     */
    public function allTrainingClasses(): BelongsToMany
    {
        return $this->belongsToMany(TrainingClass::class, 'quiz_training_class')
            ->withPivot(['assigned_at', 'available_from', 'available_until', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the training class materials that have this quiz associated.
     */
    public function trainingClassMaterials(): BelongsToMany
    {
        return $this->belongsToMany(TrainingClassMaterial::class, 'quiz_training_class_material')
            ->withPivot(['assigned_at', 'is_active', 'order'])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderBy('pivot_order');
    }

    /**
     * Check if this quiz is available for a specific training class.
     */
    public function isAvailableForClass(TrainingClass $trainingClass): bool
    {
        return $this->trainingClasses()->where('training_class_id', $trainingClass->id)->exists();
    }

    /**
     * Check if this quiz is available for a specific training class material.
     */
    public function isAvailableForMaterial(TrainingClassMaterial $material): bool
    {
        return $this->trainingClassMaterials()->where('training_class_material_id', $material->id)->exists();
    }

    /**
     * Get quiz attempts by training class.
     */
    public function getAttemptsByClass(TrainingClass $trainingClass)
    {
        return $this->attempts()
            ->whereHas('student.trainings', function ($query) use ($trainingClass) {
                $query->wherePivot('training_class_id', $trainingClass->id);
            })
            ->with('student');
    }

    /**
     * Get completion statistics for a specific training class.
     */
    public function getClassCompletionStats(TrainingClass $trainingClass): array
    {
        $attempts = $this->getAttemptsByClass($trainingClass)->get();
        $totalStudents = $trainingClass->training->students()->count();
        $completedAttempts = $attempts->where('completed_at', '!=', null)->count();
        $passedAttempts = $attempts->where('passed', true)->count();

        return [
            'total_students' => $totalStudents,
            'total_attempts' => $attempts->count(),
            'completed_attempts' => $completedAttempts,
            'passed_attempts' => $passedAttempts,
            'completion_rate' => $totalStudents > 0 ? round(($completedAttempts / $totalStudents) * 100, 2) : 0,
            'pass_rate' => $completedAttempts > 0 ? round(($passedAttempts / $completedAttempts) * 100, 2) : 0,
        ];
    }
}
