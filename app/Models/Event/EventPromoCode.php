<?php

namespace App\Models\Event;

use App\Models\Event;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventPromoCode extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'event_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'usage_per_user',
        'usage_count',
        'valid_from',
        'valid_until',
        'applicable_tickets',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_per_user' => 'integer',
            'usage_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'applicable_tickets' => 'array',
            'is_active' => 'boolean',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'promo_code_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    // Accessors

    public function getIsExpiredAttribute(): bool
    {
        if ($this->valid_until === null) {
            return false;
        }

        return now()->isAfter($this->valid_until);
    }

    public function getIsNotYetValidAttribute(): bool
    {
        if ($this->valid_from === null) {
            return false;
        }

        return now()->isBefore($this->valid_from);
    }

    public function getUsageRemainingAttribute(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }

        return max(0, $this->usage_limit - $this->usage_count);
    }

    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }

        return number_format($this->discount_value, 2, ',', ' ') . ' €';
    }

    // Methods

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->is_expired || $this->is_not_yet_valid) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isValidForTicket(EventTicket $ticket): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (empty($this->applicable_tickets)) {
            return true;
        }

        return in_array($ticket->id, $this->applicable_tickets);
    }

    public function canBeUsedByUser(int $userId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $userUsageCount = $this->registrations()
            ->where('user_id', $userId)
            ->count();

        return $userUsageCount < $this->usage_per_user;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->min_order_amount !== null && $amount < $this->min_order_amount) {
            return 0;
        }

        $discount = $this->discount_type === 'percentage'
            ? $amount * ($this->discount_value / 100)
            : $this->discount_value;

        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 2);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }

    public function validate(?int $userId = null, ?EventTicket $ticket = null, ?float $amount = null): array
    {
        $errors = [];

        if (!$this->is_active) {
            $errors[] = 'Ce code promo n\'est pas actif.';
        }

        if ($this->is_expired) {
            $errors[] = 'Ce code promo a expiré.';
        }

        if ($this->is_not_yet_valid) {
            $errors[] = 'Ce code promo n\'est pas encore valide.';
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            $errors[] = 'Ce code promo a atteint sa limite d\'utilisation.';
        }

        if ($userId !== null && !$this->canBeUsedByUser($userId)) {
            $errors[] = 'Vous avez déjà utilisé ce code promo.';
        }

        if ($ticket !== null && !$this->isValidForTicket($ticket)) {
            $errors[] = 'Ce code promo n\'est pas applicable à ce type de billet.';
        }

        if ($amount !== null && $this->min_order_amount !== null && $amount < $this->min_order_amount) {
            $errors[] = 'Le montant minimum de commande n\'est pas atteint.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'discount' => empty($errors) && $amount !== null ? $this->calculateDiscount($amount) : 0,
        ];
    }
}
