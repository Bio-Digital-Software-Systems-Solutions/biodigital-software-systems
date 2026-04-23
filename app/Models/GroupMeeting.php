<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GroupMeeting extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'group_id',
        'appointment_id',
        'created_by',
        'notify_all_members',
        'is_mandatory',
        'notes',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notify_all_members' => 'boolean',
            'is_mandatory' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasBeenNotified(): bool
    {
        return $this->notified_at !== null;
    }

    public function markAsNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    public function getMembersToNotify()
    {
        if ($this->notify_all_members && $this->appointment->participants()->count() === 0) {
            return $this->group->users;
        }

        return $this->appointment->participants;
    }
}
