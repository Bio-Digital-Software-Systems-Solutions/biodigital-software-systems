<?php

namespace App\Models\Event;

use App\Models\Event;
use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventWaitlist extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $table = 'event_waitlist';

    protected $fillable = [
        'event_id',
        'ticket_id',
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'quantity',
        'position',
        'status',
        'notified_at',
        'expires_at',
        'converted_at',
        'registration_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'position' => 'integer',
            'notified_at' => 'datetime',
            'expires_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Constants for status
    public const STATUS_WAITING = 'waiting';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(EventTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    // Scopes

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeNotified($query)
    {
        return $query->where('status', self::STATUS_NOTIFIED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_NOTIFIED]);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsWaitingAttribute(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function getIsNotifiedAttribute(): bool
    {
        return $this->status === self::STATUS_NOTIFIED;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function getCanBeNotifiedAttribute(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function getHasExpiredAttribute(): bool
    {
        return $this->expires_at !== null && now()->isAfter($this->expires_at);
    }

    // Methods

    public function notify(int $expirationHours = 24): void
    {
        $this->update([
            'status' => self::STATUS_NOTIFIED,
            'notified_at' => now(),
            'expires_at' => now()->addHours($expirationHours),
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function convert(EventRegistration $registration): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_at' => now(),
            'registration_id' => $registration->id,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function reposition(): void
    {
        // Get the new position
        $maxPosition = static::forEvent($this->event_id)
            ->active()
            ->max('position') ?? 0;

        $this->update([
            'position' => $maxPosition + 1,
        ]);
    }

    // Static methods

    public static function getNextPosition(int $eventId, ?int $ticketId = null): int
    {
        $query = static::forEvent($eventId)->active();

        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }

        return ($query->max('position') ?? 0) + 1;
    }

    public static function addToWaitlist(
        Event $event,
        array $data,
        ?EventTicket $ticket = null,
        ?User $user = null
    ): self {
        return static::create([
            'event_id' => $event->id,
            'ticket_id' => $ticket?->id,
            'user_id' => $user?->id,
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'position' => static::getNextPosition($event->id, $ticket?->id),
            'status' => self::STATUS_WAITING,
        ]);
    }

    public static function promoteNext(int $eventId, ?int $ticketId = null, int $slots = 1): array
    {
        $promoted = [];

        $query = static::forEvent($eventId)
            ->waiting()
            ->ordered();

        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }

        $toPromote = $query->limit($slots)->get();

        foreach ($toPromote as $entry) {
            $entry->notify();
            $promoted[] = $entry;
        }

        return $promoted;
    }

    public static function expireOldNotifications(): int
    {
        return static::notified()
            ->where('expires_at', '<', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_WAITING => 'En attente',
            self::STATUS_NOTIFIED => 'Notifié',
            self::STATUS_EXPIRED => 'Expiré',
            self::STATUS_CONVERTED => 'Converti',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }
}
