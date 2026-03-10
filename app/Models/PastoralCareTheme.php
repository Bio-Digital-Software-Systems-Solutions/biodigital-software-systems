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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PastoralCare> $pastoralCares
 * @property-read int|null $pastoral_cares_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme active()
 * @method static \Database\Factories\PastoralCareThemeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCareTheme whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PastoralCareTheme extends Model
{
    /** @use HasFactory<\Database\Factories\PastoralCareThemeFactory> */
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
     * Get the pastoral care appointments for this theme.
     */
    public function pastoralCares(): BelongsToMany
    {
        return $this->belongsToMany(PastoralCare::class, 'pastoral_care_pastoral_care_theme')
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
