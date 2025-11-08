<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $filename
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $duration
 * @property string $videoable_type
 * @property int $videoable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float|null $aspect_ratio
 * @property-read string $dimensions
 * @property-read string $extension
 * @property-read string $formatted_bitrate
 * @property-read string $formatted_duration
 * @property-read string $formatted_size
 * @property-read string $full_thumbnail_url
 * @property-read string $full_url
 * @property-read string $resolution
 * @property-read Model|\Eloquent $videoable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video byDuration($minDuration, $maxDuration = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video byMimeType($mimeType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video byOrientation($orientation)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video longForm()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video shortForm()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereVideoableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereVideoableType($value)
 * @mixin \Eloquent
 */
class Video extends Model
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'url',
        'thumbnail_path',
        'thumbnail_url',
        'mime_type',
        'size',
        'duration',
        'width',
        'height',
        'bitrate',
        'frame_rate',
        'title',
        'description',
        'sort_order',
        'is_featured',
        'videoable_type',
        'videoable_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'duration' => 'integer', // in seconds
            'width' => 'integer',
            'height' => 'integer',
            'bitrate' => 'integer',
            'frame_rate' => 'decimal:2',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Get the parent videoable model (Article, etc.).
     */
    public function videoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL to the video.
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }

        return asset('storage/'.$this->path);
    }

    /**
     * Get the full URL to the video thumbnail.
     */
    public function getFullThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        if ($this->thumbnail_path) {
            return asset('storage/'.$this->thumbnail_path);
        }

        // Return a default video thumbnail
        return asset('images/default-video-thumbnail.jpg');
    }

    /**
     * Get the file size in a human-readable format.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            return $bytes.' bytes';
        } elseif ($bytes == 1) {
            return $bytes.' byte';
        } else {
            return '0 bytes';
        }
    }

    /**
     * Get the duration in a human-readable format (HH:MM:SS).
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration) {
            return '00:00';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get the aspect ratio of the video.
     */
    public function getAspectRatioAttribute(): ?float
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Check if the video is landscape orientation.
     */
    public function isLandscape(): bool
    {
        return $this->aspect_ratio && $this->aspect_ratio > 1;
    }

    /**
     * Check if the video is portrait orientation.
     */
    public function isPortrait(): bool
    {
        return $this->aspect_ratio && $this->aspect_ratio < 1;
    }

    /**
     * Check if the video is square.
     */
    public function isSquare(): bool
    {
        return $this->aspect_ratio && $this->aspect_ratio === 1.0;
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get the video resolution.
     */
    public function getResolutionAttribute(): string
    {
        if (! $this->width || ! $this->height) {
            return 'Unknown';
        }

        // Common resolution names
        $resolutions = [
            '3840x2160' => '4K UHD',
            '2560x1440' => '1440p',
            '1920x1080' => '1080p',
            '1280x720' => '720p',
            '854x480' => '480p',
            '640x360' => '360p',
        ];

        $dimension = "{$this->width}x{$this->height}";

        return $resolutions[$dimension] ?? $dimension;
    }

    /**
     * Get the video dimensions as a string.
     */
    public function getDimensionsAttribute(): string
    {
        if (! $this->width || ! $this->height) {
            return 'Unknown';
        }

        return "{$this->width} × {$this->height}";
    }

    /**
     * Get the bitrate in a human-readable format.
     */
    public function getFormattedBitrateAttribute(): string
    {
        if (! $this->bitrate) {
            return 'Unknown';
        }

        if ($this->bitrate >= 1000000) {
            return number_format($this->bitrate / 1000000, 1).' Mbps';
        } elseif ($this->bitrate >= 1000) {
            return number_format($this->bitrate / 1000, 0).' Kbps';
        }

        return $this->bitrate.' bps';
    }

    /**
     * Check if the video is short form (under 60 seconds).
     */
    public function isShortForm(): bool
    {
        return $this->duration && $this->duration < 60;
    }

    /**
     * Check if the video is long form (over 10 minutes).
     */
    public function isLongForm(): bool
    {
        return $this->duration && $this->duration > 600;
    }

    /**
     * Scope a query to only include featured videos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order videos by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope a query to filter by mime type.
     */
    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope a query to filter by orientation.
     */
    public function scopeByOrientation($query, $orientation)
    {
        return match ($orientation) {
            'landscape' => $query->whereRaw('width > height'),
            'portrait' => $query->whereRaw('width < height'),
            'square' => $query->whereRaw('width = height'),
            default => $query
        };
    }

    /**
     * Scope a query to filter by duration range.
     */
    public function scopeByDuration($query, $minDuration, $maxDuration = null)
    {
        $query->where('duration', '>=', $minDuration);

        if ($maxDuration !== null) {
            $query->where('duration', '<=', $maxDuration);
        }

        return $query;
    }

    /**
     * Scope a query to filter short form videos.
     */
    public function scopeShortForm($query)
    {
        return $query->where('duration', '<', 60);
    }

    /**
     * Scope a query to filter long form videos.
     */
    public function scopeLongForm($query)
    {
        return $query->where('duration', '>', 600);
    }
}
