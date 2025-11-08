<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $street
 * @property string $city
 * @property string|null $postal_code
 * @property string $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int $events_count
 * @property-read string $formatted_address
 * @property-read string $full_address
 * @property-read string $google_maps_url
 * @property-read int|null $libraries_count
 * @property-read string $short_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Library> $libraries
 * @method static \Database\Factories\AddressFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address inCity($city)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address inCountry($country)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withCoordinates()
 * @mixin \Eloquent
 */
class Address extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

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
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'street',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Get the events at this address.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the libraries at this address.
     */
    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the short address (street, city, state).
     */
    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the formatted address for display.
     */
    public function getFormattedAddressAttribute(): string
    {
        $formatted = $this->street;

        if ($this->city || $this->postal_code) {
            $formatted .= "\n";
            $cityZip = array_filter([$this->city, $this->postal_code]);
            $formatted .= implode(', ', $cityZip);
        }

        if ($this->country) {
            $formatted .= "\n".$this->country;
        }

        return $formatted;
    }

    /**
     * Check if the address has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get the Google Maps URL for this address.
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        if ($this->hasCoordinates()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return 'https://www.google.com/maps?q='.urlencode($this->full_address);
    }

    /**
     * Calculate distance to another address (in kilometers).
     */
    public function distanceTo(Address $address): ?float
    {
        if (! $this->hasCoordinates() || ! $address->hasCoordinates()) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($address->latitude);
        $lon2 = deg2rad($address->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get the total number of events at this address.
     */
    public function getEventsCountAttribute(): int
    {
        return $this->events()->count();
    }

    /**
     * Get the total number of libraries at this address.
     */
    public function getLibrariesCountAttribute(): int
    {
        return $this->libraries()->count();
    }

    /**
     * Scope a query to filter by city.
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeInCountry($query, $country)
    {
        return $query->where('country', 'like', "%{$country}%");
    }

    /**
     * Scope a query to only include addresses with coordinates.
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}
