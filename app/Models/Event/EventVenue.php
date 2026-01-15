<?php

namespace App\Models\Event;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventVenue extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'total_capacity',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'amenities',
        'images',
        'access_instructions',
        'parking_info',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_capacity' => 'integer',
            'amenities' => 'array',
            'images' => 'array',
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

    public function rooms(): HasMany
    {
        return $this->hasMany(VenueRoom::class, 'venue_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeWithCapacityAtLeast($query, int $capacity)
    {
        return $query->where('total_capacity', '>=', $capacity);
    }

    // Accessors

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line1,
            $this->address_line2,
            $this->postal_code . ' ' . $this->city,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    public function getRoomsCountAttribute(): int
    {
        return $this->rooms()->count();
    }

    public function getAvailableRoomsCountAttribute(): int
    {
        return $this->rooms()->where('is_available', true)->count();
    }

    public function getTotalRoomCapacityAttribute(): int
    {
        return $this->rooms()->sum('capacity') ?? 0;
    }

    // Methods

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getGoogleMapsUrl(): ?string
    {
        if ($this->hasCoordinates()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($this->full_address);
    }

    public function hasAmenity(string $amenity): bool
    {
        return in_array($amenity, $this->amenities ?? []);
    }
}
