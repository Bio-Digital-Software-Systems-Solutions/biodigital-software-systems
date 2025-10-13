<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $location
 * @property int|null $max_participants
 * @property bool $is_public
 * @property string $status
 * @property int|null $address_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $color
 * @property-read \App\Models\Address|null $address
 * @property-read \App\Models\User $creator
 * @property-read int $available_spots
 * @property-read float $duration_in_hours
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $participants
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event past()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event registrationOpen()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereMaxParticipants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUserId($value)
 * @mixin \Eloquent
 */
class Event extends Model
{
    use HasFactory, HasUuid, LogsActivity;

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
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'max_participants',
        'registration_deadline',
        'is_public',
        'status',
        'color',
        'user_id',
        'address_id',
        'images',
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_deadline' => 'datetime',
            'is_public' => 'boolean',
            'max_participants' => 'integer',
            'images' => 'array',
        ];
    }

    /**
     * Get the user who created the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created the event (alias).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the address of the event.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * The users that are participating in the event.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot('registered_at', 'attended')
            ->withTimestamps();
    }

    /**
     * Get the number of participants.
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Get the number of available spots.
     */
    public function getAvailableSpotsAttribute(): int
    {
        return $this->max_participants - $this->participants_count;
    }

    /**
     * Check if the event is full.
     */
    public function isFull(): bool
    {
        if (! $this->max_participants) {
            return false;
        }

        return $this->participants()->count() >= $this->max_participants;
    }

    /**
     * Check if a participant can be added.
     */
    public function canAddParticipant(): bool
    {
        return ! $this->isFull();
    }

    /**
     * Check if registration is still open.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->registration_deadline === null ||
               now()->isBefore($this->registration_deadline);
    }

    /**
     * Check if the event has started.
     */
    public function hasStarted(): bool
    {
        return now()->isAfter($this->start_date);
    }

    /**
     * Check if the event has ended.
     */
    public function hasEnded(): bool
    {
        return now()->isAfter($this->end_date);
    }

    /**
     * Get the event duration in hours.
     */
    public function getDurationInHoursAttribute(): float
    {
        return $this->start_date->diffInHours($this->end_date);
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope a query to only include past events.
     */
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope a query to only include public events.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include events with open registration.
     */
    public function scopeRegistrationOpen($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('registration_deadline')
                ->orWhere('registration_deadline', '>', now());
        });
    }

    /**
     * Generate a random color for the event.
     */
    public static function generateRandomColor(): string
    {
        $colors = [
            '#ef4444', // red
            '#f97316', // orange
            '#f59e0b', // amber
            '#eab308', // yellow
            '#84cc16', // lime
            '#22c55e', // green
            '#10b981', // emerald
            '#14b8a6', // teal
            '#06b6d4', // cyan
            '#0ea5e9', // sky
            '#3b82f6', // blue
            '#6366f1', // indigo
            '#8b5cf6', // violet
            '#a855f7', // purple
            '#d946ef', // fuchsia
            '#ec4899', // pink
            '#f43f5e', // rose
        ];

        return $colors[array_rand($colors)];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (! $event->color) {
                $event->color = self::generateRandomColor();
            }
        });
    }
}
