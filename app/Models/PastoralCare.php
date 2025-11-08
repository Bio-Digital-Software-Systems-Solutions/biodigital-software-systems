<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PastoralCare extends Model
{
    use HasFactory, SoftDeletes, ClearsCache;

    protected $fillable = [
        'uuid',
        'user_id',
        'pastor_id',
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
        if (!$this->can_be_confirmed) {
            throw new \Exception('This appointment cannot be confirmed.');
        }

        $this->update([
            'status' => 'confirmed',
            'confirmation_sent_at' => now(),
        ]);

        return $this;
    }

    public function cancel($reason = null)
    {
        if (!$this->can_be_cancelled) {
            throw new \Exception('This appointment cannot be cancelled.');
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
        if ($this->status !== 'confirmed' || !$this->is_past) {
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

        $endTime = Carbon::parse($appointmentTime)->addMinutes($durationMinutes);

        $query = static::where('pastor_id', $pastorId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($appointmentTime, $endTime) {
                $q->where(function ($subQ) use ($appointmentTime, $endTime) {
                    // Check if new appointment starts during existing appointment
                    $subQ->where('appointment_time', '<=', $appointmentTime)
                         ->whereRaw('DATE_ADD(appointment_time, INTERVAL duration_minutes MINUTE) > ?', [$appointmentTime]);
                })->orWhere(function ($subQ) use ($appointmentTime, $endTime) {
                    // Check if new appointment ends during existing appointment
                    $subQ->where('appointment_time', '<', $endTime)
                         ->whereRaw('DATE_ADD(appointment_time, INTERVAL duration_minutes MINUTE) >= ?', [$endTime]);
                })->orWhere(function ($subQ) use ($appointmentTime, $endTime) {
                    // Check if existing appointment is completely within new appointment
                    $subQ->where('appointment_time', '>=', $appointmentTime)
                         ->whereRaw('DATE_ADD(appointment_time, INTERVAL duration_minutes MINUTE) <= ?', [$endTime]);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() === 0;
    }

    public static function getAvailableTimeSlots($pastorId, $date, $durationMinutes = 60)
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;

        // Define working hours (9 AM to 5 PM)
        $startHour = 9;
        $endHour = 17;
        $timeSlots = [];

        $currentDate = Carbon::parse($date);

        // Generate all possible time slots
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) { // 30-minute intervals
                $timeSlot = $currentDate->copy()->setTime($hour, $minute);

                // Skip if the time slot would end after working hours
                if ($timeSlot->copy()->addMinutes($durationMinutes)->hour >= $endHour) {
                    continue;
                }

                // Skip if in the past
                if ($timeSlot <= now()) {
                    continue;
                }

                if (static::isTimeSlotAvailable($pastorId, $timeSlot, $durationMinutes)) {
                    $timeSlots[] = $timeSlot->format('H:i');
                }
            }
        }

        return $timeSlots;
    }

    // Route key name for UUID-based routing
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
