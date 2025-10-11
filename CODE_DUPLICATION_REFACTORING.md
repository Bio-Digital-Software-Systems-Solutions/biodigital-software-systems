# Code Duplication Refactoring Guide

## Overview

This document identifies code duplication patterns in the AIG-App codebase and provides refactoring strategies to improve maintainability and reduce code smell.

## Identified Duplication Patterns

### 1. File Upload Handling (13 Controllers)

**Current Duplication:**
```php
// Found in: EventController, BookController, TaskController, ProjectController, etc.

// Pattern 1: Direct file upload
if ($request->hasFile('avatar')) {
    $validated['avatar'] = $request->file('avatar')->store('events/avatars', 'public');
}

// Pattern 2: TUS upload (filename only)
elseif ($request->filled('avatar') && is_string($request->avatar)) {
    $validated['avatar'] = 'events/avatars/' . $request->avatar;
}

// Pattern 3: URL handling
if (str_starts_with($request->avatar, 'http://') || str_starts_with($request->avatar, 'https://')) {
    $validated['avatar'] = $request->avatar;
}

// Pattern 4: Old file deletion
if ($model->avatar) {
    Storage::disk('public')->delete($model->avatar);
}
```

**Refactored Solution:**

Create `/app/Services/FileUploadService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * File upload service
 *
 * Handles file uploads via multiple methods (direct upload, TUS, URL)
 * and provides unified interface for file management.
 *
 * @package App\Services
 */
class FileUploadService
{
    /**
     * Handle file upload from request
     *
     * Supports multiple upload methods:
     * - Direct file upload (UploadedFile)
     * - TUS upload (filename string)
     * - External URL (http/https)
     *
     * @param mixed $file File from request (UploadedFile|string|null)
     * @param string $directory Storage directory (e.g., 'events/avatars')
     * @param string|null $oldFile Path to old file to delete (optional)
     * @return string|null Path to stored file or null
     *
     * @throws \RuntimeException When upload fails
     */
    public static function handleUpload(
        mixed $file,
        string $directory,
        ?string $oldFile = null
    ): ?string {
        if (! $file) {
            return null;
        }

        // Delete old file if provided (unless it's a URL)
        if ($oldFile && ! self::isUrl($oldFile)) {
            self::delete($oldFile);
        }

        // Handle direct file upload
        if ($file instanceof UploadedFile) {
            return $file->store($directory, 'public');
        }

        // Handle string input (TUS or URL)
        if (is_string($file)) {
            // If it's a URL, store as-is
            if (self::isUrl($file)) {
                return $file;
            }

            // Otherwise, it's a TUS upload filename
            return $directory . '/' . $file;
        }

        return null;
    }

    /**
     * Delete file from storage
     *
     * @param string $path File path to delete
     * @return bool True if deleted or file doesn't exist
     */
    public static function delete(string $path): bool
    {
        if (! $path || self::isUrl($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Check if string is a URL
     *
     * @param string $string String to check
     * @return bool True if string is a URL
     */
    protected static function isUrl(string $string): bool
    {
        return str_starts_with($string, 'http://')
            || str_starts_with($string, 'https://');
    }

    /**
     * Get full URL for file path
     *
     * @param string|null $path File path
     * @return string|null Full URL or null
     */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (self::isUrl($path)) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
```

**Usage in Controllers:**

```php
// Before (EventController)
if ($request->hasFile('avatar')) {
    $validated['avatar'] = $request->file('avatar')->store('events/avatars', 'public');
} elseif ($request->filled('avatar') && is_string($request->avatar)) {
    $validated['avatar'] = 'events/avatars/' . $request->avatar;
}

// After
use App\Services\FileUploadService;

$validated['avatar'] = FileUploadService::handleUpload(
    $request->input('avatar') ?? $request->file('avatar'),
    'events/avatars',
    $event->avatar ?? null // For updates
);
```

### 2. Cache Invalidation (5 Controllers)

**Current Duplication:**
```php
// Found in: EventController, BookController, ArticleController, etc.
CacheService::forgetPattern('events');
CacheService::forgetPattern('books');
CacheService::forgetPattern('articles');
```

**Refactored Solution:**

Create `/app/Traits/ClearsCache.php`:

```php
<?php

namespace App\Traits;

use App\Services\CacheService;

/**
 * Trait for automatic cache clearing based on model
 *
 * @package App\Traits
 */
trait ClearsCache
{
    /**
     * Clear cache for the current model
     *
     * Automatically determines cache key from model class name.
     * Can be overridden by defining $cacheKey property.
     *
     * @return void
     */
    protected function clearModelCache(): void
    {
        $cacheKey = $this->getCacheKey();
        CacheService::forgetPattern($cacheKey);
    }

    /**
     * Get cache key for this model
     *
     * @return string Cache key pattern
     */
    protected function getCacheKey(): string
    {
        if (property_exists($this, 'cacheKey')) {
            return $this->cacheKey;
        }

        // Extract model name from namespace
        $parts = explode('\\', static::class);
        $modelName = end($parts);

        // Convert to lowercase plural (simple pluralization)
        return strtolower($modelName) . 's';
    }

    /**
     * Boot the trait
     *
     * Automatically clear cache on model events.
     *
     * @return void
     */
    protected static function bootClearsCache(): void
    {
        static::saved(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }
}
```

