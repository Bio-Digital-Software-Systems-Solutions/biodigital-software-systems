<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $appointment_id
 * @property int $created_by
 * @property bool $notify_all_members
 * @property bool $is_mandatory
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Appointment $appointment
 * @property-read \App\Models\User $creator
 */
class DepartmentMeeting extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'department_id',
        'appointment_id',
        'created_by',
        'notify_all_members',
        'is_mandatory',
        'notes',
        'notified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_all_members' => 'boolean',
            'is_mandatory' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the department that owns the meeting.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the appointment associated with the meeting.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user who created the meeting.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the meeting has been notified.
     */
    public function hasBeenNotified(): bool
    {
        return $this->notified_at !== null;
    }

    /**
     * Mark the meeting as notified.
     */
    public function markAsNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    /**
     * Get members to notify for this meeting.
     * If notify_all_members is true and no participants specified, notify all department members.
     * Otherwise, notify only the specified participants.
     */
    public function getMembersToNotify()
    {
        // If notify_all_members is true and no participants on appointment, notify all department members
        if ($this->notify_all_members && $this->appointment->participants()->count() === 0) {
            return $this->department->users;
        }

        // Otherwise, return the appointment participants
        return $this->appointment->participants;
    }
}
