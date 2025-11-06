<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Maize\Markable\Markable;
use Maize\Markable\Models\Bookmark;
use Maize\Markable\Models\Like;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string|null $cover_image
 * @property string|null $video_file
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property bool $is_featured
 * @property int $user_id
 * @property int $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $views_count
 * @property-read \App\Models\Category $category
 * @property-read string $full_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @method static \Database\Factories\ArticleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereCoverImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereHasMark(\Maize\Markable\Mark $mark, \Illuminate\Database\Eloquent\Model $user, ?string $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereVideoFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereViewsCount($value)
 * @mixin \Eloquent
 */
class Article extends Model
{
    use HasFactory, HasUuid, Markable, LogsActivity;

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
    protected static $marks = [
        Like::class,
        Bookmark::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'cover_image',
        'video_file',
        'published_at',
        'is_featured',
        'views_count',
        'views',
        'user_id',
        'category_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the article.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all of the article's images.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get all of the article's videos.
     */
    public function videos(): MorphMany
    {
        return $this->morphMany(Video::class, 'videoable');
    }

    /**
     * Get the tags for the article.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Get the article's full URL attribute.
     */
    public function getFullUrlAttribute(): string
    {
        return url("/articles/{$this->slug}");
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include featured articles.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Increment the views count for the article.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Accessor for author_id (maps to user_id for compatibility).
     */
    public function getAuthorIdAttribute()
    {
        return $this->user_id;
    }

    /**
     * Mutator for author_id (maps to user_id for compatibility).
     */
    public function setAuthorIdAttribute($value)
    {
        $this->attributes['user_id'] = $value;
    }

    /**
     * Get the author relationship (alias for user).
     */
    public function author()
    {
        return $this->user();
    }

    /**
     * Accessor for featured (maps to is_featured).
     */
    public function getFeaturedAttribute()
    {
        return $this->is_featured;
    }

    /**
     * Mutator for featured (maps to is_featured).
     */
    public function setFeaturedAttribute($value)
    {
        $this->attributes['is_featured'] = $value;
    }

    /**
     * Accessor for views (maps to views_count for backward compatibility).
     */
    public function getViewsAttribute()
    {
        return $this->views_count ?? 0;
    }

    /**
     * Mutator for views (maps to views_count for backward compatibility).
     */
    public function setViewsAttribute($value)
    {
        $this->attributes['views_count'] = $value;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to search articles.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    /**
     * Get the reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        // Average reading speed: 200 words per minute
        $wordCount = str_word_count(strip_tags($this->content));
        return (int) ceil($wordCount / 200);
    }

    /**
     * Get the article excerpt.
     * If no excerpt is set, generate one from content.
     */
    public function getExcerptAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        // Generate excerpt from content (first 150 characters)
        $text = strip_tags($this->content);
        if (strlen($text) <= 150) {
            return $text;
        }

        return substr($text, 0, 150) . '...';
    }

    /**
     * Scope to filter by author.
     */
    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('user_id', $authorId);
    }

    /**
     * Check if article is published.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Publish the article.
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the article.
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
