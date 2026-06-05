<?php

namespace App\Models;

use App\Traits\ClearsCache;
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
 * @property int $training_id
 * @property int|null $teacher_id
 * @property string $title
 * @property string $type
 * @property string|null $duration
 * @property string|null $url
 * @property string|null $file_path
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Training $training
 * @property-read \App\Models\User|null $uploadedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingClass> $classes
 * @property-read string|null $file_url
 */
class TrainingMaterial extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'training_id',
        'teacher_id',
        'title',
        'type',
        'duration',
        'url',
        'file_path',
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

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Classes where this material is presented. The pivot exposes the
     * per-class state (is_active, order, teacher_id who assigned it).
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            TrainingClass::class,
            'training_class_materials',
            'training_material_id',
            'training_class_id'
        )
            ->withPivot(['uuid', 'is_active', 'order', 'teacher_id'])
            ->withTimestamps();
    }

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
