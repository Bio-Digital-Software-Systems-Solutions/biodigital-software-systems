# PHPDoc Standards and Guidelines

## Overview

This document establishes PHPDoc standards for the AIG-App codebase to ensure consistent, comprehensive, and useful code documentation.

## Why PHPDoc?

1. **IDE Support**: Enhanced autocomplete and type hints
2. **Static Analysis**: Better PHPStan/Psalm analysis
3. **Documentation Generation**: Can generate API documentation
4. **Team Communication**: Clarifies intent and usage
5. **Maintainability**: Easier to understand code months/years later

## General Standards

### Basic Structure

```php
/**
 * Short description (one line, ending with period).
 *
 * Long description providing more detail about the method/class.
 * Can span multiple lines and paragraphs.
 *
 * @param Type $paramName Description of parameter
 * @return Type Description of return value
 * @throws ExceptionType Description of when this exception is thrown
 */
```

### Required Elements

✅ **Always include:**
- Short description
- `@param` for each parameter
- `@return` for methods that return values
- `@throws` for methods that throw exceptions

❌ **Avoid:**
- Obvious comments ("Gets the user" for `getUser()`)
- Outdated documentation
- Redundant information already in type hints

## Class Documentation

### Class-Level Documentation

```php
/**
 * Book management controller
 *
 * Handles CRUD operations for books in the library system,
 * including rental management and availability tracking.
 *
 * @package App\Http\Controllers
 * @author Your Name <your.email@example.com>
 * @since 1.0.0
 */
class BookController extends Controller
{
    // ...
}
```

### Model Documentation

```php
/**
 * Book model
 *
 * Represents a book in the library system with rental tracking
 * and availability management.
 *
 * @property int $id
 * @property string $title
 * @property string $author
 * @property string|null $isbn
 * @property string|null $description
 * @property float|null $rental_price
 * @property int $max_rental_days
 * @property int $stock_quantity
 * @property int|null $category_id
 * @property string|null $cover_image
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Library[] $libraries
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BookRental[] $rentals
 * @property-read int|null $rentals_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Book whereTitleLike(string $search)
 * @method static \Illuminate\Database\Eloquent\Builder|Book available()
 *
 * @package App\Models
 */
class Book extends Model
{
    // ...
}
```

## Method Documentation

### Controller Methods

#### Index Method
```php
/**
 * Display a paginated list of books with filtering and search
 *
 * This method retrieves books from the database with optional filtering
 * by category and search terms. Results are paginated and cached for
 * performance. Categories are also loaded and cached separately.
 *
 * @param \Illuminate\Http\Request $request HTTP request with optional search and category filters
 * @return \Inertia\Response Inertia response rendering the Books/Index page
 *
 * @example
 * GET /books?search=laravel&category=1
 */
public function index(Request $request): Response
{
    // Implementation
}
```

#### Store Method
```php
/**
 * Store a newly created book in the database
 *
 * Validates the incoming request data and creates a new book record.
 * Handles cover image upload via multiple methods (direct upload, TUS, or URL).
 * Invalidates the books cache after successful creation.
 *
 * @param \Illuminate\Http\Request $request HTTP request containing book data
 * @return \Illuminate\Http\RedirectResponse Redirect to books index with success message
 *
 * @throws \Illuminate\Validation\ValidationException When validation fails
 * @throws \Illuminate\Database\QueryException When database operation fails
 *
 * @example
 * POST /books
 * {
 *     "title": "Clean Code",
 *     "author": "Robert Martin",
 *     "isbn": "9780132350884",
 *     "rental_price": 5.00,
 *     "max_rental_days": 14,
 *     "stock_quantity": 3
 * }
 */
public function store(Request $request): RedirectResponse
{
    // Implementation
}
```

#### Update Method
```php
/**
 * Update an existing book in the database
 *
 * Validates and updates the book record. Authorization is checked via policy.
 * Handles cover image replacement, including cleanup of old images.
 * Invalidates relevant caches after successful update.
 *
 * @param \Illuminate\Http\Request $request HTTP request containing updated book data
 * @param \App\Models\Book $book The book to update (route model binding)
 * @return \Illuminate\Http\RedirectResponse Redirect to books index with success message
 *
 * @throws \Illuminate\Auth\Access\AuthorizationException When user lacks permission
 * @throws \Illuminate\Validation\ValidationException When validation fails
 * @throws \Illuminate\Database\QueryException When database operation fails
 */
public function update(Request $request, Book $book): RedirectResponse
{
    // Implementation
}
```

### Service Methods

```php
/**
 * Cache a value with automatic key generation
 *
 * This method wraps Laravel's cache remember functionality with
 * automatic key prefixing and configurable TTL.
 *
 * @param string $key Cache key (will be prefixed)
 * @param callable $callback Callback to execute if cache misses
 * @param int $ttl Time to live in seconds (default: 1 hour)
 * @return mixed Cached or freshly computed value
 *
 * @throws \RuntimeException When cache driver is unavailable
 *
 * @example
 * $users = CacheService::remember(
 *     'active.users',
 *     fn() => User::where('active', true)->get(),
 *     3600
 * );
 */
public static function remember(string $key, callable $callback, int $ttl = self::MEDIUM_CACHE): mixed
{
    // Implementation
}
```

