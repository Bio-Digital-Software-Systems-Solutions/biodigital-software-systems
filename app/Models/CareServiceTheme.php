<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property string|null $icon
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CareService> $careServices
 * @property-read int|null $care_services_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme active()
 * @method static \Database\Factories\CareServiceThemeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceTheme whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CareServiceTheme extends Model
{
    /** @use HasFactory<\Database\Factories\CareServiceThemeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the care service appointments for this theme.
     */
    public function careServices(): BelongsToMany
    {
        return $this->belongsToMany(CareService::class, 'care_service_care_service_theme')
            ->withTimestamps();
    }

    /**
     * Scope to get only active themes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
