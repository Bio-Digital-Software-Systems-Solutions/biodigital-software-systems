<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property-read bool $can_be_confirmed
 * @property-read bool $can_be_cancelled
 * @property-read string $formatted_appointment_time
 * @property-read string $formatted_appointment_date
 * @property-read \Carbon\Carbon $appointment_end_time
 * @property-read bool $is_upcoming
 * @property-read bool $is_past
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property int $pastor_id
 * @property \Illuminate\Support\Carbon $appointment_date
 * @property \Illuminate\Support\Carbon $appointment_time
 * @property int $duration_minutes
 * @property string $status
 * @property string $location_type
 * @property string|null $zoom_link
 * @property string|null $client_name
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string|null $notes
 * @property string|null $pastor_notes
 * @property \Illuminate\Support\Carbon|null $confirmation_sent_at
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $pastor
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare confirmed()
 * @method static \Database\Factories\PastoralCareFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare forPastor($pastorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare onDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereAppointmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereAppointmentTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereConfirmationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare wherePastorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare wherePastorNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereZoomLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PastoralCare extends Model
{
    use ClearsCache, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'pastor_id',
        'parent_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'location_type',
        'zoom_link',
        'client_name',
        'client_email',
        'client_phone',
        'notes',
        'pastor_notes',
        'confirmation_sent_at',
        'reminder_sent_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'duration_minutes' => 'integer',
        'confirmation_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $dates = [
        'appointment_date',
        'appointment_time',
        'confirmation_sent_at',
        'reminder_sent_at',
        'cancelled_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'can_be_confirmed',
        'can_be_cancelled',
    ];

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pastor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pastor_id');
    }

    /**
     * Get the parent appointment (if this is a follow-up)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PastoralCare::class, 'parent_id');
    }

    /**
     * Get the follow-up appointments for this appointment
     */
    public function followUps()
    {
        return $this->hasMany(PastoralCare::class, 'parent_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeForPastor($query, $pastorId)
    {
        return $query->where('pastor_id', $pastorId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->where('appointment_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedAppointmentTimeAttribute()
    {
        return $this->appointment_time->format('H:i');
    }

    public function getFormattedAppointmentDateAttribute()
    {
        return $this->appointment_date->format('d/m/Y');
    }

    public function getAppointmentEndTimeAttribute()
    {
        return $this->appointment_time->addMinutes($this->duration_minutes);
    }

    public function getIsUpcomingAttribute()
    {
        return $this->appointment_date >= now()->toDateString() &&
            in_array($this->status, ['pending', 'confirmed']);
    }

    public function getIsPastAttribute()
    {
        return $this->appointment_date < now()->toDateString() ||
            ($this->appointment_date == now()->toDateString() && $this->appointment_time < now());
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
            $this->appointment_time > now()->addHours(24);
    }

    public function getCanBeConfirmedAttribute()
    {
        return $this->status === 'pending' && $this->appointment_time > now();
    }

    // Business Logic Methods
    public function confirm()
    {
        if (! $this->can_be_confirmed) {
            // Déterminer la raison spécifique de l'impossibilité de confirmer
            if ($this->status !== 'pending') {
                throw new \Exception('Seuls les rendez-vous en attente peuvent être confirmés (statut actuel: '.$this->status.').');
            }

            if ($this->appointment_time <= now()) {
                throw new \Exception('Ce rendez-vous est déjà passé et ne peut plus être confirmé.');
            }

            throw new \Exception('Ce rendez-vous ne peut pas être confirmé.');
        }

        $this->update([
            'status' => 'confirmed',
            'confirmation_sent_at' => now(),
        ]);

        return $this;
    }

    public function cancel($reason = null)
    {
        if (! $this->can_be_cancelled) {
            // Déterminer la raison spécifique de l'impossibilité d'annuler
            if (! in_array($this->status, ['pending', 'confirmed'])) {
                throw new \Exception('Ce rendez-vous ne peut plus être annulé (statut: '.$this->status.').');
            }

            if ($this->appointment_time <= now()->addHours(24)) {
                throw new \Exception('Délai d\'annulation dépassé (24h).');
            }

            throw new \Exception('Ce rendez-vous ne peut pas être annulé.');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $this;
    }

    public function complete()
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Only confirmed appointments can be marked as completed.');
        }

        $this->update(['status' => 'completed']);

        return $this;
    }

    public function markAsNoShow()
    {
        if ($this->status !== 'confirmed' || ! $this->is_past) {
            throw new \Exception('Only past confirmed appointments can be marked as no-show.');
        }

        $this->update(['status' => 'no_show']);

        return $this;
    }

    public function sendReminderEmail()
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        // This will be implemented when we create the Mail classes
        // Mail::to($this->client_email)->send(new AppointmentReminderMail($this));

        $this->update(['reminder_sent_at' => now()]);

        return true;
    }

    // Static methods for availability checking
    public static function isTimeSlotAvailable($pastorId, $appointmentTime, $durationMinutes = 60, $excludeId = null)
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;

        $appointmentStart = Carbon::parse($appointmentTime);
        $appointmentEnd = $appointmentStart->copy()->addMinutes($durationMinutes);

        // Get existing appointments for the pastor on the same day
        $existingAppointments = static::where('pastor_id', $pastorId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_time', $appointmentStart->toDateString())
            ->when($excludeId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->select(['appointment_time', 'duration_minutes'])
            ->get();

        // Check for conflicts with existing appointments
        foreach ($existingAppointments as $existing) {
            $existingStart = Carbon::parse($existing->appointment_time);
            $existingEnd = $existingStart->copy()->addMinutes($existing->duration_minutes);

            // Check for any overlap between the time slots
            if ($appointmentStart->lt($existingEnd) && $appointmentEnd->gt($existingStart)) {
                return false; // Conflict found
            }
        }

        return true; // No conflicts found
    }

    public static function getAvailableTimeSlots($pastorId, $date, $durationMinutes = 60)
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;
        $timeSlots = [];
        $currentDate = Carbon::parse($date);

        // Get pastor's availability for this date
        $availabilities = \App\Models\PastorAvailability::where('pastor_id', $pastorId)
            ->active()
            ->where(function ($query) use ($currentDate) {
                // Check for weekly recurring availability
                $query->where(function ($q) use ($currentDate) {
                    // Use Carbon dayOfWeek format directly (0=Sunday, 1=Monday, etc.)
                    $dayOfWeek = $currentDate->dayOfWeek;
                    $q->where('type', 'weekly')
                        ->where('day_of_week', $dayOfWeek);
                })
                    // Or specific date availability
                    ->orWhere(function ($q) use ($currentDate) {
                        $q->where('type', 'specific_date')
                            ->where('specific_date', $currentDate->toDateString());
                    });
            })
            ->get();

        // If no availability defined, return empty array (no slots available)
        if ($availabilities->isEmpty()) {
            return [];
        }

        // Generate time slots for each availability period
        foreach ($availabilities as $availability) {
            $slots = $availability->getTimeSlotsForDate($currentDate);

            foreach ($slots as $slot) {
                $timeSlot = $currentDate->copy()->setTimeFromTimeString($slot);

                // Skip if in the past
                if ($timeSlot <= now()) {
                    continue;
                }

                // Check if this specific time slot is available (not booked)
                if (static::isTimeSlotAvailable($pastorId, $timeSlot, $durationMinutes)) {
                    $timeSlots[] = $slot;
                }
            }
        }

        // Remove duplicates and sort
        $timeSlots = array_unique($timeSlots);
        sort($timeSlots);

        return $timeSlots;
    }

    // Route key name for UUID-based routing
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
