<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_can_remember_values_in_cache()
    {
        $key = 'test.key';
        $value = 'test value';

        $result = CacheService::remember($key, fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has($key));
    }

    /** @test */
    public function it_tracks_cache_keys_for_pattern_deletion()
    {
        $key = 'test.tracked.key';

        CacheService::remember($key, fn() => 'value');

        $trackedKeys = Cache::get('cache_keys', []);
        $this->assertContains($key, $trackedKeys);
    }

    /** @test */
    public function it_can_remember_paginated_data()
    {
        $baseKey = 'articles.list';
        $page = 1;
        $data = ['article1', 'article2'];

        $result = CacheService::rememberPaginated($baseKey, $page, fn() => $data);

        $this->assertEquals($data, $result);
        $this->assertTrue(Cache::has("{$baseKey}.page.{$page}"));
    }

    /** @test */
    public function it_can_forget_cache_patterns_with_wildcard()
    {
        // Create multiple cache entries with same prefix
        $keys = [
            'users.list.page.1',
            'users.list.page.2',
            'users.active.count',
            'events.upcoming'
        ];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => 'value');
            $this->assertTrue(Cache::has($key));
        }

        // Forget all 'users.*' patterns
        CacheService::forgetPattern('users.*');

        // User-related caches should be gone
        $this->assertFalse(Cache::has('users.list.page.1'));
        $this->assertFalse(Cache::has('users.list.page.2'));
        $this->assertFalse(Cache::has('users.active.count'));

        // Other caches should remain
        $this->assertTrue(Cache::has('events.upcoming'));
    }

    /** @test */
    public function it_can_forget_cache_patterns_with_contains()
    {
        $keys = [
            'user.profile.123',
            'user.settings.123',
            'event.details.456'
        ];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => 'value');
        }

        // Forget patterns containing 'user'
        CacheService::forgetPattern('user');

        $this->assertFalse(Cache::has('user.profile.123'));
        $this->assertFalse(Cache::has('user.settings.123'));
        $this->assertTrue(Cache::has('event.details.456'));
    }

    /** @test */
    public function it_updates_tracked_keys_when_forgetting_patterns()
    {
        $keys = [
            'test.pattern.1',
            'test.pattern.2',
            'other.key'
        ];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => 'value');
        }

        CacheService::forgetPattern('test.*');

        $remainingKeys = Cache::get('cache_keys', []);
        $this->assertNotContains('test.pattern.1', $remainingKeys);
        $this->assertNotContains('test.pattern.2', $remainingKeys);
        $this->assertContains('other.key', $remainingKeys);
    }

    /** @test */
    public function it_generates_user_specific_cache_keys()
    {
        $base = 'profile';
        $userId = 123;

        $key = CacheService::userKey($base, $userId);

        $this->assertEquals('profile.user.123', $key);
    }

    /** @test */
    public function it_generates_role_specific_cache_keys()
    {
        $base = 'permissions';
        $role = 'admin';

        $key = CacheService::roleKey($base, $role);

        $this->assertEquals('permissions.role.admin', $key);
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        // Mock Cache to throw exception
        Cache::shouldReceive('remember')->andThrow(new \Exception('Cache error'));
        Cache::shouldReceive('get')->andReturn([]);

        $result = CacheService::remember('test.key', fn() => 'fallback value');

        $this->assertEquals('fallback value', $result);
    }

    /** @test */
    public function it_uses_correct_cache_durations()
    {
        $this->assertEquals(300, CacheService::SHORT_CACHE);
        $this->assertEquals(3600, CacheService::MEDIUM_CACHE);
        $this->assertEquals(86400, CacheService::LONG_CACHE);
    }

    /** @test */
    public function it_can_forget_by_tag_with_fallback()
    {
        // Set up cache entries
        $keys = ['tag.test.1', 'tag.test.2', 'other.key'];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => 'value');
        }

        // Mock Cache::tags to throw exception (simulating no Redis/Memcached)
        Cache::shouldReceive('tags')->andThrow(new \Exception('Tags not supported'));

        // Should fall back to pattern matching
        CacheService::forgetByTag('tag');

        // All 'tag' related keys should be cleared
        $this->assertFalse(Cache::has('tag.test.1'));
        $this->assertFalse(Cache::has('tag.test.2'));
        $this->assertTrue(Cache::has('other.key'));
    }

    /** @test */
    public function it_handles_empty_cache_keys_list()
    {
        // Ensure no cache_keys exist
        Cache::forget('cache_keys');

        // Should not throw exception
        CacheService::forgetPattern('nonexistent.*');

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_preserves_cache_keys_list_integrity()
    {
        $initialKeys = ['existing.key.1', 'existing.key.2'];
        Cache::put('cache_keys', $initialKeys, 3600);

        // Add new key
        CacheService::remember('new.key', fn() => 'value');

        $updatedKeys = Cache::get('cache_keys', []);
        $this->assertContains('existing.key.1', $updatedKeys);
        $this->assertContains('existing.key.2', $updatedKeys);
        $this->assertContains('new.key', $updatedKeys);
    }

    /** @test */
    public function it_removes_forgotten_keys_from_tracked_list()
    {
        $keys = ['users.1', 'users.2', 'events.1'];

        foreach ($keys as $key) {
            CacheService::remember($key, fn() => 'value');
        }

        $trackedKeys = Cache::get('cache_keys', []);
        $this->assertCount(3, $trackedKeys);

        CacheService::forgetPattern('users.*');

        $remainingKeys = Cache::get('cache_keys', []);
        $this->assertCount(1, $remainingKeys);
        $this->assertContains('events.1', $remainingKeys);
        $this->assertNotContains('users.1', $remainingKeys);
        $this->assertNotContains('users.2', $remainingKeys);
    }
}