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
 * @property string $uuid
 * @property string $title
 * @property string $description
 * @property string $duration
 * @property string $level
 * @property numeric $price
 * @property string|null $image
 * @property string|null $category
 * @property int|null $teacher_id
 * @property numeric $rating
 * @property-read int|null $students_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClass> $classes
 * @property-read int|null $classes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingEnrollment> $enrollments
 * @property-read int|null $enrollments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingEvaluation> $evaluations
 * @property-read int|null $evaluations_count
 * @property-read string|null $image_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingMaterial> $materials
 * @property-read int|null $materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Quiz> $quizzes
 * @property-read int|null $quizzes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $students
 * @property-read \App\Models\User|null $teacher
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingTopic> $topics
 * @property-read int|null $topics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training byCategory(?string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training byLevel(?string $level)
 * @method static \Database\Factories\TrainingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStudentsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTeacherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereUuid($value)
 * @mixin \Eloquent
 */
class Training extends Model
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
        'title',
        'description',
        'duration',
        'level',
        'price',
        'image',
        'category',
        'teacher_id',
        'rating',
        'students_count',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(TrainingTopic::class)->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(TrainingMaterial::class)->orderBy('order');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(TrainingEvaluation::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->where('is_active', true);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(TrainingClass::class)->orderBy('date')->orderBy('start_time');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'training_enrollments')
            ->withPivot(['training_class_id', 'status', 'progress', 'grade', 'attendance_rate', 'motivation', 'payment_method', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, ?string $level)
    {
        if ($level) {
            return $query->where('level', $level);
        }

        return $query;
    }

    public function scopeByCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * Get the training's image URL.
     * Handles both local storage files and external URLs.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Check if the image is an external URL (starts with http:// or https://)
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Otherwise, it's a local file stored in storage/trainings
        return asset('storage/trainings/'.$this->image);
    }
}
