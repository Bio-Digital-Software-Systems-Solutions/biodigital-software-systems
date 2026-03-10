<?php

namespace App\Models\Event;

use App\Enums\Event\SessionFormat;
use App\Models\Event;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property int|null $room_id
 * @property string $title
 * @property string|null $description
 * @property SessionFormat $format
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property int|null $capacity
 * @property string|null $streaming_url
 * @property string|null $recording_url
 * @property array<array-key, mixed>|null $resources
 * @property array<array-key, mixed>|null $metadata
 * @property bool $is_mandatory
 * @property bool $requires_registration
 * @property string $status
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventRegistration> $attendees
 * @property-read int $attendees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventCheckin> $checkins
 * @property-read int|null $checkins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventDocument> $documents
 * @property-read int|null $documents_count
 * @property-read Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventFeedback> $feedback
 * @property-read int|null $feedback_count
 * @property-read int|null $available_spots
 * @property-read string $duration_for_humans
 * @property-read int $duration_in_minutes
 * @property-read string $speakers_names
 * @property-read \App\Models\Event\VenueRoom|null $room
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\SessionSpeaker> $speakers
 * @property-read int|null $speakers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession ongoing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession past()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereIsMandatory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereRecordingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereRequiresRegistration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereResources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereStreamingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventSession withoutTrashed()
 * @mixin \Eloquent
 */
class EventSession extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'event_id',
        'room_id',
        'title',
        'description',
        'format',
        'start_time',
        'end_time',
        'capacity',
        'streaming_url',
        'recording_url',
        'resources',
        'metadata',
        'is_mandatory',
        'requires_registration',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'format' => SessionFormat::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'capacity' => 'integer',
            'resources' => 'array',
            'metadata' => 'array',
            'is_mandatory' => 'boolean',
            'requires_registration' => 'boolean',
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

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(VenueRoom::class, 'room_id');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(SessionSpeaker::class, 'session_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(EventRegistration::class, 'session_attendees', 'session_id', 'registration_id')
            ->withPivot('status', 'registered_at', 'attended_at')
            ->withTimestamps();
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(EventCheckin::class, 'session_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(EventFeedback::class, 'session_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EventDocument::class, 'session_id');
    }

    // Scopes

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_time', '<=', now())
            ->where('end_time', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_time')->orderBy('sort_order');
    }

    // Accessors

    public function getDurationInMinutesAttribute(): int
    {
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }

    public function getDurationForHumansAttribute(): string
    {
        $minutes = $this->duration_in_minutes;

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}min";
    }

    public function getAttendeesCountAttribute(): int
    {
        return $this->attendees()->count();
    }

    public function getAvailableSpotsAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->attendees_count);
    }

    public function getSpeakersNamesAttribute(): string
    {
        return $this->speakers->pluck('name')->implode(', ');
    }

    // Methods

    public function hasStarted(): bool
    {
        return now()->isAfter($this->start_time);
    }

    public function hasEnded(): bool
    {
        return now()->isAfter($this->end_time);
    }

    public function isOngoing(): bool
    {
        return $this->hasStarted() && ! $this->hasEnded();
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->attendees_count >= $this->capacity;
    }

    public function canRegister(): bool
    {
        return ! $this->hasStarted() && ! $this->isFull() && $this->status === 'scheduled';
    }

    public function isVirtual(): bool
    {
        return $this->format === SessionFormat::VIRTUAL;
    }

    public function isHybrid(): bool
    {
        return $this->format === SessionFormat::HYBRID;
    }

    public function requiresVenue(): bool
    {
        return (bool) $this->format->requiresVenue();
    }

    public function getAverageRating(): ?float
    {
        $avg = $this->feedback()->whereNotNull('overall_rating')->avg('overall_rating');

        return $avg ? round($avg, 1) : null;
    }

    public function getAttendanceRate(): float
    {
        $registered = $this->attendees()->count();

        if ($registered === 0) {
            return 0;
        }

        $attended = $this->attendees()->wherePivot('attended_at', '!=')->count();

        return round(($attended / $registered) * 100, 1);
    }
}
