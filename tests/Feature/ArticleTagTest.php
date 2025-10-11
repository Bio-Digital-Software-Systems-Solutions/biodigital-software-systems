<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleTagTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_create_article_with_tags()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create articles');

        $category = Category::factory()->create(['name' => 'Technology']);
        $tag1 = Tag::create(['name' => 'PHP', 'color' => '#777bb4']);
        $tag2 = Tag::create(['name' => 'Laravel', 'color' => '#ff2d20']);

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Test Article with Tags',
                'content' => 'This is a test article content.',
                'category_id' => $category->id,
                'tags' => [$tag1->id, $tag2->id],
                'is_published' => true,
            ])
            ->assertRedirect(route('articles.index'));

        $article = Article::where('title', 'Test Article with Tags')->first();

        $this->assertNotNull($article);
        $this->assertEquals('test-article-with-tags', $article->slug);
        $this->assertNotNull($article->published_at);
        $this->assertEquals(2, $article->tags()->count());
        $this->assertTrue($article->tags->contains($tag1));
        $this->assertTrue($article->tags->contains($tag2));
    }

    public function test_user_can_create_article_with_cover_image()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->givePermissionTo('create articles');

        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test-cover.jpg', 800, 600);

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Test Article with Cover',
                'content' => 'This is a test article with cover image.',
                'category_id' => $category->id,
                'cover_image' => $image,
                'is_published' => false,
            ])
            ->assertRedirect(route('articles.index'));

        $article = Article::where('title', 'Test Article with Cover')->first();

        $this->assertNotNull($article);
        $this->assertNotNull($article->cover_image);
        $this->assertNull($article->published_at); // Should not be published
        Storage::disk('public')->assertExists($article->cover_image);
    }

    public function test_user_can_create_article_with_video_file()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->givePermissionTo('create articles');

        $category = Category::factory()->create();
        $video = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Test Article with Video',
                'content' => 'This is a test article with video.',
                'category_id' => $category->id,
                'video_file' => $video,
                'is_published' => true,
            ])
            ->assertRedirect(route('articles.index'));

        $article = Article::where('title', 'Test Article with Video')->first();

        $this->assertNotNull($article);
        $this->assertNotNull($article->video_file);
        $this->assertNotNull($article->published_at);
        Storage::disk('public')->assertExists($article->video_file);
    }

    public function test_user_can_update_article_tags()
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['create articles', 'edit articles']);

        $category = Category::factory()->create();
        $tag1 = Tag::create(['name' => 'Old Tag', 'color' => '#000000']);
        $tag2 = Tag::create(['name' => 'New Tag', 'color' => '#ffffff']);

        $article = Article::create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'Test content',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        $article->tags()->attach($tag1->id);

        $this->actingAs($user)
            ->put(route('articles.update', $article->id), [
                'title' => 'Updated Test Article',
                'content' => 'Updated test content',
                'category_id' => $category->id,
                'tags' => [$tag2->id],
                'is_published' => true,
            ])
            ->assertRedirect(route('articles.index'));

        $article->refresh();

        $this->assertEquals('Updated Test Article', $article->title);
        $this->assertEquals(1, $article->tags()->count());
        $this->assertTrue($article->tags->contains($tag2));
        $this->assertFalse($article->tags->contains($tag1));
    }

    public function test_article_slug_is_unique()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create articles');

        $category = Category::factory()->create();

        // Create first article
        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Test Article',
                'content' => 'First article content.',
                'category_id' => $category->id,
                'is_published' => true,
            ]);

        // Create second article with same title
        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Test Article',
                'content' => 'Second article content.',
                'category_id' => $category->id,
                'is_published' => true,
            ]);

        $articles = Article::where('title', 'Test Article')->get();

        $this->assertEquals(2, $articles->count());
        $this->assertEquals('test-article', $articles->first()->slug);
        $this->assertEquals('test-article-1', $articles->last()->slug);
    }

    public function test_unauthorized_user_cannot_create_articles()
    {
        $user = User::factory()->create(); // No permissions
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Unauthorized Article',
                'content' => 'This should not be created.',
                'category_id' => $category->id,
            ])
            ->assertStatus(403);
    }
}
