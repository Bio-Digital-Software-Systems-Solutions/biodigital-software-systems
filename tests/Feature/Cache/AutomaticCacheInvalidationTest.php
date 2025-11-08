<?php

namespace Tests\Feature\Cache;

use App\Models\Article;
use App\Models\Event;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AutomaticCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_automatically_invalidates_cache_when_user_is_created()
    {
        // Set up cache
        $cacheKey = 'users.list';
        CacheService::remember($cacheKey, fn() => ['test' => 'data'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create a user (should trigger cache invalidation)
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_automatically_invalidates_cache_when_user_is_updated()
    {
        $user = User::factory()->create();

        // Set up cache
        $cacheKey = 'users.profile.' . $user->id;
        CacheService::remember($cacheKey, fn() => ['user' => $user->toArray()], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Update the user
        $user->update(['first_name' => 'Updated Name']);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_automatically_invalidates_cache_when_user_is_deleted()
    {
        $user = User::factory()->create();

        // Set up cache
        $cacheKey = 'users.active';
        CacheService::remember($cacheKey, fn() => ['active_users' => 10], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Delete the user
        $user->delete();

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_invalidates_related_caches_when_user_is_modified()
    {
        $user = User::factory()->create();

        // Set up multiple related caches
        $eventsCacheKey = 'events.index.page.1';
        $articlesCacheKey = 'articles.published';
        $dashboardCacheKey = 'dashboard.stats';

        CacheService::remember($eventsCacheKey, fn() => ['events' => []], 3600);
        CacheService::remember($articlesCacheKey, fn() => ['articles' => []], 3600);
        CacheService::remember($dashboardCacheKey, fn() => ['stats' => []], 3600);

        $this->assertTrue(Cache::has($eventsCacheKey));
        $this->assertTrue(Cache::has($articlesCacheKey));
        $this->assertTrue(Cache::has($dashboardCacheKey));

        // Update the user
        $user->update(['first_name' => 'Updated Name']);

        // All related caches should be invalidated
        $this->assertFalse(Cache::has($eventsCacheKey));
        $this->assertFalse(Cache::has($articlesCacheKey));
        $this->assertFalse(Cache::has($dashboardCacheKey));
    }

    /** @test */
    public function it_automatically_invalidates_cache_when_event_is_created()
    {
        $user = User::factory()->create();

        // Set up cache
        $cacheKey = 'events.upcoming';
        CacheService::remember($cacheKey, fn() => ['events' => []], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create an event
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Event'
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_automatically_invalidates_cache_when_article_is_created()
    {
        $user = User::factory()->create();

        // Set up cache
        $cacheKey = 'articles.latest';
        CacheService::remember($cacheKey, fn() => ['articles' => []], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create an article
        Article::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Article'
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_logs_cache_invalidation_events()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Cache invalidated for model: .*User.* \(ID: \d+\)/'));

        $user = User::factory()->create();
        $user->update(['first_name' => 'Updated']);
    }

    /** @test */
    public function it_handles_cache_invalidation_errors_gracefully()
    {
        // Mock cache failure
        Cache::shouldReceive('forget')->andThrow(new \Exception('Cache error'));
        Cache::shouldReceive('has')->andReturn(true);

        $user = User::factory()->create();

        // Should not throw exception
        $this->expectNotToPerformAssertions();
        $user->update(['first_name' => 'Test']);
    }

    /** @test */
    public function it_uses_custom_cache_key_when_defined()
    {
        // Create a model with custom cache key
        $user = new class extends User {
            protected $cacheKey = 'custom_users';
            protected $table = 'users';
        };

        $user = $user->factory()->create();

        // Set up cache with custom key
        $customCacheKey = 'custom_users.test';
        CacheService::remember($customCacheKey, fn() => ['data' => 'test'], 3600);

        $this->assertTrue(Cache::has($customCacheKey));

        // Update should invalidate custom cache key
        $user->update(['first_name' => 'Updated']);

        $this->assertFalse(Cache::has($customCacheKey));
    }

    /** @test */
    public function it_clears_pattern_based_cache_keys()
    {
        $user = User::factory()->create();

        // Set up multiple cache keys with same pattern
        $keys = [
            'users.list.page.1',
            'users.list.page.2',
            'users.active.count',
            'users.inactive.count'
        ];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => ['data' => 'test'], 3600);
            $this->assertTrue(Cache::has($key));
        }

        // Update user
        $user->update(['first_name' => 'Updated']);

        // All user-related cache should be cleared
        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key), "Cache key {$key} should be invalidated");
        }
    }

    /** @test */
    public function it_executes_custom_cache_invalidation_if_defined()
    {
        // Create a model with custom invalidation
        $testModel = new class extends User {
            protected $table = 'users';
            protected $customInvalidationCalled = false;

            public function customCacheInvalidation(): void
            {
                $this->customInvalidationCalled = true;
                Cache::forget('custom.invalidation.key');
            }

            public function wasCustomInvalidationCalled(): bool
            {
                return $this->customInvalidationCalled;
            }
        };

        $instance = $testModel->factory()->create();

        // Set up custom cache
        Cache::put('custom.invalidation.key', 'test', 3600);
        $this->assertTrue(Cache::has('custom.invalidation.key'));

        // Update should trigger custom invalidation
        $instance->update(['first_name' => 'Updated']);

        // Custom cache should be cleared
        $this->assertFalse(Cache::has('custom.invalidation.key'));
    }

    /** @test */
    public function it_handles_soft_delete_cache_invalidation()
    {
        // Skip if User model doesn't use soft deletes
        if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(User::class))) {
            $this->markTestSkipped('User model does not use soft deletes');
        }

        $user = User::factory()->create();

        // Set up cache
        $cacheKey = 'users.active';
        CacheService::remember($cacheKey, fn() => ['active_users' => 10], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Soft delete the user
        $user->delete();

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));

        // Set up cache again
        CacheService::remember($cacheKey, fn() => ['active_users' => 9], 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // Restore the user
        $user->restore();

        // Cache should be invalidated again
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_properly_pluralizes_model_names_for_cache_keys()
    {
        $testCases = [
            'User' => 'users',
            'Event' => 'events',
            'Article' => 'articles',
            'Category' => 'categories', // y -> ies
        ];

        foreach ($testCases as $modelName => $expectedPlural) {
            $mockModel = new class extends \Illuminate\Database\Eloquent\Model {
                use \App\Traits\ClearsCache;

                protected static $testModelName;

                public static function setTestModelName($name)
                {
                    static::$testModelName = $name;
                }

                protected function getCacheKey(): string
                {
                    return $this->pluralize(strtolower(static::$testModelName));
                }
            };

            $mockModel->setTestModelName($modelName);
            $instance = new $mockModel;

            $reflection = new \ReflectionClass($instance);
            $method = $reflection->getMethod('getCacheKey');
            $method->setAccessible(true);

            $result = $method->invoke($instance);
            $this->assertEquals($expectedPlural, $result, "Failed to pluralize {$modelName} to {$expectedPlural}");
        }
    }
}