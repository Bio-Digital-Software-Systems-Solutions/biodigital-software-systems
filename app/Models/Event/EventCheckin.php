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
 * @property int $registration_id
 * @property int|null $session_id
 * @property int|null $checked_in_by
 * @property string $check_type
 * @property string $method
 * @property string|null $device_id
 * @property string|null $location
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $checked_in_at
 * @property \Illuminate\Support\Carbon|null $checked_out_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User|null $checkedInByUser
 * @property-read int|null $duration
 * @property-read string|null $duration_for_humans
 * @property-read bool $is_entry
 * @property-read bool $is_exit
 * @property-read bool $is_session
 * @property-read \App\Models\Event\EventRegistration $registration
 * @property-read \App\Models\Event\EventSession|null $session
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin byMethod(string $method)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin entryCheckins()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin exitCheckins()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin forEvent(int $eventId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin sessionCheckins()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereCheckType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereCheckedInAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereCheckedInBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereCheckedOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereRegistrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCheckin whereUuid($value)
 * @mixin \Eloquent
 */
class EventCheckin extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'registration_id',
        'session_id',
        'checked_in_by',
        'check_type',
        'method',
        'device_id',
        'location',
        'metadata',
        'checked_in_at',
        'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
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

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    // Scopes

    public function scopeEntryCheckins($query)
    {
        return $query->where('check_type', 'entry');
    }

    public function scopeExitCheckins($query)
    {
        return $query->where('check_type', 'exit');
    }

    public function scopeSessionCheckins($query)
    {
        return $query->where('check_type', 'session');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->whereHas('registration', function ($q) use ($eventId): void {
            $q->where('event_id', $eventId);
        });
    }

    public function scopeToday($query)
    {
        return $query->whereDate('checked_in_at', today());
    }

    // Accessors

    public function getIsEntryAttribute(): bool
    {
        return $this->check_type === 'entry';
    }

    public function getIsExitAttribute(): bool
    {
        return $this->check_type === 'exit';
    }

    public function getIsSessionAttribute(): bool
    {
        return $this->check_type === 'session';
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->checked_out_at === null) {
            return null;
        }

        return (int) $this->checked_in_at->diffInMinutes($this->checked_out_at);
    }

    public function getDurationForHumansAttribute(): ?string
    {
        $duration = $this->duration;

        if ($duration === null) {
            return null;
        }

        if ($duration < 60) {
            return "{$duration} min";
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        return "{$hours}h {$minutes}min";
    }

    // Methods

    public function checkOut(): void
    {
        $this->update([
            'checked_out_at' => now(),
        ]);
    }

    public function isQrCode(): bool
    {
        return $this->method === 'qr_code';
    }

    public function isManual(): bool
    {
        return $this->method === 'manual';
    }

    public function isNfc(): bool
    {
        return $this->method === 'nfc';
    }

    // Static methods

    public static function findLatestForRegistration(int $registrationId): ?self
    {
        return static::where('registration_id', $registrationId)
            ->latest('checked_in_at')
            ->first();
    }

    public static function getStatsByEvent(int $eventId): array
    {
        $query = static::forEvent($eventId);

        return [
            'total_checkins' => $query->entryCheckins()->count(),
            'today_checkins' => $query->entryCheckins()->today()->count(),
            'qr_checkins' => $query->byMethod('qr_code')->count(),
            'manual_checkins' => $query->byMethod('manual')->count(),
            'nfc_checkins' => $query->byMethod('nfc')->count(),
        ];
    }

    public static function getHourlyDistribution(int $eventId, ?\DateTime $date = null): array
    {
        $date ??= today();

        return static::forEvent($eventId)
            ->entryCheckins()
            ->whereDate('checked_in_at', $date)
            ->selectRaw('HOUR(checked_in_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }
}
