<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pivot row between a TrainingClass and a TrainingMaterial.
 *
 * The same TrainingMaterial can be linked to multiple TrainingClasses; each
 * pivot row stores the per-class state: is_active (visible in that class),
 * order (display order within that class), teacher_id (who attached it).
 *
 * Modeled as a plain Model rather than a Pivot subclass so that other code
 * (like Quiz::trainingClassMaterials) can target it from belongsToMany — the
 * Pivot base class breaks foreign-key inference when used as a relation
 * target.
 *
 * @property int $id
 * @property string $uuid
 * @property int $training_class_id
 * @property int $training_material_id
 * @property int|null $teacher_id
 * @property bool $is_active
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TrainingClass $trainingClass
 * @property-read \App\Models\TrainingMaterial $material
 * @property-read \App\Models\User|null $assignedBy
 */
class TrainingClassMaterial extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    protected $table = 'training_class_materials';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'training_class_id',
        'training_material_id',
        'teacher_id',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(TrainingMaterial::class, 'training_material_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Quizzes attached to this specific class/material assignment. Quizzes
     * keep targeting the pivot row so the same TrainingMaterial can carry
     * different quizzes in different classes.
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class_material')
            ->withPivot(['assigned_at', 'is_active', 'order'])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderBy('pivot_order');
    }

    public function allQuizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class_material')
            ->withPivot(['assigned_at', 'is_active', 'order'])
            ->withTimestamps();
    }

    public function hasQuiz(Quiz $quiz): bool
    {
        return $this->quizzes()->where('quiz_id', $quiz->id)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
