<?php

namespace App\Models\Event;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property int|null $total_capacity
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $website
 * @property array<array-key, mixed>|null $amenities
 * @property array<array-key, mixed>|null $images
 * @property string|null $access_instructions
 * @property string|null $parking_info
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read int $available_rooms_count
 * @property-read string $full_address
 * @property-read int|null $rooms_count
 * @property-read int $total_room_capacity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event\VenueRoom> $rooms
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue inCity(string $city)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereAccessInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereAmenities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereParkingInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereTotalCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue withCapacityAtLeast(int $capacity)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventVenue withoutTrashed()
 * @mixin \Eloquent
 */
class EventVenue extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'total_capacity',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'amenities',
        'images',
        'access_instructions',
        'parking_info',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_capacity' => 'integer',
            'amenities' => 'array',
            'images' => 'array',
            'is_active' => 'boolean',
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

    public function rooms(): HasMany
    {
        return $this->hasMany(VenueRoom::class, 'venue_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeWithCapacityAtLeast($query, int $capacity)
    {
        return $query->where('total_capacity', '>=', $capacity);
    }

    // Accessors

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line1,
            $this->address_line2,
            $this->postal_code.' '.$this->city,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    public function getRoomsCountAttribute(): int
    {
        return $this->rooms()->count();
    }

    public function getAvailableRoomsCountAttribute(): int
    {
        return $this->rooms()->where('is_available', true)->count();
    }

    public function getTotalRoomCapacityAttribute(): int
    {
        return (int) ($this->rooms()->sum('capacity') ?? 0);
    }

    // Methods

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getGoogleMapsUrl(): ?string
    {
        if ($this->hasCoordinates()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->full_address);
    }

    public function hasAmenity(string $amenity): bool
    {
        return in_array($amenity, $this->amenities ?? []);
    }
}