### Repository/Model Methods

```php
/**
 * Scope query to only include available books
 *
 * A book is considered available if it has stock quantity greater than
 * the number of active rentals.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 *
 * @example
 * $availableBooks = Book::available()->get();
 */
public function scopeAvailable(Builder $query): Builder
{
    return $query->whereRaw('stock_quantity > (
        SELECT COUNT(*) FROM book_rentals
        WHERE book_id = books.id
        AND status = "active"
    )');
}
```

### API Resource Methods

```php
/**
 * Transform the book into an array for JSON response
 *
 * Formats the book data for API consumption, including computed
 * properties and related data.
 *
 * @param \Illuminate\Http\Request $request
 * @return array<string, mixed> Formatted book data
 *
 * @example
 * return BookResource::collection(Book::paginate());
 */
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'author' => $this->author,
        'isbn' => $this->isbn,
        'description' => $this->description,
        'rental_price' => $this->rental_price,
        'max_rental_days' => $this->max_rental_days,
        'stock_quantity' => $this->stock_quantity,
        'available_quantity' => $this->getAvailableQuantity(),
        'category' => new CategoryResource($this->whenLoaded('category')),
        'cover_image_url' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
        'is_available' => $this->isAvailable(),
        'created_at' => $this->created_at->toIso8601String(),
        'updated_at' => $this->updated_at->toIso8601String(),
    ];
}
```

## Tag Reference

### Core Tags

#### @param
```php
/**
 * @param Type $name Description
 * @param Type|null $optional Description (optional)
 * @param array<Type> $items Description of array items
 * @param array{key: Type, key2: Type} $shaped Description of shaped array
 */
```

#### @return
```php
/**
 * @return Type Description
 * @return Type|null Description (nullable return)
 * @return void No return value
 * @return never Method never returns (throws or exits)
 * @return array<Type> Array of Type
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Book> Collection
 */
```

#### @throws
```php
/**
 * @throws ExceptionType When this condition occurs
 * @throws \Illuminate\Auth\Access\AuthorizationException When user lacks permission
 * @throws \Illuminate\Validation\ValidationException When validation fails
 */
```

### Additional Tags

#### @var
```php
/**
 * @var Type $variable Description
 */

// For class properties
class Book extends Model
{
    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rental_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
```

#### @property (for magic properties)
```php
/**
 * @property int $id Primary key
 * @property string $title Book title
 * @property-read \App\Models\User $author Relationship: belongs to User
 * @property-write string $password Write-only password property
 */
```

#### @method (for magic methods)
```php
/**
 * @method static \Illuminate\Database\Eloquent\Builder whereTitleLike(string $search)
 * @method static self create(array $attributes = [])
 * @method \Illuminate\Database\Eloquent\Collection get(array $columns = ['*'])
 */
```

#### @deprecated
```php
/**
 * Get book by ISBN
 *
 * @param string $isbn
 * @return \App\Models\Book|null
 *
 * @deprecated since 2.0, use findByIsbn() instead
 * @see findByIsbn()
 */
public function getBookByIsbn(string $isbn): ?Book
{
    // Implementation
}
```

#### @see
```php
/**
 * @see https://docs.example.com/books Link to external documentation
 * @see \App\Services\BookService Related service class
 * @see rentBook() Related method
 */
```

#### @todo
```php
/**
 * Calculate late fees for overdue rentals
 *
 * @todo Implement tiered late fee system
 * @todo Send automatic reminders
 */
```

## Type Hints

### Primitive Types
```php
/**
 * @param string $name
 * @param int $count
 * @param float $price
 * @param bool $isActive
 * @param array $items
 * @param object $data
 * @param resource $handle
 * @param callable $callback
 * @param mixed $anything
 */
```

### Compound Types
```php
/**
 * @param string|int $id Can be string UUID or integer ID
 * @param User|null $user User object or null
 * @param array<string, mixed> $config Associative array
 * @param array<int, Book> $books Indexed array of Book objects
 * @param Collection<int, User> $users Collection of Users
 */
```

### Laravel-Specific Types
```php
/**
 * @param \Illuminate\Http\Request $request
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @param \Illuminate\Database\Eloquent\Collection<int, \App\Models\Book> $books
 * @param \Illuminate\Http\JsonResponse $response
 * @param \Illuminate\Http\RedirectResponse $redirect
 * @param \Inertia\Response $inertia
 * @param \Carbon\Carbon $date
 */
```

## Special Cases

### Closures/Callbacks
```php
/**
 * Execute callback with transaction
 *
 * @param callable(): mixed $callback Callback to execute in transaction
 * @param callable(Exception): void $onError Optional error handler
 * @return mixed Result from callback
 */
public function transaction(callable $callback, ?callable $onError = null): mixed
{
    // Implementation
}
```

