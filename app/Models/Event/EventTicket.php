<?php

namespace App\Models\Event;

use App\Enums\Event\TicketType;
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

class EventTicket extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'type',
        'price',
        'original_price',
        'currency',
        'quantity_total',
        'quantity_sold',
        'quantity_reserved',
        'min_per_order',
        'max_per_order',
        'sales_start',
        'sales_end',
        'benefits',
        'restrictions',
        'is_visible',
        'requires_approval',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'quantity_total' => 'integer',
            'quantity_sold' => 'integer',
            'quantity_reserved' => 'integer',
            'min_per_order' => 'integer',
            'max_per_order' => 'integer',
            'sales_start' => 'datetime',
            'sales_end' => 'datetime',
            'benefits' => 'array',
            'restrictions' => 'array',
            'is_visible' => 'boolean',
            'requires_approval' => 'boolean',
            'sort_order' => 'integer',
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
        return $this->hasMany(EventRegistration::class, 'ticket_id');
    }

    // Scopes

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeOnSale($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('sales_start')
                ->orWhere('sales_start', '<=', now());
        })->where(function ($q) {
            $q->whereNull('sales_end')
                ->orWhere('sales_end', '>=', now());
        });
    }

    public function scopeAvailable($query)
    {
        return $query->visible()->onSale();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Accessors

    public function getQuantityAvailableAttribute(): ?int
    {
        if ($this->quantity_total === null) {
            return null;
        }

        return max(0, $this->quantity_total - $this->quantity_sold - $this->quantity_reserved);
    }

    public function getIsSoldOutAttribute(): bool
    {
        if ($this->quantity_total === null) {
            return false;
        }

        return $this->quantity_available <= 0;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->price == 0;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->original_price !== null && $this->original_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->has_discount || $this->original_price == 0) {
            return null;
        }

        return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return 'Gratuit';
        }

        return number_format($this->price, 2, ',', ' ') . ' ' . $this->currency;
    }

    // Methods

    public function isOnSale(): bool
    {
        if ($this->sales_start && now()->isBefore($this->sales_start)) {
            return false;
        }

        if ($this->sales_end && now()->isAfter($this->sales_end)) {
            return false;
        }

        return true;
    }

    public function isSoldOut(): bool
    {
        return $this->is_sold_out;
    }

    public function canPurchase(int $quantity = 1): bool
    {
        if (!$this->is_visible || !$this->isOnSale()) {
            return false;
        }

        if ($this->isSoldOut()) {
            return false;
        }

        if ($this->quantity_available !== null && $quantity > $this->quantity_available) {
            return false;
        }

        if ($quantity < $this->min_per_order) {
            return false;
        }

        if ($this->max_per_order !== null && $quantity > $this->max_per_order) {
            return false;
        }

        return true;
    }

    public function reserve(int $quantity): bool
    {
        if (!$this->canPurchase($quantity)) {
            return false;
        }

        $this->increment('quantity_reserved', $quantity);

        return true;
    }

    public function releaseReservation(int $quantity): void
    {
        $this->decrement('quantity_reserved', min($quantity, $this->quantity_reserved));
    }

    public function confirmSale(int $quantity): void
    {
        $this->decrement('quantity_reserved', $quantity);
        $this->increment('quantity_sold', $quantity);
    }

    public function calculatePrice(int $quantity = 1, ?EventPromoCode $promoCode = null): array
    {
        $unitPrice = $this->price;
        $subtotal = $unitPrice * $quantity;
        $discount = 0;

        if ($promoCode && $promoCode->isValidForTicket($this)) {
            $discount = $promoCode->calculateDiscount($subtotal);
        }

        return [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
            'currency' => $this->currency,
        ];
    }
}
