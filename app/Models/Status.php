<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $active_tasks_count
 * @property-read string $color_class
 * @property-read string $display_label
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status completion()
 * @method static \Database\Factories\StatusFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status final()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereUpdatedAt($value)
 * @property string $uuid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereUuid($value)
 * @mixin \Eloquent
 */
class Status extends Model
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'label',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
        'is_final',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_final' => 'boolean',
        ];
    }

    /**
     * Get the tasks with this status.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the total number of tasks with this status.
     */
    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get the active tasks count with this status.
     */
    public function getActiveTasksCountAttribute(): int
    {
        return $this->tasks()
            ->whereHas('program', function ($query) {
                $query->whereIn('status', ['active', 'in_progress']);
            })
            ->count();
    }

    /**
     * Check if this status indicates completion.
     */
    public function isCompleted(): bool
    {
        return in_array(strtolower($this->name), ['completed', 'done', 'finished', 'closed']);
    }

    /**
     * Check if this status indicates work in progress.
     */
    public function isInProgress(): bool
    {
        return in_array(strtolower($this->name), ['in_progress', 'working', 'active', 'ongoing']);
    }

    /**
     * Check if this status indicates pending state.
     */
    public function isPending(): bool
    {
        return in_array(strtolower($this->name), ['pending', 'waiting', 'queued', 'new']);
    }

    /**
     * Get the display label for the status.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: ucfirst(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the CSS class for the status color.
     */
    public function getColorClassAttribute(): string
    {
        return match ($this->color) {
            'red' => 'text-red-600 bg-red-100',
            'green' => 'text-green-600 bg-green-100',
            'blue' => 'text-blue-600 bg-blue-100',
            'yellow' => 'text-yellow-600 bg-yellow-100',
            'purple' => 'text-purple-600 bg-purple-100',
            'gray' => 'text-gray-600 bg-gray-100',
            'orange' => 'text-orange-600 bg-orange-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    /**
     * Scope a query to only include active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include final statuses.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Scope a query to order statuses by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to get completion statuses.
     */
    public function scopeCompletion($query)
    {
        return $query->whereIn('name', ['completed', 'done', 'finished', 'closed']);
    }

    /**
     * Scope a query to get in-progress statuses.
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('name', ['in_progress', 'working', 'active', 'ongoing']);
    }

    /**
     * Scope a query to get pending statuses.
     */
    public function scopePending($query)
    {
        return $query->whereIn('name', ['pending', 'waiting', 'queued', 'new']);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