### Generics (PHPStan/Psalm)
```php
/**
 * Get items from cache or compute
 *
 * @template T
 * @param string $key
 * @param callable(): T $callback
 * @return T
 */
function remember(string $key, callable $callback)
{
    // Implementation
}
```

### Variadic Parameters
```php
/**
 * Log multiple messages
 *
 * @param string ...$messages Variable number of message strings
 * @return void
 */
public function logMultiple(string ...$messages): void
{
    // Implementation
}
```

## Best Practices

### 1. Be Specific, Not Obvious

❌ **Bad:**
```php
/**
 * Get user
 *
 * @param int $id
 * @return User
 */
public function getUser(int $id): User
```

✅ **Good:**
```php
/**
 * Retrieve user by ID with their active roles and permissions
 *
 * Eager loads the user's roles and permissions to prevent N+1 queries.
 * Throws exception if user is not found.
 *
 * @param int $id User ID
 * @return \App\Models\User User with loaded roles and permissions
 *
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When user not found
 */
public function getUser(int $id): User
```

### 2. Document Side Effects

```php
/**
 * Delete book and clean up related data
 *
 * WARNING: This method performs several operations:
 * - Deletes the book record
 * - Removes cover image from storage
 * - Cancels all active rentals
 * - Invalidates related caches
 * - Logs activity for audit trail
 *
 * @param \App\Models\Book $book Book to delete
 * @return bool True if deletion successful
 *
 * @throws \Exception When deletion fails
 */
public function delete(Book $book): bool
```

### 3. Include Examples

```php
/**
 * Search books by multiple criteria
 *
 * @param array{
 *     title?: string,
 *     author?: string,
 *     category?: int,
 *     available?: bool
 * } $criteria Search criteria
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Book>
 *
 * @example
 * $books = $repository->search([
 *     'title' => 'Laravel',
 *     'available' => true,
 * ]);
 */
public function search(array $criteria): Collection
```

### 4. Document Permissions

```php
/**
 * Update book details
 *
 * Requires 'manage library' permission or ownership of the book.
 *
 * @param \App\Models\Book $book
 * @param array $data
 * @return \App\Models\Book
 *
 * @throws \Illuminate\Auth\Access\AuthorizationException When user lacks permission
 *
 * @permission manage library
 * @permission owner
 */
public function update(Book $book, array $data): Book
```

### 5. Document Complex Logic

```php
/**
 * Calculate late fee for overdue rental
 *
 * Late fee calculation:
 * - Days 1-7: $1/day
 * - Days 8-14: $2/day
 * - Days 15+: $5/day + replacement fee
 *
 * Maximum fee is capped at 2x the book's replacement value.
 *
 * @param \App\Models\BookRental $rental
 * @return float Late fee amount in dollars
 */
public function calculateLateFee(BookRental $rental): float
```

## Tools and Automation

### Generate PHPDoc Stubs

Use IDE features to generate PHPDoc stubs:

**PHPStorm:**
- Type `/**` above a method and press Enter
- Generates template based on method signature

**VS Code (with PHP Intelephense):**
- Type `/**` and press Enter
- Auto-generates @param and @return tags

### Static Analysis

Configure PHPStan to enforce documentation:

```neon
# phpstan.neon
parameters:
    level: 10
    checkMissingDocComments: true
    checkPhpDocMissingReturn: true
```

### Laravel IDE Helper

Generate PHPDoc for Laravel facades:

```bash
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

## Quality Checklist

Before committing, verify:

- [ ] All classes have class-level PHPDoc
- [ ] All public methods have PHPDoc
- [ ] All parameters are documented
- [ ] Return types are documented
- [ ] Exceptions are documented
- [ ] Complex logic has explanatory comments
- [ ] Magic properties/methods are documented
- [ ] No outdated or incorrect documentation

## Migration Strategy

### Phase 1: Core Models (Week 1)
- Document all model classes
- Add @property tags for attributes
- Add @property-read for relationships
- Add @method tags for scopes

### Phase 2: Controllers (Week 2-3)
- Document all controller methods
- Focus on public API endpoints first
- Include permission requirements
- Add request/response examples

### Phase 3: Services (Week 4)
- Document service classes
- Explain business logic
- Document side effects
- Include usage examples

### Phase 4: Helpers & Utilities (Week 5)
- Document helper functions
- Document traits
- Document middleware
- Document custom classes

### Phase 5: Polish (Week 6)
- Review and update existing docs
- Ensure consistency
- Add missing examples
- Run PHPStan validation

## Resources

- [PHPDoc Documentation](https://docs.phpdoc.org/)
- [PSR-5 (Draft)](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md)
- [PHPStan Type System](https://phpstan.org/writing-php-code/phpdoc-types)
- [Laravel API Documentation](https://laravel.com/api/)

## Conclusion

Comprehensive PHPDoc comments are an investment in code quality that pays dividends through:
- Improved IDE support
- Better static analysis
- Easier onboarding for new developers
- Reduced maintenance burden
- Professional codebase presentation

By following these standards consistently, the AIG-App codebase will become more maintainable, understandable, and professional.
