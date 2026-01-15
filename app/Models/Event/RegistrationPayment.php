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

        static::creating(function ($payment) {
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
        return $this->status->canRefund();
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2, ',', ' ') . ' ' . $this->currency;
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
        if (!$this->can_refund) {
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
            $number = 'PAY-' . strtoupper(Str::random(8));
        } while (static::where('payment_number', $number)->exists());

        return $number;
    }

    public static function findByTransactionId(string $transactionId): ?self
    {
        return static::where('transaction_id', $transactionId)->first();
    }
}
