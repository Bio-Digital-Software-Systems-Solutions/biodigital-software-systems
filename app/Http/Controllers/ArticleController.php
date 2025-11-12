<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:create articles')->only(['create', 'store']);
        $this->middleware('can:edit articles')->only(['edit', 'update']);
        $this->middleware('can:delete articles')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Article::with(['category', 'user', 'tags'])
            ->withCount('likes');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('content', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->status) {
            if ($request->status === 'published') {
                $query->whereNotNull('published_at');
            } elseif ($request->status === 'draft') {
                $query->whereNull('published_at');
            }
        }

        // Default to published articles for guests and non-admin users
        if ((! Auth::check() || ! Auth::user()->can('edit articles')) && ! $request->status) {
            $query->whereNotNull('published_at');
        }

        $articles = $query->latest('published_at')->paginate(12)
            ->appends($request->query());

        // Cache categories (1 hour cache)
        $categories = CacheService::remember(
            'articles.categories',
            fn() => Category::all(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Articles/Index', [
            'articles' => [
                'data' => $articles->items(),
                'links' => $articles->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                ],
            ],
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Cache categories and tags (1 hour cache)
        $categories = CacheService::remember(
            'articles.categories',
            fn() => Category::all(),
            CacheService::MEDIUM_CACHE
        );

        $tags = CacheService::remember(
            'articles.tags',
            fn() => Tag::all(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Articles/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published,pending,scheduled',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2097152',
            'video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,webm|max:2097152',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_published' => 'boolean',
        ]);

        // Handle cover image upload with FileUploadService
        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $fileUploadService = new FileUploadService;
            try {
                $coverImagePath = $fileUploadService->uploadImage($request->file('cover_image'), 'articles/covers');
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['cover_image' => $e->getMessage()]);
            }
        }

        // Handle video file upload with FileUploadService
        $videoFilePath = null;
        if ($request->hasFile('video_file')) {
            $fileUploadService = new FileUploadService;
            try {
                $videoFilePath = $fileUploadService->uploadVideo($request->file('video_file'), 'articles/videos');
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['video_file' => $e->getMessage()]);
            }
        }

        // Generate slug
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Handle publication status
        $status = $validated['status'] ?? ($validated['is_published'] ?? false ? 'published' : 'draft');
        $publishedAt = ($status === 'published' || ($validated['is_published'] ?? false)) ? now() : null;

        // Sanitize HTML content to prevent XSS
        $sanitizedContent = Purifier::clean($validated['content']);

        $article = Article::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $sanitizedContent,
            'excerpt' => $validated['excerpt'] ?? null,
            'status' => $status,
            'category_id' => $validated['category_id'],
            'cover_image' => $coverImagePath,
            'video_file' => $videoFilePath,
            'user_id' => Auth::id(),
            'published_at' => $publishedAt,
            'is_featured' => false,
        ]);

        // Attach tags if provided
        if (! empty($validated['tags'])) {
            $article->tags()->attach($validated['tags']);
        }

        // Invalidate articles cache
        CacheService::forgetPattern('articles');

        return redirect()->route('articles.index')
            ->with('message', 'Article créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article): Response
    {
        // Check if article is published and not scheduled for future
        $isPublished = $article->published_at && $article->published_at->isPast();
        $canEdit = Auth::check() && Auth::user()->can('edit articles');
        $isAuthor = Auth::check() && Auth::user()->id === $article->user_id;

        if (! $isPublished && ! $canEdit && ! $isAuthor) {
            abort(403);
        }

        $article->load(['category', 'user', 'tags']);

        // Increment views count
        $article->incrementViews();

        // Get related articles
        $relatedArticles = Article::where('id', '!=', $article->id)
            ->whereNotNull('published_at')
            ->when($article->category_id, function ($query) use ($article) {
                $query->where('category_id', $article->category_id);
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        return Inertia::render('Articles/Show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'isLiked' => Auth::check() ? \Maize\Markable\Models\Like::has($article, Auth::user()) : false,
            'isFavorited' => Auth::check() ? \Maize\Markable\Models\Bookmark::has($article, Auth::user()) : false,
            'likesCount' => \Maize\Markable\Models\Like::count($article),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article): Response
    {
        $this->authorize('update', $article);

        $article->load(['category', 'tags']);

        // Cache categories and tags (1 hour cache)
        $categories = CacheService::remember(
            'articles.categories',
            fn() => Category::all(),
            CacheService::MEDIUM_CACHE
        );

        $tags = CacheService::remember(
            'articles.tags',
            fn() => Tag::all(),
            CacheService::MEDIUM_CACHE
        );

        return Inertia::render('Articles/Edit', [
            'article' => $article,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published,pending,scheduled',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2097152',
            'video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,webm|max:2097152',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_published' => 'boolean',
        ]);

        // Handle cover image upload with FileUploadService
        $coverImagePath = $article->cover_image;
        if ($request->hasFile('cover_image')) {
            // Delete old image
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $fileUploadService = new FileUploadService;
            try {
                $coverImagePath = $fileUploadService->uploadImage($request->file('cover_image'), 'articles/covers');
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['cover_image' => $e->getMessage()]);
            }
        }

        // Handle video file upload with FileUploadService
        $videoFilePath = $article->video_file;
        if ($request->hasFile('video_file')) {
            // Delete old video
            if ($article->video_file) {
                Storage::disk('public')->delete($article->video_file);
            }
            $fileUploadService = new FileUploadService;
            try {
                $videoFilePath = $fileUploadService->uploadVideo($request->file('video_file'), 'articles/videos');
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['video_file' => $e->getMessage()]);
            }
        }

        // Update slug if title changed
        $slug = $article->slug;
        if ($validated['title'] !== $article->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        // Handle publication status
        $status = $validated['status'] ?? ($validated['is_published'] ?? false ? 'published' : 'draft');
        $publishedAt = $article->published_at;

        if ($status === 'published' || ($validated['is_published'] ?? false)) {
            if (! $article->published_at) {
                $publishedAt = now();
            }
        } else {
            $publishedAt = null;
        }

        // Sanitize HTML content to prevent XSS
        $sanitizedContent = Purifier::clean($validated['content']);

        $article->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $sanitizedContent,
            'excerpt' => $validated['excerpt'] ?? $article->excerpt,
            'status' => $status,
            'category_id' => $validated['category_id'],
            'cover_image' => $coverImagePath,
            'video_file' => $videoFilePath,
            'published_at' => $publishedAt,
        ]);

        // Sync tags
        if (isset($validated['tags'])) {
            $article->tags()->sync($validated['tags']);
        } else {
            $article->tags()->detach();
        }

        // Invalidate articles cache
        CacheService::forgetPattern('articles');

        return redirect()->route('articles.index')
            ->with('message', 'Article mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        // Delete cover image
        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }

        // Delete video file
        if ($article->video_file) {
            Storage::disk('public')->delete($article->video_file);
        }

        // Detach tags
        $article->tags()->detach();

        $article->delete();

        // Invalidate articles cache
        CacheService::forgetPattern('articles');

        return redirect()->route('articles.index')
            ->with('message', 'Article supprimé avec succès.');
    }
}
