<?php

namespace App\Models\Event;

use App\Enums\Event\NotificationType;
use App\Models\Event;
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
 * @property int $event_id
 * @property NotificationType $type
 * @property string $trigger
 * @property string $subject
 * @property string $content
 * @property array<array-key, mixed>|null $recipients
 * @property string $status
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $failed_count
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property int|null $created_by
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User|null $creator
 * @property-read Event $event
 * @property-read bool $can_be_cancelled
 * @property-read bool $can_be_sent
 * @property-read bool $is_cancelled
 * @property-read bool $is_failed
 * @property-read bool $is_pending
 * @property-read bool $is_scheduled
 * @property-read bool $is_sent
 * @property-read float|null $success_rate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification byTrigger(string $trigger)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification byType(\App\Enums\Event\NotificationType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification readyToSend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification sent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereFailedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereRecipients($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereRecipientsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereSentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereTrigger($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventNotification whereUuid($value)
 * @mixin \Eloquent
 */
class EventNotification extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'event_id',
        'type',
        'trigger',
        'subject',
        'content',
        'recipients',
        'status',
        'recipients_count',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'recipients' => 'array',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Constants for triggers
    public const TRIGGER_ON_REGISTRATION = 'on_registration';

    public const TRIGGER_BEFORE_EVENT = 'before_event';

    public const TRIGGER_AFTER_EVENT = 'after_event';

    public const TRIGGER_ON_UPDATE = 'on_update';

    public const TRIGGER_MANUAL = 'manual';

    // Constants for status
    public const STATUS_PENDING = 'pending';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    public function scopeByType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }

    // Accessors

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsScheduledAttribute(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function getIsSentAttribute(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getSuccessRateAttribute(): ?float
    {
        if ($this->recipients_count === 0) {
            return null;
        }

        return round(($this->sent_count / $this->recipients_count) * 100, 1);
    }

    public function getCanBeSentAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SCHEDULED]);
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SCHEDULED]);
    }

    // Methods

    public function schedule(\DateTime $scheduledAt): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function markAsSent(int $sentCount, int $failedCount = 0): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
    }

    public function incrementFailedCount(): void
    {
        $this->increment('failed_count');
    }

    public function getRecipientsQuery()
    {
        $event = $this->event;
        $filters = $this->recipients ?? [];

        $query = EventRegistration::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['confirmed', 'checked_in']);

        // Apply filters from recipients array
        if (! empty($filters['ticket_ids'])) {
            $query->whereIn('ticket_id', $filters['ticket_ids']);
        }

        if (! empty($filters['roles'])) {
            $query->whereIn('participant_role', $filters['roles']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return $query;
    }

    public function getRecipientEmails(): array
    {
        return (array) $this->getRecipientsQuery()
            ->pluck('email')
            ->unique()
            ->toArray();
    }

    // Static methods

    public static function getTriggerOptions(): array
    {
        return [
            self::TRIGGER_ON_REGISTRATION => 'À l\'inscription',
            self::TRIGGER_BEFORE_EVENT => 'Avant l\'événement',
            self::TRIGGER_AFTER_EVENT => 'Après l\'événement',
            self::TRIGGER_ON_UPDATE => 'À la mise à jour',
            self::TRIGGER_MANUAL => 'Manuel',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_SCHEDULED => 'Planifié',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    public static function createWelcomeNotification(Event $event, int $createdBy): self
    {
        return static::create([
            'event_id' => $event->id,
            'type' => NotificationType::EMAIL,
            'trigger' => self::TRIGGER_ON_REGISTRATION,
            'subject' => "Confirmation d'inscription - {$event->title}",
            'content' => "Bonjour {name},\n\nVotre inscription à l'événement \"{$event->title}\" a été confirmée.\n\nCordialement,\nL'équipe d'organisation",
            'status' => self::STATUS_PENDING,
            'created_by' => $createdBy,
        ]);
    }

    public static function createReminderNotification(Event $event, int $createdBy, int $daysBefore = 1): self
    {
        return static::create([
            'event_id' => $event->id,
            'type' => NotificationType::EMAIL,
            'trigger' => self::TRIGGER_BEFORE_EVENT,
            'subject' => "Rappel : {$event->title} - J-{$daysBefore}",
            'content' => "Bonjour {name},\n\nNous vous rappelons que l'événement \"{$event->title}\" aura lieu dans {$daysBefore} jour(s).\n\nCordialement,\nL'équipe d'organisation",
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $event->start_date->subDays($daysBefore),
            'created_by' => $createdBy,
        ]);
    }
}
