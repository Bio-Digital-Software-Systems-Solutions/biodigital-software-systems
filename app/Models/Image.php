<?php

namespace App\Models;

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
 * @property string $imageable_type
 * @property int $imageable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float|null $aspect_ratio
 * @property-read string $dimensions
 * @property-read string $extension
 * @property-read string $formatted_size
 * @property-read string $full_url
 * @property-read string $srcset
 * @property-read string $thumbnail_url
 * @property-read Model|\Eloquent $imageable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image byMimeType($mimeType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image byOrientation($orientation)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image minimumSize($width, $height)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereImageableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereImageableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    use HasFactory, LogsActivity;

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
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'caption',
        'sort_order',
        'is_featured',
        'imageable_type',
        'imageable_id',
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
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Get the parent imageable model (Article, etc.).
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL to the image.
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }

        return asset('storage/'.$this->path);
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
     * Get the aspect ratio of the image.
     */
    public function getAspectRatioAttribute(): ?float
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Check if the image is landscape orientation.
     */
    public function isLandscape(): bool
    {
        return $this->aspect_ratio && $this->aspect_ratio > 1;
    }

    /**
     * Check if the image is portrait orientation.
     */
    public function isPortrait(): bool
    {
        return $this->aspect_ratio && $this->aspect_ratio < 1;
    }

    /**
     * Check if the image is square.
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
     * Get the thumbnail URL (if available).
     */
    public function getThumbnailUrlAttribute(): string
    {
        // This would typically be generated by an image processing service
        // For now, return the full URL
        return $this->full_url;
    }

    /**
     * Generate a responsive image srcset.
     */
    public function getSrcsetAttribute(): string
    {
        // This would typically generate multiple sizes
        // For now, return the single image
        return $this->full_url;
    }

    /**
     * Get the image dimensions as a string.
     */
    public function getDimensionsAttribute(): string
    {
        if (! $this->width || ! $this->height) {
            return 'Unknown';
        }

        return "{$this->width} × {$this->height}";
    }

    /**
     * Scope a query to only include featured images.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order images by sort order.
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
     * Scope a query to filter by minimum dimensions.
     */
    public function scopeMinimumSize($query, $width, $height)
    {
        return $query->where('width', '>=', $width)
            ->where('height', '>=', $height);
    }
}
