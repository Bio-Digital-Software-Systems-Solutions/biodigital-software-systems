<?php

namespace App\Models\Event;

use App\Enums\Event\ParticipantRole;
use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\User;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $registration_number
 * @property int $event_id
 * @property int|null $user_id
 * @property int|null $ticket_id
 * @property int|null $promo_code_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $job_title
 * @property RegistrationStatus $status
 * @property ParticipantRole $participant_role
 * @property int $quantity
 * @property numeric $unit_price
 * @property numeric $discount_amount
 * @property numeric $total_amount
 * @property string $currency
 * @property array<array-key, mixed>|null $form_answers
 * @property array<array-key, mixed>|null $dietary_requirements
 * @property array<array-key, mixed>|null $accessibility_needs
 * @property string|null $special_requests
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $qr_code
 * @property \Illuminate\Support\Carbon|null $registered_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Event\EventBadge|null $badge
 * @property-read User|null $cancelledByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventCheckin> $checkins
 * @property-read int|null $checkins_count
 * @property-read Event $event
 * @property-read string $formatted_total
 * @property-read string $full_name
 * @property-read bool $is_cancelled
 * @property-read bool $is_checked_in
 * @property-read bool $is_confirmed
 * @property-read bool $is_pending
 * @property-read bool $is_waitlisted
 * @property-read \App\Models\Event\EventCheckin|null $last_checkin
 * @property-read \App\Models\Event\RegistrationPayment|null $latestPayment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\RegistrationPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Event\EventPromoCode|null $promoCode
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventSession> $sessions
 * @property-read int|null $sessions_count
 * @property-read \App\Models\Event\EventTicket|null $ticket
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration checkedIn()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration forEvent(int $eventId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration waitlisted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereAccessibilityNeeds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCancelledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereDietaryRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereFormAnswers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereParticipantRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration wherePromoCodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereRegisteredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereSpecialRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRegistration withoutTrashed()
 * @mixin \Eloquent
 */
class EventRegistration extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'registration_number',
        'event_id',
        'user_id',
        'ticket_id',
        'promo_code_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'status',
        'participant_role',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_amount',
        'currency',
        'form_answers',
        'dietary_requirements',
        'accessibility_needs',
        'special_requests',
        'metadata',
        'qr_code',
        'registered_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'participant_role' => ParticipantRole::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'form_answers' => 'array',
            'dietary_requirements' => 'array',
            'accessibility_needs' => 'array',
            'metadata' => 'array',
            'registered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($registration): void {
            if (empty($registration->registration_number)) {
                $registration->registration_number = static::generateRegistrationNumber();
            }

            if (empty($registration->qr_code)) {
                $registration->qr_code = static::generateQrCode();
            }

            if (empty($registration->registered_at)) {
                $registration->registered_at = now();
            }
        });
    }

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(EventTicket::class, 'ticket_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(EventPromoCode::class, 'promo_code_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RegistrationPayment::class, 'registration_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(RegistrationPayment::class, 'registration_id')->latestOfMany();
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(EventCheckin::class, 'registration_id');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(EventBadge::class, 'registration_id');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(EventSession::class, 'session_attendees', 'registration_id', 'session_id')
            ->withPivot('status', 'registered_at', 'attended_at')
            ->withTimestamps();
    }

    // Scopes

    public function scopeConfirmed($query)
    {
        return $query->where('status', RegistrationStatus::CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', RegistrationStatus::PENDING);
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', RegistrationStatus::WAITLISTED);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', RegistrationStatus::CHECKED_IN);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', RegistrationStatus::activeStatuses());
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->status === RegistrationStatus::CONFIRMED;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === RegistrationStatus::PENDING;
    }

    public function getIsWaitlistedAttribute(): bool
    {
        return $this->status === RegistrationStatus::WAITLISTED;
    }

    public function getIsCheckedInAttribute(): bool
    {
        return $this->status === RegistrationStatus::CHECKED_IN;
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === RegistrationStatus::CANCELLED;
    }

    public function getFormattedTotalAttribute(): string
    {
        if ($this->total_amount == 0) {
            return 'Gratuit';
        }

        return number_format($this->total_amount, 2, ',', ' ').' '.$this->currency;
    }

    public function getLastCheckinAttribute(): ?EventCheckin
    {
        return $this->checkins()->latest('checked_in_at')->first();
    }

    // Methods

    public function confirm(): bool
    {
        if (! $this->status->canCheckIn() && $this->status !== RegistrationStatus::PENDING) {
            return false;
        }

        $this->update([
            'status' => RegistrationStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return true;
    }

    public function cancel(?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (! $this->status->canCancel()) {
            return false;
        }

        $this->update([
            'status' => RegistrationStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        // Release ticket reservation if applicable
        if ($this->ticket) {
            $this->ticket->releaseReservation($this->quantity);
        }

        // Decrement promo code usage if applicable
        if ($this->promoCode) {
            $this->promoCode->decrementUsage();
        }

        return true;
    }

    public function checkIn(?int $checkedInBy = null, string $method = 'qr_code'): EventCheckin
    {
        $this->update([
            'status' => RegistrationStatus::CHECKED_IN,
        ]);

        return $this->checkins()->create([
            'checked_in_by' => $checkedInBy,
            'check_type' => 'entry',
            'method' => $method,
            'checked_in_at' => now(),
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update([
            'status' => RegistrationStatus::NO_SHOW,
        ]);
    }

    public function promoteFromWaitlist(): bool
    {
        if (! $this->status->canBePromoted()) {
            return false;
        }

        return $this->confirm();
    }

    public function isPaid(): bool
    {
        if ($this->total_amount == 0) {
            return true;
        }

        $paidAmount = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return $paidAmount >= $this->total_amount;
    }

    public function getOutstandingAmount(): float
    {
        $paidAmount = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return max(0, $this->total_amount - $paidAmount);
    }

    public function generateQrCodeImage(): string
    {
        // Returns the data to be encoded in QR code
        return (string) json_encode([
            'type' => 'event_registration',
            'code' => $this->qr_code,
            'event_id' => $this->event_id,
            'registration_id' => $this->id,
        ]);
    }

    // Static methods

    public static function generateRegistrationNumber(): string
    {
        do {
            $number = 'REG-'.strtoupper(Str::random(8));
        } while (static::where('registration_number', $number)->exists());

        return $number;
    }

    public static function generateQrCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (static::where('qr_code', $code)->exists());

        return $code;
    }

    public static function findByQrCode(string $qrCode): ?self
    {
        return static::where('qr_code', $qrCode)->first();
    }

    public static function findByRegistrationNumber(string $number): ?self
    {
        return static::where('registration_number', $number)->first();
    }
}
