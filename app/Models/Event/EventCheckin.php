<?php

namespace App\Models\Event;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        return $query->whereHas('registration', function ($q) use ($eventId) {
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

        return $this->checked_in_at->diffInMinutes($this->checked_out_at);
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
        $date = $date ?? today();

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
