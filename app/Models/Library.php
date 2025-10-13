<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property \App\Models\Address|null $address
 * @property string|null $contact_person
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property bool $is_active
 * @property int|null $address_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookRental> $bookRentals
 * @property-read int|null $book_rentals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Book> $books
 * @property-read int|null $books_count
 * @property-read int $available_books
 * @property-read string $full_address
 * @property-read float $occupancy_rate
 * @property-read int $rented_books
 * @property-read int $total_books
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library active()
 * @method static \Database\Factories\LibraryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Library whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Library extends Model
{
    use HasFactory, HasUuid, LogsActivity;

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
        'code',
        'description',
        'phone',
        'email',
        'opening_hours',
        'capacity',
        'is_active',
        'address_id',
        'image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the address of the library.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * The books that belong to this library.
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'library_book')
            ->withPivot('quantity', 'available_quantity')
            ->withTimestamps();
    }

    /**
     * Get the book rentals from this library.
     */
    public function bookRentals(): HasMany
    {
        return $this->hasMany(BookRental::class);
    }

    /**
     * Get the total number of books in this library.
     */
    public function getTotalBooksAttribute(): int
    {
        return $this->books()->sum('library_book.quantity');
    }

    /**
     * Get the total number of available books in this library.
     */
    public function getAvailableBooksAttribute(): int
    {
        return $this->books()->sum('library_book.available_quantity');
    }

    /**
     * Get the total number of rented books from this library.
     */
    public function getRentedBooksAttribute(): int
    {
        return $this->bookRentals()->active()->count();
    }

    /**
     * Get the occupancy rate of the library.
     */
    public function getOccupancyRateAttribute(): float
    {
        if ($this->capacity === 0) {
            return 0;
        }

        return ($this->rented_books / $this->capacity) * 100;
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        return $this->address ? $this->address->full_address : '';
    }

    /**
     * Check if the library is currently open.
     */
    public function isOpen(): bool
    {
        if (! $this->is_active || ! $this->opening_hours) {
            return false;
        }

        $today = now()->format('l'); // Get current day name
        $currentTime = now()->format('H:i');

        if (! isset($this->opening_hours[strtolower($today)])) {
            return false;
        }

        $hours = $this->opening_hours[strtolower($today)];

        if ($hours === 'closed') {
            return false;
        }

        [$open, $close] = explode('-', $hours);

        return $currentTime >= $open && $currentTime <= $close;
    }

    /**
     * Scope a query to only include active libraries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order libraries by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
