<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_datetime
 * @property \Illuminate\Support\Carbon $end_datetime
 * @property string|null $location
 * @property string $status
 * @property string $type
 * @property string $visibility
 * @property int $user_id
 * @property string|null $appointmentable_type
 * @property int|null $appointmentable_id
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $organizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $participants
 * @property-read int|null $participants_count
 * @property-read Model|\Eloquent $appointmentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $confirmedParticipants
 * @property-read int|null $confirmed_participants_count
 * @property-read bool $can_be_cancelled
 * @property-read bool $can_be_modified
 * @property-read int $duration_minutes
 * @property-read string $formatted_date
 * @property-read string $formatted_time_range
 * @property-read bool $is_future
 * @property-read bool $is_past
 * @property-read bool $is_today
 *
 * @method static Builder<static>|Appointment betweenDates(string $startDate, string $endDate)
 * @method static Builder<static>|Appointment conflictsWith(\Carbon\Carbon $startDateTime, \Carbon\Carbon $endDateTime, ?int $excludeId = null)
 * @method static \Database\Factories\AppointmentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Appointment forDate(string $date)
 * @method static Builder<static>|Appointment forUser(\App\Models\User $user)
 * @method static Builder<static>|Appointment newModelQuery()
 * @method static Builder<static>|Appointment newQuery()
 * @method static Builder<static>|Appointment past()
 * @method static Builder<static>|Appointment private()
 * @method static Builder<static>|Appointment public()
 * @method static Builder<static>|Appointment query()
 * @method static Builder<static>|Appointment today()
 * @method static Builder<static>|Appointment upcoming()
 * @method static Builder<static>|Appointment whereAppointmentableId($value)
 * @method static Builder<static>|Appointment whereAppointmentableType($value)
 * @method static Builder<static>|Appointment whereCreatedAt($value)
 * @method static Builder<static>|Appointment whereDescription($value)
 * @method static Builder<static>|Appointment whereEndDatetime($value)
 * @method static Builder<static>|Appointment whereId($value)
 * @method static Builder<static>|Appointment whereLocation($value)
 * @method static Builder<static>|Appointment whereMetadata($value)
 * @method static Builder<static>|Appointment whereStartDatetime($value)
 * @method static Builder<static>|Appointment whereStatus($value)
 * @method static Builder<static>|Appointment whereTitle($value)
 * @method static Builder<static>|Appointment whereType($value)
 * @method static Builder<static>|Appointment whereUpdatedAt($value)
 * @method static Builder<static>|Appointment whereUserId($value)
 * @method static Builder<static>|Appointment whereUuid($value)
 * @method static Builder<static>|Appointment whereVisibility($value)
 * @method static Builder<static>|Appointment withStatus(string $status)
 * @method static Builder<static>|Appointment withType(string $type)
 *
 * @mixin \Eloquent
 */
