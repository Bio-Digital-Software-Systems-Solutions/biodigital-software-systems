<?php

namespace App\Models\Event;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property string|null $icon
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventCategory> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int $events_count
 * @property-read string $full_path
 * @property-read EventCategory|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory rootCategories()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventCategory withoutTrashed()
 * @mixin \Eloquent
 */
class EventCategory extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EventCategory::class, 'parent_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(\App\Models\Event::class, 'category_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors

    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    public function getEventsCountAttribute(): int
    {
        return $this->events()->count();
    }

    // Methods

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }
}
