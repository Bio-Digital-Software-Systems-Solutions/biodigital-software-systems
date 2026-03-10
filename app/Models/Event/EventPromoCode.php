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

/**
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property string $code
 * @property string|null $description
 * @property string $discount_type
 * @property numeric $discount_value
 * @property numeric|null $min_order_amount
 * @property numeric|null $max_discount
 * @property int|null $usage_limit
 * @property int $usage_per_user
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property array<array-key, mixed>|null $applicable_tickets
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Event $event
 * @property-read string $formatted_discount
 * @property-read bool $is_expired
 * @property-read bool $is_not_yet_valid
 * @property-read int|null $usage_remaining
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventRegistration> $registrations
 * @property-read int|null $registrations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode valid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereApplicableTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereDiscountValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereMaxDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereMinOrderAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereUsageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereUsageLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereUsagePerUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventPromoCode withoutTrashed()
 * @mixin \Eloquent
 */
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
            ->where(function ($q): void {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q): void {
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

        if ($ticket instanceof \App\Models\Event\EventTicket && !$this->isValidForTicket($ticket)) {
            $errors[] = 'Ce code promo n\'est pas applicable à ce type de billet.';
        }

        if ($amount !== null && $this->min_order_amount !== null && $amount < $this->min_order_amount) {
            $errors[] = 'Le montant minimum de commande n\'est pas atteint.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'discount' => $errors === [] && $amount !== null ? $this->calculateDiscount($amount) : 0,
        ];
    }
}
