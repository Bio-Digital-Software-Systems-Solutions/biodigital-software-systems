<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Church model for ICC churches worldwide
 * 
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $country
 * @property float $latitude
 * @property float $longitude
 * @property int $members
 * @property string|null $address
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $continent
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Church extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'country',
        'latitude',
        'longitude',
        'members',
        'address',
        'website',
        'email',
        'phone',
        'continent',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'members' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active churches
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get coordinates in the format expected by the frontend
     */
    public function getCoordinatesAttribute(): array
    {
        return [$this->longitude, $this->latitude];
    }

    /**
     * Determine continent based on coordinates
     */
    public static function detectContinent(float $latitude, float $longitude): string
    {
        // Simple continent detection based on coordinates
        // Europe
        if ($latitude >= 35 && $latitude <= 71 && $longitude >= -10 && $longitude <= 40) {
            return 'europe';
        }
        // Africa
        if ($latitude >= -35 && $latitude <= 37 && $longitude >= -20 && $longitude <= 55) {
            return 'africa';
        }
        // Asia
        if ($latitude >= 5 && $latitude <= 77 && $longitude >= 40 && $longitude <= 180) {
            return 'asia';
        }
        // Oceania
        if ($latitude >= -50 && $latitude <= 0 && $longitude >= 110 && $longitude <= 180) {
            return 'oceania';
        }
        // Americas (default for rest)
        return 'americas';
    }

    /**
     * Boot method to auto-detect continent on create/update
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($church) {
            if (!$church->continent) {
                $church->continent = static::detectContinent($church->latitude, $church->longitude);
            }
        });
    }
}
