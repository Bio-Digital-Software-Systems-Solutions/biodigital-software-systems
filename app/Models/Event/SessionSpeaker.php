<?php

namespace App\Models\Event;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $session_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $title
 * @property string|null $company
 * @property string|null $bio
 * @property string|null $photo
 * @property string $role
 * @property array<array-key, mixed>|null $social_links
 * @property int $sort_order
 * @property bool $is_confirmed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string|null $display_email
 * @property-read string $display_name
 * @property-read string $full_title
 * @property-read string|null $photo_url
 * @property-read \App\Models\Event\EventSession $session
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker byRole(string $role)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereIsConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereSocialLinks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionSpeaker whereUuid($value)
 * @mixin \Eloquent
 */
class SessionSpeaker extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'session_id',
        'user_id',
        'name',
        'email',
        'title',
        'company',
        'bio',
        'photo',
        'role',
        'social_links',
        'sort_order',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'sort_order' => 'integer',
            'is_confirmed' => 'boolean',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Accessors

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return (string) $this->user->name;
        }

        return (string) $this->name;
    }

    public function getDisplayEmailAttribute(): ?string
    {
        if ($this->user) {
            return (string) $this->user->email;
        }

        return $this->email;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/'.$this->photo);
        }

        if ($this->user && $this->user->avatar) {
            return asset('storage/'.$this->user->avatar);
        }

        return null;
    }

    public function getFullTitleAttribute(): string
    {
        $parts = [];

        if ($this->title) {
            $parts[] = $this->title;
        }

        if ($this->company) {
            $parts[] = $this->company;
        }

        return implode(' - ', $parts);
    }

    // Methods

    public function isSpeaker(): bool
    {
        return $this->role === 'speaker';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isPanelist(): bool
    {
        return $this->role === 'panelist';
    }

    public function getSocialLink(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
    }
}
