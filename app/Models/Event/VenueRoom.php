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
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
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