class Appointment extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

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
     */
    protected $fillable = [
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_mode',
        'meeting_link',
        'meeting_platform',
        'status',
        'type',
        'visibility',
        'user_id',
        'appointmentable_type',
        'appointmentable_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'duration_minutes',
        'is_past',
        'is_future',
        'is_today',
        'can_be_cancelled',
        'can_be_modified',
        'formatted_date',
        'formatted_time_range',
        'participants_count',
    ];

    /**
     * The organizer of the appointment.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The participants in the appointment.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_user')
            ->withPivot(['status', 'confirmation_token', 'response_message', 'invited_at', 'responded_at', 'notification_sent_at', 'attended'])
            ->withTimestamps();
    }

    /**
     * Get confirmed participants only.
     */
    public function confirmedParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'accepted');
    }

    /**
     * The appointmentable model (polymorphic relationship).
     */
    public function appointmentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resolve route binding with eager loading for User relationships.
     * This prevents N+1 queries when appointment is loaded via route model binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->with(['organizer:id,first_name,last_name,email', 'participants:id,first_name,last_name,email'])
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    /**
     * Scope: Get upcoming appointments.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_datetime', '>', now());
    }

    /**
     * Scope: Get past appointments.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('end_datetime', '<', now());
    }

    /**
     * Scope: Get appointments for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('start_datetime', today());
    }

    /**
     * Scope: Get appointments for a specific date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_datetime', $date);
    }

    /**
     * Scope: Get appointments between dates.
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('start_datetime', [$startDate, $endDate]);
    }

    /**
     * Scope: Get appointments by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get appointments by type.
     */
    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get appointments for a specific user (organizer or participant).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        });
    }

    /**
     * Scope: Check if time slot is available.
     */
    public function scopeConflictsWith(Builder $query, Carbon $startDateTime, Carbon $endDateTime, ?int $excludeId = null): Builder
    {
        $query = $query->where(function ($q) use ($startDateTime, $endDateTime) {
            $q->where('start_datetime', '<', $endDateTime)
                ->where('end_datetime', '>', $startDateTime);
        })->whereNotIn('status', ['cancelled']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    /**
     * Check if the appointment is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->start_datetime->isPast();
    }

    /**
     * Check if the appointment is in the future.
     */
    public function getIsFutureAttribute(): bool
    {
        return $this->start_datetime->isFuture();
    }

    /**
     * Check if the appointment is today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->start_datetime->isToday();
    }

    /**
     * Check if the appointment can be cancelled.
     */
    public function getCanBeCancelledAttribute(): bool
    {
        return ! $this->is_past &&
               ! in_array($this->status, ['cancelled', 'completed']) &&
               $this->start_datetime->isAfter(now()->addHours(1)); // Min 1 hour notice
    }

    /**
     * Check if the appointment can be modified.
     */
    public function getCanBeModifiedAttribute(): bool
    {
        return ! $this->is_past &&
               ! in_array($this->status, ['cancelled', 'completed']) &&
               $this->start_datetime->isAfter(now()->addHours(2)); // Min 2 hours notice
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->start_datetime->format('d/m/Y');
    }

    /**
     * Get formatted time range.
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        return $this->start_datetime->format('H:i').' - '.$this->end_datetime->format('H:i');
    }

    /**
     * Get participants count.
     */
    public function getParticipantsCountAttribute(): int
    {
        // Use preloaded count if available (from withCount), otherwise query
        if (array_key_exists('participants_count', $this->attributes)) {
            return $this->attributes['participants_count'];
        }

        return $this->participants()->count();
    }

    /**
     * Check if the appointment conflicts with another time slot.
     */
    public static function hasConflict(Carbon $startDateTime, Carbon $endDateTime, ?int $excludeId = null): bool
    {
        return static::conflictsWith($startDateTime, $endDateTime, $excludeId)->exists();
    }

    /**
     * Get available time slots for a specific date.
     */
    public static function getAvailableSlots(
        string $date,
        int $durationMinutes = 60,
        string $startHour = '03:00',
        string $endHour = '00:00',
        ?User $organizer = null
    ): array {
        $date = Carbon::parse($date);
        $slots = [];

        $start = $date->copy()->setTimeFromTimeString($startHour);
        $end = $date->copy()->setTimeFromTimeString($endHour);

        // If end time is 00:00, it means midnight of the next day
        if ($endHour === '00:00') {
            $end->addDay();
        }

        $query = static::forDate($date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_datetime');

        if ($organizer) {
            $query->forUser($organizer);
        }

        $existingAppointments = $query->get(['start_datetime', 'end_datetime', 'title', 'visibility']);

        $current = $start->copy();

        // Generate ALL time slots for the day (not just available ones)
        while ($current->addMinutes($durationMinutes)->lte($end)) {
            $slotStart = $current->copy()->subMinutes($durationMinutes);
            $slotEnd = $current->copy();

            $isAvailable = true;
            $conflictingAppointment = null;

            // Check if this slot conflicts with any existing appointment
            foreach ($existingAppointments as $appointment) {
                if ($slotStart->lt($appointment->end_datetime) &&
                    $slotEnd->gt($appointment->start_datetime)) {
                    $isAvailable = false;
                    $conflictingAppointment = $appointment;
                    break;
                }
            }

            // Check if slot is in the past
            $isPast = $slotStart->isPast();

            // Always add the slot to show complete schedule
            if ($isAvailable && ! $isPast) {
                $slots[] = [
                    'start_datetime' => $slotStart->format('Y-m-d\TH:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d\TH:i:s'),
                    'formatted_time' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'available' => true,
                ];
            } else {
                // Show occupied or past slots with appropriate reason
                $reason = 'Occupé';
                if ($isPast) {
                    $reason = 'Passé';
                } elseif ($conflictingAppointment) {
                    if ($conflictingAppointment->visibility === 'public') {
                        $reason = 'Occupé - '.$conflictingAppointment->title;
                    } else {
                        $reason = 'Occupé';
                    }
                }

                $slots[] = [
                    'start_datetime' => $slotStart->format('Y-m-d\TH:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d\TH:i:s'),
                    'formatted_time' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'available' => false,
                    'reason' => $reason,
                ];
            }
        }

        return $slots;
    }

    /**
     * Invite a user to the appointment.
     */
    public function inviteUser(User $user, ?string $notes = null): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => [
                'status' => 'pending',
                'invited_at' => now(),
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * Accept the appointment invitation for a user.
     */
    public function acceptInvitation(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    /**
     * Decline the appointment invitation for a user.
     */
    public function declineInvitation(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'status' => 'declined',
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark user as attended.
     */
    public function markAsAttended(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'attended' => true,
        ]);
    }

    /**
     * Check if user can be modified by the given user.
     */
    public function canBeModifiedBy(User $user): bool
    {
        // Admin or appointment organizer can modify
        return $user->hasPermissionTo('edit appointments') ||
               $this->user_id === $user->id;
    }

    /**
     * Check if appointment can be viewed by the given user.
     */
    public function canBeViewedBy(User $user): bool
    {
        // Can always view if organizer or has permission
        if ($this->user_id === $user->id ||
            $user->hasPermissionTo('view appointments')) {
            return true;
        }

        // Check if user is a participant without loading the collection
        if ($this->participants()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // For public appointments, anyone can view them
        return $this->visibility === 'public';
    }

    /**
     * Scope for public appointments.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for private appointments.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Check if appointment is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Check if appointment is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * Get public available slots for a user on a specific date.
     */
    public static function getPublicAvailableSlots(
        User $user,
        string $date,
        int $durationMinutes = 60,
        string $startHour = '03:00',
        string $endHour = '00:00'
    ): array {
        $date = Carbon::parse($date);
        $slots = [];

        $start = $date->copy()->setTimeFromTimeString($startHour);
        $end = $date->copy()->setTimeFromTimeString($endHour);

        // If end time is 00:00, it means midnight of the next day
        if ($endHour === '00:00') {
            $end->addDay();
        }

        // Get all appointments for this user on this date (both private and public)
        $existingAppointments = static::forDate($date->toDateString())
            ->forUser($user)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_datetime')
            ->get(['start_datetime', 'end_datetime', 'visibility', 'title']);

        $current = $start->copy();

        // Generate ALL time slots for the day (not just available ones)
        while ($current->addMinutes($durationMinutes)->lte($end)) {
            $slotStart = $current->copy()->subMinutes($durationMinutes);
            $slotEnd = $current->copy();

            $isAvailable = true;
            $conflictingAppointment = null;

            // Check if this slot conflicts with any existing appointment
            foreach ($existingAppointments as $appointment) {
                if ($slotStart->lt($appointment->end_datetime) &&
                    $slotEnd->gt($appointment->start_datetime)) {
                    $isAvailable = false;
                    $conflictingAppointment = $appointment;
                    break;
                }
            }

            // Check if slot is in the past
            $isPast = $slotStart->isPast();

            // Always add the slot to show complete schedule
            if ($isAvailable && ! $isPast) {
                $slots[] = [
                    'start_datetime' => $slotStart->format('Y-m-d\TH:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d\TH:i:s'),
                    'formatted_time' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'available' => true,
                ];
            } else {
                // Show occupied or past slots with appropriate reason
                $reason = 'Occupé';
                if ($isPast) {
                    $reason = 'Passé';
                } elseif ($conflictingAppointment) {
                    if ($conflictingAppointment->visibility === 'public') {
                        $reason = 'Occupé - '.$conflictingAppointment->title;
                    } else {
                        $reason = 'Occupé';
                    }
                }

                $slots[] = [
                    'start_datetime' => $slotStart->format('Y-m-d\TH:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d\TH:i:s'),
                    'formatted_time' => $slotStart->format('H:i').' - '.$slotEnd->format('H:i'),
                    'available' => false,
                    'reason' => $reason,
                ];
            }
        }

        return $slots;
    }

    /**
     * Get user's schedule visibility for a specific date.
     */
    public static function getUserSchedule(User $user, string $date): array
    {
        $date = Carbon::parse($date);

        $appointments = static::forDate($date->toDateString())
            ->forUser($user)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_datetime')
            ->get(['id', 'start_datetime', 'end_datetime', 'title', 'type', 'status', 'visibility']);

        return $appointments->map(function ($appointment) {
            return [
                'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                'title' => $appointment->title,
                'type' => $appointment->type,
                'formatted_time' => $appointment->formatted_time_range,
                'status' => $appointment->status,
                'visibility' => $appointment->visibility,
            ];
        })->toArray();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get department meetings that use this appointment.
     */
    public function departmentMeetings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DepartmentMeeting::class);
    }

    /**
     * Get departments that have this appointment as a meeting.
     */
    public function meetingDepartments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_meetings')
            ->withPivot(['uuid', 'created_by', 'notify_all_members', 'is_mandatory', 'notes', 'notified_at'])
            ->withTimestamps();
    }
}
