<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $need_id
 * @property int $uploaded_by
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $path
 * @property string $disk
 * @property string $type
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\DepartmentNeed $need
 * @property-read \App\Models\User $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereNeedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedAttachment whereUuid($value)
 * @mixin \Eloquent
 */
class NeedAttachment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'need_id',
        'uploaded_by',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'disk',
        'type',
        'description',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (self $model): void {
            // Delete the file when the attachment is deleted
            $model->deleteFile();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function need(): BelongsTo
    {
        return $this->belongsTo(DepartmentNeed::class, 'need_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getTemporaryUrl(int $minutes = 5): string
    {
        if ($this->disk === 's3' || Storage::disk($this->disk)->providesTemporaryUrls()) {
            return Storage::disk($this->disk)->temporaryUrl(
                $this->path,
                now()->addMinutes($minutes)
            );
        }

        return $this->getUrl();
    }

    public function download()
    {
        return Storage::disk($this->disk)->download($this->path, $this->original_filename);
    }

    public function deleteFile(): bool
    {
        return Storage::disk($this->disk)->delete($this->path);
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    public function getIcon(): string
    {
        if ($this->isImage()) {
            return 'photo';
        }

        if ($this->isPdf()) {
            return 'document-text';
        }

        if (str_contains($this->mime_type, 'spreadsheet') || str_contains($this->mime_type, 'excel')) {
            return 'table-cells';
        }

        if (str_contains($this->mime_type, 'presentation') || str_contains($this->mime_type, 'powerpoint')) {
            return 'presentation-chart-bar';
        }

        return 'document';
    }
}
