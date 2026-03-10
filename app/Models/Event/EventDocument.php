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
 * @property int|null $session_id
 * @property int|null $uploaded_by
 * @property string $title
 * @property string|null $description
 * @property string $file_path
 * @property string $file_name
 * @property string $file_type
 * @property int $file_size
 * @property string $document_type
 * @property string $visibility
 * @property int $download_count
 * @property bool $is_downloadable
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Event $event
 * @property-read bool $can_preview
 * @property-read string $file_size_for_humans
 * @property-read string $file_url
 * @property-read string $icon
 * @property-read bool $is_image
 * @property-read bool $is_pdf
 * @property-read bool $is_video
 * @property-read \App\Models\Event\EventSession|null $session
 * @property-read User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument downloadable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument forRegistered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereDownloadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereIsDownloadable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventDocument withoutTrashed()
 * @mixin \Eloquent
 */
class EventDocument extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'event_id',
        'session_id',
        'uploaded_by',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'document_type',
        'visibility',
        'download_count',
        'is_downloadable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_count' => 'integer',
            'is_downloadable' => 'boolean',
            'sort_order' => 'integer',
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
    public const TYPE_GENERAL = 'general';

    public const TYPE_SCHEDULE = 'schedule';

    public const TYPE_BROCHURE = 'brochure';

    public const TYPE_PRESENTATION = 'presentation';

    public const TYPE_HANDOUT = 'handout';

    public const TYPE_CERTIFICATE = 'certificate';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_REGISTERED = 'registered';

    public const VISIBILITY_PRIVATE = 'private';

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeForRegistered($query)
    {
        return $query->whereIn('visibility', [self::VISIBILITY_PUBLIC, self::VISIBILITY_REGISTERED]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeDownloadable($query)
    {
        return $query->where('is_downloadable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // Accessors

    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
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

    public function getIconAttribute(): string
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'document-text',
            'doc', 'docx' => 'document',
            'xls', 'xlsx' => 'table-cells',
            'ppt', 'pptx' => 'presentation-chart-bar',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'photo',
            'mp4', 'mov', 'avi' => 'video-camera',
            'mp3', 'wav' => 'musical-note',
            'zip', 'rar', '7z' => 'archive-box',
            default => 'document',
        };
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->file_type, 'video/');
    }

    public function getCanPreviewAttribute(): bool
    {
        return $this->is_image || $this->is_pdf || $this->is_video;
    }

    // Methods

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function isVisibleTo(?User $user = null, ?EventRegistration $registration = null): bool
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        if ($this->visibility === self::VISIBILITY_REGISTERED) {
            return $registration instanceof \App\Models\Event\EventRegistration || ($user && $this->event->participants->contains($user));
        }
        // Private - only event organizers
        return $user && ($user->hasRole('super-admin') || $this->event->user_id === $user->id);
    }

    // Static methods

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_GENERAL => 'Général',
            self::TYPE_SCHEDULE => 'Programme',
            self::TYPE_BROCHURE => 'Brochure',
            self::TYPE_PRESENTATION => 'Présentation',
            self::TYPE_HANDOUT => 'Document de référence',
            self::TYPE_CERTIFICATE => 'Certificat',
        ];
    }

    public static function getVisibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_REGISTERED => 'Participants inscrits',
            self::VISIBILITY_PRIVATE => 'Privé (organisateurs)',
        ];
    }
}
