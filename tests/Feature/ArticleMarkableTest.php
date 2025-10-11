<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maize\Markable\Models\Bookmark;
use Maize\Markable\Models\Like;
use Tests\TestCase;

class ArticleMarkableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_authenticated_user_can_like_article(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('articles.like', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isLiked' => true,
                'likesCount' => 1,
            ]);

        $this->assertTrue(Like::has($article, $user));
        $this->assertEquals(1, Like::count($article));
    }

    public function test_authenticated_user_can_unlike_article(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        // First like the article
        Like::add($article, $user);

        // Then unlike it
        $response = $this->actingAs($user)->postJson(route('articles.like', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isLiked' => false,
                'likesCount' => 0,
            ]);

        $this->assertFalse(Like::has($article, $user));
    }

    public function test_user_can_only_like_article_once(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        // Like the article multiple times (should toggle)
        $this->actingAs($user)->postJson(route('articles.like', $article->slug));
        $this->actingAs($user)->postJson(route('articles.like', $article->slug));
        $response = $this->actingAs($user)->postJson(route('articles.like', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isLiked' => true,
                'likesCount' => 1,
            ]);

        $this->assertTrue(Like::has($article, $user));
        $this->assertEquals(1, Like::count($article));
    }

    public function test_unauthenticated_user_cannot_like_article(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('articles.like', $article->slug));

        $response->assertStatus(401);
    }

    public function test_likes_count_updates_correctly(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('view articles');

        $user2 = User::factory()->create();
        $user2->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user1->id,
            'published_at' => now(),
        ]);

        // User 1 likes
        $this->actingAs($user1)->postJson(route('articles.like', $article->id));

        // User 2 likes
        $response = $this->actingAs($user2)->postJson(route('articles.like', $article->id));

        $response->assertStatus(200)
            ->assertJson([
                'isLiked' => true,
                'likesCount' => 2,
            ]);

        // User 1 unlikes
        $response = $this->actingAs($user1)->postJson(route('articles.like', $article->id));

        $response->assertStatus(200)
            ->assertJson([
                'isLiked' => false,
                'likesCount' => 1,
            ]);

        $this->assertEquals(1, Like::count($article));
    }

    public function test_authenticated_user_can_bookmark_article(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isBookmarked' => true,
            ]);

        $this->assertTrue(Bookmark::has($article, $user));
    }

    public function test_authenticated_user_can_remove_bookmark(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        // First bookmark the article
        Bookmark::add($article, $user);

        // Then remove bookmark
        $response = $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isBookmarked' => false,
            ]);

        $this->assertFalse(Bookmark::has($article, $user));
    }

    public function test_user_can_only_bookmark_article_once(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        // Bookmark the article multiple times (should toggle)
        $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));
        $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));
        $response = $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isBookmarked' => true,
            ]);

        $this->assertTrue(Bookmark::has($article, $user));
    }

    public function test_unauthenticated_user_cannot_bookmark_article(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('articles.favorite', $article->slug));

        $response->assertStatus(401);
    }

    public function test_multiple_users_can_bookmark_same_article(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('view articles');

        $user2 = User::factory()->create();
        $user2->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user1->id,
            'published_at' => now(),
        ]);

        // User 1 bookmarks
        $this->actingAs($user1)->postJson(route('articles.favorite', $article->id));

        // User 2 bookmarks
        $response = $this->actingAs($user2)->postJson(route('articles.favorite', $article->id));

        $response->assertStatus(200)
            ->assertJson([
                'isBookmarked' => true,
            ]);

        $this->assertTrue(Bookmark::has($article, $user1));
        $this->assertTrue(Bookmark::has($article, $user2));
    }

    public function test_user_can_bookmark_and_like_same_article(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        // Like the article
        $this->actingAs($user)->postJson(route('articles.like', $article->slug));

        // Bookmark the article
        $response = $this->actingAs($user)->postJson(route('articles.favorite', $article->slug));

        $response->assertStatus(200)
            ->assertJson([
                'isBookmarked' => true,
            ]);

        $this->assertTrue(Like::has($article, $user));
        $this->assertTrue(Bookmark::has($article, $user));
    }

    public function test_deleting_article_deletes_associated_marks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view articles');

        $category = Category::factory()->create();
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'published_at' => now(),
        ]);

        Like::add($article, $user);
        Bookmark::add($article, $user);

        $articleId = $article->id;
        $article->delete();

        // Check that marks are deleted
        $this->assertDatabaseMissing('markable_likes', [
            'markable_id' => $articleId,
            'markable_type' => Article::class,
        ]);

        $this->assertDatabaseMissing('markable_bookmarks', [
            'markable_id' => $articleId,
            'markable_type' => Article::class,
        ]);
    }
}
