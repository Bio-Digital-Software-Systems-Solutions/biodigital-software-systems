<?php

namespace App\Models\Event;

use App\Models\Event;
use App\Models\User;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property int|null $uploaded_by
 * @property string|null $title
 * @property string|null $description
 * @property string $file_path
 * @property string $file_name
 * @property string $file_type
 * @property int $file_size
 * @property string $media_type
 * @property string $collection
 * @property bool $is_featured
 * @property string|null $thumbnail_path
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration
 * @property int $sort_order
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Event $event
 * @property-read float|null $aspect_ratio
 * @property-read string|null $dimensions
 * @property-read string|null $duration_for_humans
 * @property-read bool $file_exists
 * @property-read string $file_size_for_humans
 * @property-read string $file_url
 * @property-read string|null $thumbnail_url
 * @property-read User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia banner()
 * @method static \Database\Factories\EventMediaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia gallery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia images()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia videos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereCollection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereMediaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMedia withoutTrashed()
 * @mixin \Eloquent
 */
class EventMedia extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $table = 'event_media';

    protected static function newFactory(): \Database\Factories\EventMediaFactory
    {
        return \Database\Factories\EventMediaFactory::new();
    }

    protected $fillable = [
        'event_id',
        'uploaded_by',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'media_type',
        'collection',
        'is_featured',
        'thumbnail_path',
        'width',
        'height',
        'duration',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_featured' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Constants
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const COLLECTION_BANNER = 'banner';

    public const COLLECTION_GALLERY = 'gallery';

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes

    public function scopeImages($query)
    {
        return $query->where('media_type', self::TYPE_IMAGE);
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', self::TYPE_VIDEO);
    }

    public function scopeBanner($query)
    {
        return $query->where('collection', self::COLLECTION_BANNER);
    }

    public function scopeGallery($query)
    {
        return $query->where('collection', self::COLLECTION_GALLERY);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // Accessors

    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/'.$this->thumbnail_path);
        }

        // For images, use the image itself as thumbnail
        if ($this->isImage()) {
            return $this->file_url;
        }

        return null;
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 1).' GB';
    }

    public function getDurationForHumansAttribute(): ?string
    {
        if (! $this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return $this->width.'x'.$this->height;
        }

        return null;
    }

    public function getAspectRatioAttribute(): ?float
    {
        if ($this->width && $this->height && $this->height > 0) {
            return round($this->width / $this->height, 2);
        }

        return null;
    }

    public function getFileExistsAttribute(): bool
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path);
    }

    // Methods

    public function fileExists(): bool
    {
        return (bool) $this->file_exists;
    }

    public function isImage(): bool
    {
        return $this->media_type === self::TYPE_IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->media_type === self::TYPE_VIDEO;
    }

    public function isBanner(): bool
    {
        return $this->collection === self::COLLECTION_BANNER;
    }

    public function isGallery(): bool
    {
        return $this->collection === self::COLLECTION_GALLERY;
    }

    public function setAsFeatured(): void
    {
        // Remove featured status from other media in the same event
        self::where('event_id', $this->event_id)
            ->where('id', '!=', $this->id)
            ->update(['is_featured' => false]);

        $this->update(['is_featured' => true]);
    }

    public static function getMediaTypeFromMime(string $mimeType): ?string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return self::TYPE_IMAGE;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return self::TYPE_VIDEO;
        }

        return null;
    }

    public static function getCollectionOptions(): array
    {
        return [
            self::COLLECTION_BANNER => 'Banner / Flyer',
            self::COLLECTION_GALLERY => 'Galerie',
        ];
    }

    public static function getMediaTypeOptions(): array
    {
        return [
            self::TYPE_IMAGE => 'Image',
            self::TYPE_VIDEO => 'Vidéo',
        ];
    }

    public static function getAllowedImageTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    public static function getAllowedVideoTypes(): array
    {
        return ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    }

    public static function getAllowedTypes(): array
    {
        return array_merge(self::getAllowedImageTypes(), self::getAllowedVideoTypes());
    }
}
