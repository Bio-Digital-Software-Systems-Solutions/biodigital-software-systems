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
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $members
 * @property string|null $address
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $leader_name
 * @property string|null $category
 * @property string|null $continent
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $category_label
 * @property-read array $coordinates
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereContinent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereLeaderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Church whereWebsite($value)
 * @mixin \Eloquent
 */
class Church extends Model
{
    use HasFactory;

    // Category constants
    public const CATEGORY_EGLISE = 'eglise';
    public const CATEGORY_CAMPUS_CONNECTE = 'campus_connecte';
    public const CATEGORY_FAMILLE_CONNECTE = 'famille_connecte';
    public const CATEGORY_FAMILLE_IMPACT = 'famille_impact';

    public const CATEGORIES = [
        self::CATEGORY_EGLISE => 'Église',
        self::CATEGORY_CAMPUS_CONNECTE => 'Campus connecté',
        self::CATEGORY_FAMILLE_CONNECTE => 'Famille connectée',
        self::CATEGORY_FAMILLE_IMPACT => 'Famille d\'impact',
    ];

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
        'leader_name',
        'category',
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
     * Get the French label for the category
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Église';
    }

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

        static::saving(function ($church): void {
            if (!$church->continent && $church->latitude !== null && $church->longitude !== null) {
                $church->continent = static::detectContinent($church->latitude, $church->longitude);
            }
        });
    }
}
