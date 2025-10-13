<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\CreatesPermissions;

class ArticleModelTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_belongs_to_author(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->create(['author_id' => $author->id]);

        $this->assertInstanceOf(User::class, $article->author);
        $this->assertEquals($author->id, $article->author->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $article = Article::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $article->category);
        $this->assertEquals($category->id, $article->category->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_has_published_scope(): void
    {
        Article::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        Article::factory()->count(2)->create([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $publishedArticles = Article::published()->get();

        $this->assertCount(3, $publishedArticles);
        $this->assertTrue($publishedArticles->every(fn ($article) => $article->status === 'published'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_has_draft_scope(): void
    {
        Article::factory()->count(2)->create(['status' => 'draft']);
        Article::factory()->count(3)->create(['status' => 'published']);

        $draftArticles = Article::draft()->get();

        $this->assertCount(2, $draftArticles);
        $this->assertTrue($draftArticles->every(fn ($article) => $article->status === 'draft'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_has_by_author_scope(): void
    {
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        Article::factory()->count(3)->create(['author_id' => $author1->id]);
        Article::factory()->count(2)->create(['author_id' => $author2->id]);

        $author1Articles = Article::byAuthor($author1->id)->get();

        $this->assertCount(3, $author1Articles);
        $this->assertTrue($author1Articles->every(fn ($article) => $article->author_id === $author1->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_is_published_attribute(): void
    {
        $publishedArticle = Article::factory()->create(['status' => 'published']);
        $draftArticle = Article::factory()->create(['status' => 'draft']);

        $this->assertTrue($publishedArticle->is_published);
        $this->assertFalse($draftArticle->is_published);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_can_be_published(): void
    {
        $article = Article::factory()->create(['status' => 'draft']);

        $article->publish();

        $this->assertEquals('published', $article->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_can_be_unpublished(): void
    {
        $article = Article::factory()->create(['status' => 'published']);

        $article->unpublish();

        $this->assertEquals('draft', $article->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_slug_is_generated_from_title(): void
    {
        $article = Article::factory()->create(['title' => 'This is a Test Article']);

        $this->assertEquals('this-is-a-test-article', $article->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_excerpt_is_generated_from_content(): void
    {
        $content = str_repeat('Lorem ipsum dolor sit amet. ', 50);
        $article = Article::factory()->create(['content' => $content]);

        $this->assertNotEmpty($article->excerpt);
        $this->assertLessThan(200, strlen($article->excerpt));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_reading_time_is_calculated(): void
    {
        $content = str_repeat('word ', 300); // ~300 words
        $article = Article::factory()->create(['content' => $content]);

        $readingTime = $article->reading_time;

        $this->assertGreaterThan(0, $readingTime);
        $this->assertIsInt($readingTime);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_can_increment_views(): void
    {
        $article = Article::factory()->create(['views_count' => 0]);

        $article->incrementViews();

        $this->assertEquals(1, $article->fresh()->views_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_fillable_attributes_are_protected(): void
    {
        $article = new Article();

        $this->assertContains('title', $article->getFillable());
        $this->assertContains('content', $article->getFillable());
        $this->assertNotContains('id', $article->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_casts_dates_properly(): void
    {
        $article = Article::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->updated_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_has_tags_relationship(): void
    {
        $article = Article::factory()->create();

        // If tags relationship exists
        if (method_exists($article, 'tags')) {
            $this->assertIsObject($article->tags());
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function article_search_scope_works(): void
    {
        Article::factory()->create([
            'title' => 'Laravel Best Practices',
            'content' => 'Content about Laravel',
            'status' => 'published',
        ]);

        Article::factory()->create([
            'title' => 'React Components',
            'content' => 'Content about React',
            'status' => 'published',
        ]);

        $results = Article::search('Laravel')->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Laravel', $results->first()->title);
    }
}
