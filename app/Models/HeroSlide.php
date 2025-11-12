<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $media_type
 * @property string $media_url
 * @property string|null $cta_text
 * @property string|null $cta_link
 * @property numeric $overlay_opacity
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide active()
 * @method static \Database\Factories\HeroSlideFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereCtaLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereCtaText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereMediaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereMediaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereOverlayOpacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereUpdatedAt($value)
 * @property string $uuid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HeroSlide whereUuid($value)
 * @mixin \Eloquent
 */
class HeroSlide extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache;

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
    protected $fillable = [
        'title',
        'description',
        'media_type',
        'media_url',
        'cta_text',
        'cta_link',
        'overlay_opacity',
        'order',
        'is_active',
    ];

    protected $casts = [
        'overlay_opacity' => 'decimal:2',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active slides ordered by order field
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
}
