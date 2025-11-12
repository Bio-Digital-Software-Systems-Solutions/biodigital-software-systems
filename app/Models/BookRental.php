<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $book_id
 * @property int $user_id
 * @property int $library_id
 * @property \Illuminate\Support\Carbon $rental_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $return_date
 * @property numeric $rental_fee
 * @property numeric $late_fee
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Book $book
 * @property-read int $days_overdue
 * @property-read int $rental_duration
 * @property-read \App\Models\Library $library
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental active()
 * @method static \Database\Factories\BookRentalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental returned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereBookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereLateFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereLibraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereRentalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereUserId($value)
 * @property string $uuid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookRental whereUuid($value)
 * @mixin \Eloquent
 */
class BookRental extends Model
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
        'book_id',
        'user_id',
        'library_id',
        'rental_date',
        'due_date',
        'return_date',
        'rental_fee',
        'late_fee',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'rental_fee' => 'decimal:2',
            'late_fee' => 'decimal:2',
        ];
    }

    /**
     * Get the book that was rented.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the user who rented the book.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the library from which the book was rented.
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * Check if the rental is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->return_date === null && now()->isAfter($this->due_date);
    }

    /**
     * Check if the book has been returned.
     */
    public function isReturned(): bool
    {
        return $this->return_date !== null;
    }

    /**
     * Get the days overdue.
     */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->isReturned() || ! $this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Get the rental duration in days.
     */
    public function getRentalDurationAttribute(): int
    {
        $endDate = $this->return_date ?? now();

        return $this->rental_date->diffInDays($endDate);
    }

    /**
     * Scope a query to only include active rentals.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include returned rentals.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope a query to only include overdue rentals.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }
}
