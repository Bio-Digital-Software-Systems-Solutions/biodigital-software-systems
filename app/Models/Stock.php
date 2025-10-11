<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property int $quantity
 * @property int $minimum_quantity
 * @property numeric $unit_price
 * @property string|null $supplier
 * @property string|null $supplier_contact
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $location
 * @property bool $is_active
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read int|null $days_until_expiry
 * @property-read string $expiry_status
 * @property-read string $stock_status
 * @property-read string $stock_status_label
 * @property-read float $total_value
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock bySupplier($supplier)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock expired()
 * @method static \Database\Factories\StockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock lowStock()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock nearExpiry()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock outOfStock()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereMinimumQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereSupplierContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Stock extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'sku',
        'description',
        'quantity',
        'minimum_quantity',
        'unit_price',
        'supplier',
        'supplier_contact',
        'expiry_date',
        'location',
        'is_active',
        'category_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'minimum_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the stock item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the total value of the stock item.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Check if the stock is low (below minimum quantity).
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Check if the stock is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Check if the stock item is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && now()->isAfter($this->expiry_date);
    }

    /**
     * Check if the stock item is near expiry (within 30 days).
     */
    public function isNearExpiry(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return now()->diffInDays($this->expiry_date) <= 30 &&
               now()->isBefore($this->expiry_date);
    }

    /**
     * Get the days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        $diffInDays = now()->diffInDays($this->expiry_date, false);

        return (int) $diffInDays;
    }

    /**
     * Get the stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get the stock status label.
     */
    public function getStockStatusLabelAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    /**
     * Get the expiry status.
     */
    public function getExpiryStatusAttribute(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isNearExpiry()) {
            return 'near_expiry';
        }

        return 'fresh';
    }

    /**
     * Scope a query to only include active stock items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_quantity');
    }

    /**
     * Scope a query to only include out of stock items.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope a query to only include expired items.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope a query to only include items near expiry.
     */
    public function scopeNearExpiry($query)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)]);
    }

    /**
     * Scope a query to filter by supplier.
     */
    public function scopeBySupplier($query, $supplier)
    {
        return $query->where('supplier', 'like', "%{$supplier}%");
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