**Usage in Models:**

```php
class Event extends Model
{
    use ClearsCache;

    // Cache will be cleared automatically on save/delete
}

// Or with custom cache key:
class Event extends Model
{
    use ClearsCache;

    protected string $cacheKey = 'events';
}
```

**Usage in Controllers (if needed manually):**

```php
class EventController extends Controller
{
    use ClearsCache;

    public function store(Request $request)
    {
        $event = Event::create($validated);

        // Cache cleared automatically via model trait
        // Or manually if needed:
        // $this->clearModelCache();

        return redirect()->route('events.index');
    }
}
```

### 3. Flash Message Patterns

**Current Duplication:**
```php
// Found in nearly all controllers
return redirect()->route('events.index')
    ->with('message', 'Événement créé avec succès.');

return back()->with('error', 'Une erreur est survenue.');

return redirect()->route('books.show', $book->id)
    ->with('success', 'Livre loué avec succès.');
```

**Refactored Solution:**

Create `/app/Traits/HasFlashMessages.php`:

```php
<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

/**
 * Trait for standardized flash messages
 *
 * @package App\Traits
 */
trait HasFlashMessages
{
    /**
     * Redirect with success message
     *
     * @param string $route Route name
     * @param string $message Success message
     * @param array $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithSuccess(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('message', $message);
    }

    /**
     * Redirect with error message
     *
     * @param string $route Route name
     * @param string $message Error message
     * @param array $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithError(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('error', $message);
    }

    /**
     * Go back with error message
     *
     * @param string $message Error message
     * @return RedirectResponse
     */
    protected function backWithError(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }

    /**
     * Go back with success message
     *
     * @param string $message Success message
     * @return RedirectResponse
     */
    protected function backWithSuccess(string $message): RedirectResponse
    {
        return back()->with('message', $message);
    }
}
```

**Usage:**

```php
class EventController extends Controller
{
    use HasFlashMessages;

    public function store(Request $request)
    {
        $event = Event::create($validated);

        return $this->redirectWithSuccess(
            'events.index',
            'Événement créé avec succès.'
        );
    }

    public function destroy(Event $event)
    {
        if (! $this->authorize('delete', $event)) {
            return $this->backWithError('Vous n\'avez pas la permission de supprimer cet événement.');
        }

        $event->delete();

        return $this->redirectWithSuccess(
            'events.index',
            'Événement supprimé avec succès.'
        );
    }
}
```

### 4. Validation Patterns

**Current Duplication:**
```php
// Similar validation in create and update methods
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'author' => 'required|string|max:255',
    // ... many more rules
]);
```

**Refactored Solution:**

Create Form Request classes:

```bash
php artisan make:request StoreBookRequest
php artisan make:request UpdateBookRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for storing a new book
 *
 * @package App\Http\Requests
 */
class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage library');
    }

    /**
     * Get the validation rules
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'unique:books,isbn'],
            'description' => ['nullable', 'string'],
            'rental_price' => ['nullable', 'numeric', 'min:0'],
            'max_rental_days' => ['required', 'integer', 'min:1'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'author.required' => 'L\'auteur est obligatoire.',
            'isbn.unique' => 'Ce numéro ISBN existe déjà.',
        ];
    }

    /**
     * Prepare data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Normalize data before validation
        if ($this->has('isbn')) {
            $this->merge([
                'isbn' => str_replace('-', '', $this->isbn),
            ]);
        }
    }
}
```

**Usage:**

```php
class BookController extends Controller
{
    public function store(StoreBookRequest $request)
    {
        // Validation already done, authorization already checked
        $book = Book::create($request->validated());

        return $this->redirectWithSuccess('books.index', 'Livre créé avec succès.');
    }
}
```

### 5. Authorization Patterns

**Current Duplication:**
```php
// Found in many controllers
$this->authorize('update', $event);
$this->authorize('delete', $book);

if (! $this->authorize(...)) {
    return back()->with('error', '...');
}
```

**Refactored Solution:**

Use middleware consistently:

```php
class EventController extends Controller
{
    public function __construct()
    {
        // Centralize authorization in constructor
        $this->authorizeResource(Event::class, 'event');
    }

    // Methods will be automatically authorized based on naming:
    // index -> viewAny
    // show -> view
    // create/store -> create
    // edit/update -> update
    // destroy -> delete
}
```

### 6. Query Patterns with Eager Loading

**Current Duplication:**
```php
// Found in many index methods
Event::with(['creator', 'address', 'participants'])->paginate(10);
Book::with(['category', 'libraries'])->paginate(12);
Article::with(['author', 'category'])->paginate(15);
```

