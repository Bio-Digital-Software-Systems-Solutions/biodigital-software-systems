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
 * @property string $title
 * @property string $author
 * @property string|null $isbn
 * @property string|null $description
 * @property string|null $rental_price
 * @property int $max_rental_days
 * @property int $stock_quantity
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookRental> $bookRentals
 * @property-read int|null $book_rentals_count
 * @property-read \App\Models\Category|null $category
 * @property-read int $total_available_quantity
 * @property-read int $total_quantity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Library> $libraries
 * @property-read int|null $libraries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book byAuthor($author)
 * @method static \Database\Factories\BookFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereIsbn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereMaxRentalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereRentalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereStockQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Book whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Book extends Model
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
        'title',
        'isbn',
        'author',
        'description',
        'publication_date',
        'publisher',
        'pages',
        'language',
        'cover_image',
        'rental_price',
        'max_rental_days',
        'stock_quantity',
        'total_copies',
        'available_copies',
        'category_id',
        'library_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'pages' => 'integer',
        ];
    }

    /**
     * Get the category that owns the book.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the library that owns the book.
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * Get the book rentals for the book.
     */
    public function bookRentals(): HasMany
    {
        return $this->hasMany(BookRental::class);
    }

    /**
     * The libraries that have this book.
     */
    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'library_book')
            ->withPivot('quantity', 'available_quantity')
            ->withTimestamps();
    }

    /**
     * Get the available quantity of the book across all libraries.
     */
    public function getTotalAvailableQuantityAttribute(): int
    {
        return $this->libraries()->sum('library_book.available_quantity');
    }

    /**
     * Get the total quantity of the book across all libraries.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->libraries()->sum('library_book.quantity');
    }

    /**
     * Check if the book is available for rental.
     */
    public function isAvailable(): bool
    {
        return $this->total_available_quantity > 0;
    }

    /**
     * Scope a query to only include available books.
     */
    public function scopeAvailable($query)
    {
        return $query->whereHas('libraries', function ($q) {
            $q->where('library_book.available_quantity', '>', 0);
        });
    }

    /**
     * Scope a query to filter by author.
     */
    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', 'like', "%{$author}%");
    }
}
