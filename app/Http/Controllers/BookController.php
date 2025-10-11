<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRental;
use App\Models\Category;
use App\Models\Library;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view books')->only(['index', 'show']);
        $this->middleware('can:manage library')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('can:rent books')->only(['rent']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Book::with(['category', 'libraries']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('author', 'like', "%{$request->search}%")
                    ->orWhere('isbn', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        $books = $query->latest()->paginate(12);

        // Cache book categories (1 hour cache)
        $categories = CacheService::remember(
            'books.categories',
            fn() => Category::where('type', 'book')->get(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Books/Index', [
            'books' => $books,
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Cache book categories (1 hour cache)
        $categories = CacheService::remember(
            'books.categories',
            fn() => Category::where('type', 'book')->get(),
            CacheService::MEDIUM_CACHE
        );

        // Cache libraries list (1 hour cache)
        $libraries = CacheService::remember(
            'books.libraries',
            fn() => Library::all(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Books/Create', [
            'categories' => $categories,
            'libraries' => $libraries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn',
            'description' => 'nullable|string',
            'rental_price' => 'nullable|numeric|min:0',
            'max_rental_days' => 'required|integer|min:1',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Validate cover_image based on its type (file or string)
        if ($request->hasFile('cover_image')) {
            $request->validate([
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            ]);
        } elseif ($request->filled('cover_image')) {
            $request->validate([
                'cover_image' => 'nullable|string|max:2048',
            ]);
        }

        // Handle cover_image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }
        // Handle cover_image from URL or TUS upload
        elseif ($request->filled('cover_image') && is_string($request->cover_image)) {
            // If it's a URL (starts with http:// or https://), store it as-is
            if (str_starts_with($request->cover_image, 'http://') || str_starts_with($request->cover_image, 'https://')) {
                $validated['cover_image'] = $request->cover_image;
            }
            // Otherwise, it's a TUS upload (just filename)
            else {
                // Cover image has already been uploaded via TUS to books/covers directory
                $validated['cover_image'] = 'books/covers/' . $request->cover_image;
            }
        }

        $book = Book::create($validated);

        // Invalidate books cache
        CacheService::forgetPattern('books');

        return redirect()->route('books.index')
            ->with('message', 'Livre ajouté avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): Response
    {
        $book->load(['category', 'libraries']);
        $activeRentals = BookRental::where('book_id', $book->id)
            ->where('status', 'active')
            ->with(['user', 'library'])
            ->get();

        return Inertia::render('Books/Show', [
            'book' => $book,
            'activeRentals' => $activeRentals,
            'canRent' => auth()->user()->can('rent books'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): Response
    {
        $book->load('category');

        // Cache book categories (1 hour cache)
        $categories = CacheService::remember(
            'books.categories',
            fn() => Category::where('type', 'book')->get(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Books/Edit', [
            'book' => $book,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn,'.$book->id,
            'description' => 'nullable|string',
            'rental_price' => 'nullable|numeric|min:0',
            'max_rental_days' => 'required|integer|min:1',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Validate cover_image based on its type (file or string)
        if ($request->hasFile('cover_image')) {
            $request->validate([
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            ]);
        } elseif ($request->filled('cover_image')) {
            $request->validate([
                'cover_image' => 'nullable|string|max:2048',
            ]);
        }

        // Handle cover_image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover_image if it exists and it's not a URL
            if ($book->cover_image && !str_starts_with($book->cover_image, 'http://') && !str_starts_with($book->cover_image, 'https://')) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }
        // Handle cover_image from URL or TUS upload
        elseif ($request->filled('cover_image') && is_string($request->cover_image)) {
            // If it's a URL (starts with http:// or https://), store it as-is
            if (str_starts_with($request->cover_image, 'http://') || str_starts_with($request->cover_image, 'https://')) {
                // Delete old cover_image if it exists and it's not a URL
                if ($book->cover_image && !str_starts_with($book->cover_image, 'http://') && !str_starts_with($book->cover_image, 'https://')) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                $validated['cover_image'] = $request->cover_image;
            }
            // Otherwise, it's a TUS upload (just filename)
            else {
                // Delete old cover_image if it exists and it's not a URL
                if ($book->cover_image && !str_starts_with($book->cover_image, 'http://') && !str_starts_with($book->cover_image, 'https://')) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                // Cover image has already been uploaded via TUS to books/covers directory
                $validated['cover_image'] = 'books/covers/' . $request->cover_image;
            }
        }

        $book->update($validated);

        // Invalidate books cache
        CacheService::forgetPattern('books');

        return redirect()->route('books.index')
            ->with('message', 'Livre mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $book);

        // Delete cover_image if it exists and it's not a URL
        if ($book->cover_image && !str_starts_with($book->cover_image, 'http://') && !str_starts_with($book->cover_image, 'https://')) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();

        // Invalidate books cache
        CacheService::forgetPattern('books');

        // If the request expects JSON (from axios), return JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Livre supprimé avec succès.'
            ], 200);
        }

        // Otherwise, return a redirect (for traditional form submissions)
        return redirect()->route('books.index')
            ->with('message', 'Livre supprimé avec succès.');
    }

    /**
     * Rent a book
     */
    public function rent(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('rent', $book);

        $validated = $request->validate([
            'library_id' => 'required|exists:libraries,id',
            'rental_days' => 'required|integer|min:1|max:'.$book->max_rental_days,
        ]);

        // Check if book is available
        $activeRentals = BookRental::where('book_id', $book->id)
            ->where('status', 'active')
            ->count();

        if ($activeRentals >= $book->stock_quantity) {
            return back()->with('error', 'Ce livre n\'est plus disponible à la location.');
        }

        // Check if user already has this book rented
        $existingRental = BookRental::where('book_id', $book->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        if ($existingRental) {
            return back()->with('error', 'Vous avez déjà loué ce livre.');
        }

        // Verify that the library has this book
        if (! $book->libraries()->where('library_id', $validated['library_id'])->exists()) {
            return back()->withErrors(['library_id' => 'Ce livre n\'est pas disponible dans cette bibliothèque.']);
        }

        $rentalDate = now();
        $dueDate = $rentalDate->copy()->addDays($validated['rental_days']);
        $rentalFee = ($book->rental_price ?? 0) * $validated['rental_days'];

        try {
            BookRental::create([
                'book_id' => $book->id,
                'user_id' => Auth::id(),
                'library_id' => $validated['library_id'],
                'rental_date' => $rentalDate,
                'due_date' => $dueDate,
                'rental_fee' => $rentalFee,
                'late_fee' => 0,
                'status' => 'active',
            ]);

            return redirect()->route('books.show', $book->id)
                ->with('success', 'Livre loué avec succès jusqu\'au '.$dueDate->format('d/m/Y').'.');
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la location. Veuillez réessayer.');
        }
    }
}
