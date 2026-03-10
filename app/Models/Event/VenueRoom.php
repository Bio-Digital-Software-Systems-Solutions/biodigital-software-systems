<?php

namespace App\Models\Event;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $venue_id
 * @property string $name
 * @property string|null $description
 * @property int|null $capacity
 * @property string|null $floor
 * @property string|null $room_number
 * @property array<array-key, mixed>|null $equipment
 * @property array<array-key, mixed>|null $layout_options
 * @property bool $is_available
 * @property numeric|null $hourly_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string $full_name
 * @property-read int|null $sessions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\EventSession> $sessions
 * @property-read \App\Models\Event\EventVenue $venue
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereEquipment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereFloor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereLayoutOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereRoomNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom whereVenueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom withCapacityAtLeast(int $capacity)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VenueRoom withoutTrashed()
 * @mixin \Eloquent
 */
class VenueRoom extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache, SoftDeletes;

    protected $fillable = [
        'venue_id',
        'name',
        'description',
        'capacity',
        'floor',
        'room_number',
        'equipment',
        'layout_options',
        'is_available',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'equipment' => 'array',
            'layout_options' => 'array',
            'is_available' => 'boolean',
            'hourly_rate' => 'decimal:2',
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

    public function venue(): BelongsTo
    {
        return $this->belongsTo(EventVenue::class, 'venue_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class, 'room_id');
    }

    // Scopes

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeWithCapacityAtLeast($query, int $capacity)
    {
        return $query->where('capacity', '>=', $capacity);
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        $parts = [$this->venue->name];

        if ($this->floor) {
            $parts[] = $this->floor;
        }

        $parts[] = $this->name;

        return implode(' - ', $parts);
    }

    public function getSessionsCountAttribute(): int
    {
        return $this->sessions()->count();
    }

    // Methods

    public function hasEquipment(string $item): bool
    {
        return in_array($item, $this->equipment ?? []);
    }

    public function supportsLayout(string $layout): bool
    {
        return in_array($layout, $this->layout_options ?? []);
    }

    public function isAvailableAt(\DateTime $startTime, \DateTime $endTime, ?int $excludeSessionId = null): bool
    {
        $query = $this->sessions()
            ->where(function ($q) use ($startTime, $endTime): void {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime): void {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return !$query->exists();
    }
}
