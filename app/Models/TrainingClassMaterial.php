<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $training_class_id
 * @property int $teacher_id
 * @property string $title
 * @property string $type
 * @property string|null $file_path
 * @property string|null $url
 * @property string|null $duration
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TrainingClass $trainingClass
 * @property-read \App\Models\User $uploadedBy
 * @property-read string|null $file_url
 */
class TrainingClassMaterial extends Model
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
        'teacher_id',
        'title',
        'type',
        'file_path',
        'url',
        'duration',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'file_url',
    ];

    /**
     * Get the training class this material belongs to.
     */
    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    /**
     * Get the teacher who uploaded this material.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the full URL to the file.
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return null;
    }

    /**
     * Scope to get only active materials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order materials by their order column.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get the quizzes associated with this material.
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class_material')
            ->withPivot(['assigned_at', 'is_active', 'order'])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderBy('pivot_order');
    }

    /**
     * Get all quizzes (including inactive associations).
     */
    public function allQuizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_training_class_material')
            ->withPivot(['assigned_at', 'is_active', 'order'])
            ->withTimestamps();
    }

    /**
     * Check if this material has a specific quiz associated.
     */
    public function hasQuiz(Quiz $quiz): bool
    {
        return $this->quizzes()->where('quiz_id', $quiz->id)->exists();
    }
}