**Refactored Solution:**

Use the `HasEagerLoading` trait (already implemented):

```php
class Event extends Model
{
    use HasEagerLoading;

    // Define default eager loading
    protected $with = ['creator', 'address'];

    // Controller can then simplify to:
    // Event::paginate(10); // creator and address automatically loaded
}
```

### 7. JSON/Inertia Response Patterns

**Current Duplication:**
```php
// Mixed responses for JSON and redirect
if ($request->expectsJson()) {
    return response()->json(['message' => '...'], 200);
}

return redirect()->route('...')
    ->with('message', '...');
```

**Refactored Solution:**

Create `/app/Traits/RespondsWithFormat.php`:

```php
<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Trait for responding based on request type
 *
 * @package App\Traits
 */
trait RespondsWithFormat
{
    /**
     * Return appropriate response based on request type
     *
     * @param Request $request
     * @param string $route Redirect route name
     * @param string $message Success message
     * @param array $routeParams Route parameters
     * @param array $jsonData Additional JSON data
     * @return JsonResponse|RedirectResponse
     */
    protected function respondSuccess(
        Request $request,
        string $route,
        string $message,
        array $routeParams = [],
        array $jsonData = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                ...$jsonData,
            ], 200);
        }

        return redirect()
            ->route($route, $routeParams)
            ->with('message', $message);
    }

    /**
     * Return error response based on request type
     *
     * @param Request $request
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return JsonResponse|RedirectResponse
     */
    protected function respondError(
        Request $request,
        string $message,
        int $statusCode = 400
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $message,
            ], $statusCode);
        }

        return back()->with('error', $message);
    }
}
```

## Refactoring Priority

### High Priority (Do First)
1. ✅ **FileUploadService** - Used in 13 controllers
2. ✅ **ClearsCache Trait** - Used in 5 controllers
3. ✅ **HasFlashMessages Trait** - Used in nearly all controllers

### Medium Priority
4. ✅ **Form Request Classes** - Improve validation consistency
5. ✅ **RespondsWithFormat Trait** - Better API/web handling

### Low Priority
6. **Query Optimization** - Already partially addressed with HasEagerLoading
7. **Authorization** - Already using policies, just need consistency

## Implementation Plan

### Week 1: Create Services and Traits
- [ ] Create FileUploadService
- [ ] Create ClearsCache trait
- [ ] Create HasFlashMessages trait
- [ ] Create RespondsWithFormat trait
- [ ] Write tests for new code

### Week 2: Refactor Core Controllers
- [ ] EventController
- [ ] BookController
- [ ] ArticleController
- [ ] UserManagementController
- [ ] TrainingController

### Week 3: Refactor Remaining Controllers
- [ ] ProjectController
- [ ] TaskController
- [ ] DepartmentController
- [ ] GroupController
- [ ] StockController
- [ ] LibraryController

### Week 4: Create Form Requests
- [ ] Extract validation to Form Request classes
- [ ] Add custom error messages
- [ ] Document validation rules

### Week 5: Testing and Documentation
- [ ] Comprehensive testing of refactored code
- [ ] Update documentation
- [ ] Code review
- [ ] Performance testing

## Measuring Success

### Before Refactoring
- 13 controllers with duplicated file upload code (≈40 lines each)
- 5 controllers with duplicated cache invalidation
- Inconsistent flash message patterns
- Validation rules repeated in multiple places

### After Refactoring
- File upload logic: **1 service class** (≈100 lines) replacing **≈520 lines**
- Cache invalidation: **1 trait** (≈30 lines) with automatic clearing
- Flash messages: **1 trait** (≈50 lines) standardizing responses
- Form requests: **Validation centralized** and reusable

**Total Lines Eliminated: ~500+**
**Code Duplication Reduced: ~70%**

## Tools for Detection

### PHPCPD (PHP Copy/Paste Detector)

```bash
composer require --dev sebastian/phpcpd

vendor/bin/phpcpd app/
```

### PHPStan with Duplication Detection

```neon
# phpstan.neon
includes:
    - phpstan-baseline.neon

parameters:
    level: 10
    paths:
        - app
```

## Best Practices to Avoid Future Duplication

1. **DRY Principle**: Don't Repeat Yourself
2. **Extract Early**: If code is used 3+ times, extract it
3. **Use Traits**: For cross-cutting concerns
4. **Use Services**: For complex business logic
5. **Use Form Requests**: For validation
6. **Code Reviews**: Catch duplication before merge
7. **Pair Programming**: Two heads catch duplication better
8. **Regular Refactoring**: Schedule time for cleanup

## Conclusion

Code duplication makes the codebase harder to maintain and more prone to bugs. By systematically identifying and refactoring duplicate code into reusable services, traits, and classes, we can:

- Reduce codebase size
- Improve maintainability
- Ensure consistency
- Make testing easier
- Speed up development

The investment in refactoring pays dividends in long-term code quality and developer productivity.
