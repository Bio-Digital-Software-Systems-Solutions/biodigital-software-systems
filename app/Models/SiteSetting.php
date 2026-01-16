<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Site-wide settings stored as key-value pairs
 * 
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("site_setting:{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        $value = $setting->value;

        // Try to decode JSON if it looks like JSON
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value): void
    {
        // Encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear cache
        Cache::forget("site_setting:{$key}");
    }

    /**
     * Get global presence statistics
     */
    public static function getGlobalStats(): array
    {
        $stats = static::get('global_presence_stats');

        if ($stats && is_array($stats)) {
            return $stats;
        }

        // Return default values if not set
        return [
            'total_churches' => 117,
            'total_countries' => 27,
            'total_members' => 49820,
            'europe' => 63,
            'africa' => 39,
            'americas' => 12,
            'asia' => 2,
            'oceania' => 1,
        ];
    }

    /**
     * Set global presence statistics
     */
    public static function setGlobalStats(array $stats): void
    {
        static::set('global_presence_stats', $stats);
    }
}
