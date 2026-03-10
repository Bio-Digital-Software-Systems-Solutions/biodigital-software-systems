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
 * @property string $file_path
 * @property string $file_name
 * @property string $file_type
 * @property int $file_size
 * @property string|null $share_token
 * @property \Illuminate\Support\Carbon|null $share_token_expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Event $event
 * @property-read bool $can_preview
 * @property-read bool $file_exists
 * @property-read string $file_size_for_humans
 * @property-read string $file_url
 * @property-read bool $is_image
 * @property-read bool $is_pdf
 * @property-read string|null $share_url
 * @property-read User|null $uploader
 * @method static \Database\Factories\EventProgrammeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereShareToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereShareTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventProgramme withoutTrashed()
 * @mixin \Eloquent
 */
class EventProgramme extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected static function newFactory(): \Database\Factories\EventProgrammeFactory
    {
        return \Database\Factories\EventProgrammeFactory::new();
    }

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'event_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'share_token',
        'share_token_expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'share_token_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Share Token Methods

    /**
     * Generate a new share token valid for the given number of hours.
     */
    public function generateShareToken(int $hours = 24): self
    {
        $this->update([
            'share_token' => bin2hex(random_bytes(32)),
            'share_token_expires_at' => now()->addHours($hours),
        ]);

        return $this;
    }

    /**
     * Renew the existing share token for the given number of hours.
     */
    public function renewShareToken(int $hours = 24): self
    {
        if (! $this->share_token) {
            return $this->generateShareToken($hours);
        }

        $this->update([
            'share_token_expires_at' => now()->addHours($hours),
        ]);

        return $this;
    }

    /**
     * Revoke the share token.
     */
    public function revokeShareToken(): self
    {
        $this->update([
            'share_token' => null,
            'share_token_expires_at' => null,
        ]);

        return $this;
    }

    /**
     * Check if the share token is currently valid.
     */
    public function isShareTokenValid(): bool
    {
        return $this->is_active
            && $this->share_token !== null
            && $this->share_token_expires_at !== null
            && $this->share_token_expires_at->isFuture();
    }

    /**
     * Find an active programme by a valid share token.
     */
    public static function findByValidToken(string $token): ?self
    {
        return self::where('share_token', $token)
            ->where('is_active', true)
            ->where('share_token_expires_at', '>', now())
            ->first();
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

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function getCanPreviewAttribute(): bool
    {
        return $this->is_pdf || $this->is_image;
    }

    public function getShareUrlAttribute(): ?string
    {
        if (! $this->share_token) {
            return null;
        }

        return route('events.programme.shared', $this->share_token);
    }

    public function getFileExistsAttribute(): bool
    {
        return \Storage::disk('public')->exists($this->file_path);
    }
}
