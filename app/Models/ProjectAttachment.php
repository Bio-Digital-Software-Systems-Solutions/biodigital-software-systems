<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property string $mime_type
 * @property int $file_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $file_url
 * @property-read string $formatted_file_size
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAttachment whereUserId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @mixin \Eloquent
 */
class ProjectAttachment extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

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
        'project_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
    ];

    protected $appends = ['file_url'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
