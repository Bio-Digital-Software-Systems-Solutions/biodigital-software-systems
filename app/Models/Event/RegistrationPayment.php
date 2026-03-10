<?php

namespace App\Models\Event;

use App\Enums\Event\PaymentStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $registration_id
 * @property string $payment_number
 * @property PaymentStatus $status
 * @property string|null $payment_method
 * @property string|null $payment_provider
 * @property string|null $transaction_id
 * @property numeric $amount
 * @property numeric $fee
 * @property numeric $net_amount
 * @property string $currency
 * @property array<array-key, mixed>|null $provider_response
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property numeric|null $refund_amount
 * @property string|null $refund_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read bool $can_refund
 * @property-read string $formatted_amount
 * @property-read string $formatted_net_amount
 * @property-read bool $is_paid
 * @property-read bool $is_refunded
 * @property-read \App\Models\Event\EventRegistration $registration
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment refunded()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereNetAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment wherePaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment wherePaymentProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereProviderResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereRefundAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereRefundReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereRefundedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereRegistrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationPayment whereUuid($value)
 * @mixin \Eloquent
 */
class RegistrationPayment extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'registration_id',
        'payment_number',
        'status',
        'payment_method',
        'payment_provider',
        'transaction_id',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'provider_response',
        'notes',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'provider_response' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
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

        static::creating(function ($payment): void {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }

            if ($payment->net_amount === null) {
                $payment->net_amount = $payment->amount - $payment->fee;
            }
        });
    }

    // Relationships

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', PaymentStatus::REFUNDED);
    }

    // Accessors

    public function getIsPaidAttribute(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    public function getCanRefundAttribute(): bool
    {
        return (bool) $this->status->canRefund();
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ').' '.$this->currency;
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2, ',', ' ').' '.$this->currency;
    }

    // Methods

    public function markAsCompleted(?string $transactionId = null): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'paid_at' => now(),
        ]);

        // Confirm the registration if it was pending payment
        if ($this->registration->is_pending) {
            $this->registration->confirm();
        }
    }

    public function markAsFailed(?array $response = null): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'provider_response' => $response,
        ]);
    }

    public function processRefund(float $amount, ?string $reason = null): bool
    {
        if (! $this->can_refund) {
            return false;
        }

        $this->update([
            'status' => $amount >= $this->amount ? PaymentStatus::REFUNDED : PaymentStatus::PARTIAL,
            'refunded_at' => now(),
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);

        return true;
    }

    // Static methods

    public static function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-'.strtoupper(Str::random(8));
        } while (static::where('payment_number', $number)->exists());

        return $number;
    }

    public static function findByTransactionId(string $transactionId): ?self
    {
        return static::where('transaction_id', $transactionId)->first();
    }
}
