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
            return $registration !== null || ($user && $this->event->participants->contains($user));
        }

        // Private - only event organizers
        if ($user && ($user->hasRole('super-admin') || $this->event->user_id === $user->id)) {
            return true;
        }

        return false;
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
