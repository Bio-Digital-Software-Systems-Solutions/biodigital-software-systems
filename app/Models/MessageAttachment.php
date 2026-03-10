<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $message_id
 * @property string $filename
 * @property string $stored_filename
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $file_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string $download_url
 * @property-read string $file_url
 * @property-read string $human_file_size
 * @property-read bool $is_image
 * @property-read \App\Models\Message $message
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereStoredFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageAttachment whereUuid($value)
 * @mixin \Eloquent
 */
class MessageAttachment extends Model
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'message_id',
        'filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
        'file_type',
    ];

    /**
     * Get the message that owns the attachment.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the file URL for the attachment.
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the download URL for the attachment.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('messages.attachments.download', $this->uuid);
    }

    /**
     * Get human readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the attachment is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Determine file type based on MIME type.
     */
    public static function determineFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])) {
            return 'document';
        }

        if (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
        ])) {
            return 'archive';
        }

        return 'other';
    }
}
