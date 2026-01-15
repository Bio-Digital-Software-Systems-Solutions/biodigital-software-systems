<?php

namespace App\Models\Event;

use App\Models\Event;
use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventSponsor extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'tier',
        'logo',
        'website',
        'description',
        'contact_name',
        'contact_email',
        'contact_phone',
        'sponsorship_amount',
        'benefits',
        'social_links',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sponsorship_amount' => 'decimal:2',
            'benefits' => 'array',
            'social_links' => 'array',
            'is_featured' => 'boolean',
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

    // Constants for tiers
    public const TIER_PLATINUM = 'platinum';
    public const TIER_GOLD = 'gold';
    public const TIER_SILVER = 'silver';
    public const TIER_BRONZE = 'bronze';
    public const TIER_STANDARD = 'standard';

    public const TIERS = [
        self::TIER_PLATINUM => ['label' => 'Platinum', 'color' => '#E5E4E2', 'priority' => 1],
        self::TIER_GOLD => ['label' => 'Gold', 'color' => '#FFD700', 'priority' => 2],
        self::TIER_SILVER => ['label' => 'Silver', 'color' => '#C0C0C0', 'priority' => 3],
        self::TIER_BRONZE => ['label' => 'Bronze', 'color' => '#CD7F32', 'priority' => 4],
        self::TIER_STANDARD => ['label' => 'Standard', 'color' => '#6B7280', 'priority' => 5],
    ];

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // Scopes

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("FIELD(tier, 'platinum', 'gold', 'silver', 'bronze', 'standard')")
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    // Accessors

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

    public function getTierLabelAttribute(): string
    {
        return self::TIERS[$this->tier]['label'] ?? ucfirst($this->tier);
    }

    public function getTierColorAttribute(): string
    {
        return self::TIERS[$this->tier]['color'] ?? '#6B7280';
    }

    public function getTierPriorityAttribute(): int
    {
        return self::TIERS[$this->tier]['priority'] ?? 99;
    }

    public function getFormattedAmountAttribute(): ?string
    {
        if ($this->sponsorship_amount === null) {
            return null;
        }

        return number_format($this->sponsorship_amount, 2, ',', ' ') . ' €';
    }

    // Methods

    public function isPlatinum(): bool
    {
        return $this->tier === self::TIER_PLATINUM;
    }

    public function isGold(): bool
    {
        return $this->tier === self::TIER_GOLD;
    }

    public function isSilver(): bool
    {
        return $this->tier === self::TIER_SILVER;
    }

    public function isBronze(): bool
    {
        return $this->tier === self::TIER_BRONZE;
    }

    public function hasBenefit(string $benefit): bool
    {
        return in_array($benefit, $this->benefits ?? []);
    }

    public function getSocialLink(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
    }

    // Static methods

    public static function getTierOptions(): array
    {
        return array_map(fn ($tier) => $tier['label'], self::TIERS);
    }

    public static function getTotalSponsorshipByEvent(int $eventId): float
    {
        return static::where('event_id', $eventId)->sum('sponsorship_amount') ?? 0;
    }
}
