<?php

namespace App\Models;

use App\Enums\Event\EventStatus;
use App\Enums\Event\EventType;
use App\Enums\Event\EventVisibility;
use App\Models\Event\EventCategory;
use App\Models\Event\EventDocument;
use App\Models\Event\EventFeedback;
use App\Models\Event\EventMedia;
use App\Models\Event\EventNotification;
use App\Models\Event\EventProgramme;
use App\Models\Event\EventPromoCode;
use App\Models\Event\EventRegistration;
use App\Models\Event\EventSession;
use App\Models\Event\EventSponsor;
use App\Models\Event\EventTicket;
use App\Models\Event\EventWaitlist;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property EventType $type
 * @property string|null $description
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $timezone
 * @property \Illuminate\Support\Carbon|null $registration_deadline
 * @property \Illuminate\Support\Carbon|null $early_bird_deadline
 * @property string|null $location
 * @property string|null $streaming_url
 * @property string|null $streaming_platform
 * @property int|null $max_participants
 * @property int|null $waitlist_capacity
 * @property bool $waitlist_enabled
 * @property bool $requires_approval
 * @property bool $is_public
 * @property EventVisibility $visibility
 * @property EventStatus $status
 * @property \Illuminate\Database\Eloquent\Collection<int, EventMedia> $images
 * @property array<array-key, mixed>|null $settings
 * @property array<array-key, mixed>|null $metadata
 * @property string $color
 * @property int|null $address_id
 * @property int $user_id
 * @property int|null $category_id
 * @property int|null $department_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Address|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMedia> $banners
 * @property-read int|null $banners_count
 * @property-read EventCategory|null $category
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventFeedback> $feedback
 * @property-read int|null $feedback_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMedia> $galleryMedia
 * @property-read int|null $gallery_media_count
 * @property-read int $available_spots
 * @property-read int $checked_in_count
 * @property-read float $duration_in_hours
 * @property-read bool $has_early_bird
 * @property-read bool $is_hybrid
 * @property-read bool $is_virtual
 * @property-read int|null $participants_count
 * @property-read int|null $registrations_count
 * @property-read float $total_revenue
 * @property-read int|null $waitlist_count
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMedia> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $participants
 * @property-read EventProgramme|null $programme
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventPromoCode> $promoCodes
 * @property-read int|null $promo_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventRegistration> $registrations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventSession> $sessions
 * @property-read int|null $sessions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventSponsor> $sponsors
 * @property-read int|null $sponsors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventTicket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMedia> $videos
 * @property-read int|null $videos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventWaitlist> $waitlist
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event inCategory(int $categoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event inDepartment(int $departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event ofType(\App\Enums\Event\EventType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event ongoing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event past()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event registrationOpen()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event search(string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEarlyBirdDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereMaxParticipants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereRegistrationDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStreamingPlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStreamingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereWaitlistCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereWaitlistEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withVisibility(\App\Enums\Event\EventVisibility $visibility)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 * @mixin \Eloquent
 */
class Event extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

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
        // New fields
        'type',
        'visibility',
        'early_bird_deadline',
        'waitlist_capacity',
        'waitlist_enabled',
        'requires_approval',
        'timezone',
        'streaming_url',
        'streaming_platform',
        'settings',
        'metadata',
        'category_id',
        'department_id',
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
            'early_bird_deadline' => 'datetime',
            'is_public' => 'boolean',
            'waitlist_enabled' => 'boolean',
            'requires_approval' => 'boolean',
            'max_participants' => 'integer',
            'waitlist_capacity' => 'integer',
            'images' => 'array',
            'settings' => 'array',
            'metadata' => 'array',
            'status' => EventStatus::class,
            'type' => EventType::class,
            'visibility' => EventVisibility::class,
        ];
    }

    // ==================
    // Existing Relationships
    // ==================

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
     * The users that are participating in the event (legacy relationship).
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot('registered_at', 'attended', 'registration_id', 'role', 'notes')
            ->withTimestamps();
    }

    // ==================
    // New Relationships
    // ==================

    /**
     * Get the category of the event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * Get the department associated with the event.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the sessions for the event.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    /**
     * Get the programme for the event.
     */
    public function programme(): HasOne
    {
        return $this->hasOne(EventProgramme::class);
    }

    /**
     * Get the tickets for the event.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    /**
     * Get the registrations for the event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get the promo codes for the event.
     */
    public function promoCodes(): HasMany
    {
        return $this->hasMany(EventPromoCode::class);
    }

    /**
     * Get the sponsors for the event.
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(EventSponsor::class);
    }

    /**
     * Get the documents for the event.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EventDocument::class);
    }

    /**
     * Get the notifications for the event.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(EventNotification::class);
    }

    /**
     * Get the waitlist entries for the event.
     */
    public function waitlist(): HasMany
    {
        return $this->hasMany(EventWaitlist::class);
    }

    /**
     * Get all feedback for the event.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(EventFeedback::class);
    }

    /**
     * Get all media for the event.
     */
    public function media(): HasMany
    {
        return $this->hasMany(EventMedia::class);
    }

    /**
     * Get all images for the event.
     */
    public function images(): HasMany
    {
        return $this->hasMany(EventMedia::class)->images()->ordered();
    }

    /**
     * Get all videos for the event.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(EventMedia::class)->videos()->ordered();
    }

    /**
     * Get banner/flyer images for the event.
     */
    public function banners(): HasMany
    {
        return $this->hasMany(EventMedia::class)->banner()->ordered();
    }

    /**
     * Get gallery media for the event.
     */
    public function galleryMedia(): HasMany
    {
        return $this->hasMany(EventMedia::class)->gallery()->ordered();
    }

    // ==================
    // Existing Accessors
    // ==================

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
        if ($this->max_participants === null) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_participants - $this->participants_count);
    }

    /**
     * Get the event duration in hours.
     */
    public function getDurationInHoursAttribute(): float
    {
        return $this->start_date->diffInHours($this->end_date);
    }

    // ==================
    // New Accessors
    // ==================

    /**
     * Get the total number of registrations (confirmed).
     */
    public function getRegistrationsCountAttribute(): int
    {
        return (int) $this->registrations()->confirmed()->count();
    }

    /**
     * Get the total number of checked-in attendees.
     */
    public function getCheckedInCountAttribute(): int
    {
        return (int) $this->registrations()->checkedIn()->count();
    }

    /**
     * Get the waitlist count.
     */
    public function getWaitlistCountAttribute(): int
    {
        return (int) $this->waitlist()->waiting()->count();
    }

    /**
     * Get the total revenue from registrations.
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->registrations()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->sum('total_amount');
    }

    /**
     * Check if the event is virtual.
     */
    public function getIsVirtualAttribute(): bool
    {
        return $this->type === EventType::WEBINAR;
    }

    /**
     * Check if the event is hybrid.
     */
    public function getIsHybridAttribute(): bool
    {
        return $this->type === EventType::HYBRID;
    }

    /**
     * Check if the event has early bird pricing.
     */
    public function getHasEarlyBirdAttribute(): bool
    {
        return $this->early_bird_deadline !== null && now()->isBefore($this->early_bird_deadline);
    }

    // ==================
    // Existing Methods
    // ==================

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
     * Check if the event is in the past (has ended).
     */
    public function isPast(): bool
    {
        return $this->hasEnded();
    }

    /**
     * Check if the event can be modified.
     * Past events can only be modified by super-admin.
     */
    public function canBeModifiedBy(?User $user = null): bool
    {
        if (!$user instanceof \App\Models\User) {
            return false;
        }

        // super-admin can modify any event, even past ones
        if ($user->hasRole('super-admin')) {
            return true;
        }
        // Past events cannot be modified by non-super-admin users
        return !$this->isPast();
    }

    /**
     * Check if users can participate in this event.
     * Past events cannot accept new participants or allow leaving.
     */
    public function canAcceptParticipationChanges(?User $user = null): bool
    {
        // super-admin can always manage participation
        if ($user && $user->hasRole('super-admin')) {
            return true;
        }
        // Past events don't allow participation changes
        return !$this->isPast();
    }

    // ==================
    // New Methods
    // ==================

    /**
     * Check if the event is ongoing.
     */
    public function isOngoing(): bool
    {
        return $this->hasStarted() && ! $this->hasEnded();
    }

    /**
     * Check if waitlist is available.
     */
    public function hasWaitlistAvailable(): bool
    {
        if (! $this->waitlist_enabled) {
            return false;
        }

        if ($this->waitlist_capacity === null) {
            return true;
        }

        return $this->waitlist_count < $this->waitlist_capacity;
    }

    /**
     * Check if the event accepts registrations.
     */
    public function canAcceptRegistrations(): bool
    {
        if (! $this->isRegistrationOpen()) {
            return false;
        }
        return !$this->hasStarted();
    }

    /**
     * Get the attendance rate.
     */
    public function getAttendanceRate(): float
    {
        $confirmed = $this->registrations_count;

        if ($confirmed === 0) {
            return 0;
        }

        return round(($this->checked_in_count / $confirmed) * 100, 1);
    }

    /**
     * Get the average feedback rating.
     */
    public function getAverageFeedbackRating(): ?float
    {
        $avg = $this->feedback()->whereNotNull('overall_rating')->avg('overall_rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Duplicate the event.
     */
    public function duplicate(?array $overrides = []): self
    {
        $newEvent = $this->replicate([
            'uuid',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        foreach ($overrides as $key => $value) {
            $newEvent->{$key} = $value;
        }

        $newEvent->save();

        // Optionally duplicate related data
        // This can be extended based on requirements

        return $newEvent;
    }

    // ==================
    // Existing Scopes
    // ==================

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
        return $query->where(function ($q): void {
            $q->whereNull('registration_deadline')
                ->orWhere('registration_deadline', '>', now());
        });
    }

    // ==================
    // New Scopes
    // ==================

    /**
     * Scope a query to only include ongoing events.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>', now());
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, EventType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by visibility.
     */
    public function scopeWithVisibility($query, EventVisibility $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to search events.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search): void {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // ==================
    // Static Methods
    // ==================

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

        static::creating(function ($event): void {
            if (! $event->color) {
                $event->color = self::generateRandomColor();
            }

            // Set default type if not set
            if (! $event->type) {
                $event->type = EventType::OTHER;
            }

            // Set default visibility if not set
            if (! $event->visibility) {
                $event->visibility = $event->is_public ? EventVisibility::PUBLIC : EventVisibility::PRIVATE;
            }
        });
    }
}
