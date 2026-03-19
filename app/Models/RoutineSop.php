<?php

namespace App\Models;

use App\Enums\RoutineSopStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RoutineSop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'routine_id',
        'routine_step_id',
        'title',
        'description',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'extension',
        'status',
        'validated_by',
        'validated_at',
        'uploaded_by',
        'sort_order',
    ];

    protected $casts = [
        'status' => RoutineSopStatus::class,
        'validated_at' => 'datetime',
    ];

    protected $appends = [
        'file_url',
        'formatted_file_size',
        'file_type',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function routineStep(): BelongsTo
    {
        return $this->belongsTo(RoutineStep::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Accessors

    public function getFileUrlAttribute(): string
    {
        if ($this->relationLoaded('routine') && $this->routine) {
            $routine = $this->routine;
            $deptUuid = $routine->relationLoaded('department') && $routine->department
                ? $routine->department->uuid
                : Department::where('id', $routine->department_id)->value('uuid');

            return url("/departments/{$deptUuid}/routines/{$routine->uuid}/sops/{$this->uuid}/download");
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    public function getFileTypeAttribute(): string
    {
        return match (true) {
            in_array($this->extension, ['pdf']) => 'pdf',
            in_array($this->extension, ['doc', 'docx']) => 'word',
            in_array($this->extension, ['ppt', 'pptx']) => 'presentation',
            in_array($this->extension, ['xls', 'xlsx']) => 'spreadsheet',
            in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'image',
            in_array($this->extension, ['mp4', 'webm', 'mov', 'avi']) => 'video',
            in_array($this->extension, ['mp3', 'wav', 'ogg']) => 'audio',
            default => 'other',
        };
    }
}
