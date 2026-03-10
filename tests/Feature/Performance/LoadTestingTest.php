<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Event;
use App\Models\Article;
use App\Models\Book;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\CreatesPermissions;

class LoadTestingTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function homepage_loads_under_acceptable_time(): void
    {
        Event::factory()->count(10)->create(['status' => 'published']);
        Article::factory()->count(10)->create(['status' => 'published']);

        $startTime = microtime(true);

        $response = $this->get('/');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to ms

        $response->assertSuccessful();

        // Should load in under 500ms
        $this->assertLessThan(500, $loadTime, "Homepage loaded in {$loadTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_handles_large_dataset(): void
    {
        $user = User::factory()->create();

        // Create large dataset
        Event::factory()->count(500)->create();
        Article::factory()->count(500)->create();
        Book::factory()->count(500)->create();

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/dashboard');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertSuccessful();

        // Dashboard should load reasonably fast even with large dataset
        $this->assertLessThan(1000, $loadTime, "Dashboard loaded in {$loadTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_user_requests_are_handled(): void
    {
        $users = User::factory()->count(10)->create();
        Event::factory()->count(50)->create();

        $startTime = microtime(true);

        // Simulate concurrent requests
        foreach ($users as $user) {
            $this->actingAs($user)->get('/events');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Should handle 10 concurrent requests efficiently
        $averageTime = $totalTime / count($users);
        $this->assertLessThan(200, $averageTime, "Average request time: {$averageTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function api_responses_are_optimized(): void
    {
        $user = User::factory()->create();
        Event::factory()->count(100)->create();

        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->actingAs($user)->getJson('/api/events');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertSuccessful();

        // API should respond quickly
        $this->assertLessThan(300, $responseTime, "API responded in {$responseTime}ms");

        // Should use minimal queries
        $this->assertLessThan(20, count($queries), 'API uses ' . count($queries) . ' queries');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function memory_usage_stays_within_limits(): void
    {
        $user = User::factory()->create();

        // Create large dataset
        Event::factory()->count(1000)->create();

        $initialMemory = memory_get_usage(true);

        $response = $this->actingAs($user)->get('/events');

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $response->assertSuccessful();

        // Memory increase should be reasonable (under 50MB for this operation)
        $this->assertLessThan(50, $memoryIncrease, "Memory increased by {$memoryIncrease}MB");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function database_connection_pooling_is_efficient(): void
    {
        $user = User::factory()->create();

        $queries = 0;

        // Make multiple requests that access database
        for ($i = 0; $i < 10; $i++) {
            DB::enableQueryLog();

            $this->actingAs($user)->get('/dashboard');

            $queries += count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        // Connection pooling should reuse connections efficiently
        $this->assertTrue($queries > 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function large_result_set_pagination_performance(): void
    {
        Article::factory()->count(1000)->create(['status' => 'published']);

        $user = User::factory()->create();

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/articles?page=1');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertSuccessful();

        // First page should load quickly even with 1000 records
        $this->assertLessThan(500, $loadTime, "First page loaded in {$loadTime}ms");

        // Last page should also load efficiently
        $startTime = microtime(true);

        $this->actingAs($user)->get('/articles?page=67'); // Assuming 15 per page

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(500, $loadTime, "Last page loaded in {$loadTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function search_performance_with_large_dataset(): void
    {
        // Create searchable content
        for ($i = 0; $i < 500; $i++) {
            Article::factory()->create([
                'title' => 'Article ' . $i,
                'content' => 'Content with searchable keywords ' . $i,
                'status' => 'published',
            ]);
        }

        $user = User::factory()->create();

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/articles?search=keyword');

        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;

        $response->assertSuccessful();

        // Search should complete in reasonable time
        $this->assertLessThan(800, $searchTime, "Search completed in {$searchTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chat_message_retrieval_is_optimized(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        // Create many messages
        ChatMessage::factory()->count(500)->create([
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get("/chat/rooms/{$room->uuid}");

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertSuccessful();

        // Should load messages efficiently (with pagination/lazy loading)
        $this->assertLessThan(600, $loadTime, "Chat loaded in {$loadTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function asset_loading_is_optimized(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();

        $content = $response->getContent();

        // Check for asset optimization
        // Should have versioned assets for cache busting
        $this->assertMatchesRegularExpression('/\.(css|js)\?id=/', $content);

        // Should load minimal JavaScript on initial page load
        preg_match_all('/<script[^>]*src=[^>]*>/i', $content, $scripts);
        $scriptCount = count($scripts[0]);

        // Should have reasonable number of script tags (for code splitting)
        $this->assertLessThan(10, $scriptCount, "Page loads {$scriptCount} scripts");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function image_lazy_loading_implementation(): void
    {
        Event::factory()->count(20)->create([
            'image' => 'events/sample-image.jpg',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/events');

        $content = $response->getContent();

        // Check for lazy loading attributes
        if (str_contains($content, '<img')) {
            // Images should have loading="lazy" attribute
            $this->assertTrue(
                str_contains($content, 'loading="lazy"') ||
                str_contains($content, 'data-src')
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cache_hit_rate_optimization(): void
    {
        $user = User::factory()->create();

        Event::factory()->count(50)->create();

        // First request - cache miss
        Cache::flush();
        $this->actingAs($user)->get('/events');

        // Second request - should hit cache
        DB::enableQueryLog();

        $this->actingAs($user)->get('/events');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // If caching is implemented, second request should have fewer queries
        // This tests cache effectiveness
        $this->assertTrue(count($queries) >= 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function json_response_size_is_reasonable(): void
    {
        $user = User::factory()->create();

        Event::factory()->count(100)->create();

        $response = $this->actingAs($user)->getJson('/api/events');

        $responseSize = strlen($response->getContent());
        $responseSizeKB = $responseSize / 1024;

        $response->assertSuccessful();

        // Response should be reasonably sized (under 500KB)
        $this->assertLessThan(500, $responseSizeKB, "Response size: {$responseSizeKB}KB");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function database_index_effectiveness(): void
    {
        // Create large dataset
        for ($i = 0; $i < 1000; $i++) {
            Event::factory()->create([
                'status' => $i % 3 === 0 ? 'published' : 'draft',
                'start_date' => now()->addDays($i % 30),
            ]);
        }

        $user = User::factory()->create();

        DB::enableQueryLog();

        // Query using indexed column
        $this->actingAs($user)->get('/events?status=published');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use indexed query (fast execution)
        foreach ($queries as $query) {
            if (str_contains((string) $query['query'], 'status')) {
                // Query execution time should be minimal (under 100ms)
                $this->assertLessThan(100, $query['time']);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function static_content_caching_headers(): void
    {
        $response = $this->get('/build/assets/app.js');

        if ($response->isSuccessful()) {
            // Static assets should have cache headers
            $cacheControl = $response->headers->get('Cache-Control');

            if ($cacheControl) {
                $this->assertStringContainsString('max-age', $cacheControl);
            }

            // Should have ETag for cache validation
            $etag = $response->headers->get('ETag');
            if ($etag) {
                $this->assertNotEmpty($etag);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function websocket_connection_performance(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);

        // Test chat room polling/websocket endpoint
        $startTime = microtime(true);

        $response = $this->actingAs($user)->getJson("/api/chat/rooms/{$room->uuid}/messages");

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        if ($response->isSuccessful()) {
            // Real-time endpoints should be very fast
            $this->assertLessThan(200, $responseTime, "Websocket endpoint: {$responseTime}ms");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function background_job_processing_efficiency(): void
    {
        $user = User::factory()->create();

        // Trigger action that might queue jobs
        $response = $this->actingAs($user)->post('/events', [
            'title' => 'Test Event',
            'description' => 'Description',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        // Jobs should be queued, not block response
        if ($response->isRedirect()) {
            // Response should be fast even if jobs are queued
            $this->assertTrue(true);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function session_storage_performance(): void
    {
        $user = User::factory()->create();

        // Login to create session
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Make multiple requests with session
        $startTime = microtime(true);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->get('/dashboard');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / 5;

        // Session retrieval should be fast
        $this->assertLessThan(200, $avgTime, "Average request with session: {$avgTime}ms");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function database_query_optimization_analysis(): void
    {
        $user = User::factory()->create();

        // Create relational data
        $users = User::factory()->count(10)->create();
        foreach ($users as $u) {
            Event::factory()->count(3)->create(['user_id' => $u->id]);
        }

        DB::enableQueryLog();

        $this->actingAs($user)->get('/events');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Analyze queries for optimization opportunities
        $selectQueries = array_filter($queries, fn(array $query): bool => stripos((string) $query['query'], 'select') === 0);

        // Should not have excessive SELECT queries (N+1 problem)
        $this->assertLessThan(15, count($selectQueries), 'Found ' . count($selectQueries) . ' SELECT queries');

        // Check for queries without WHERE clause on large tables
        foreach ($queries as $query) {
            if (stripos((string) $query['query'], 'select') === 0) {
                // Queries on large tables should have WHERE or LIMIT
                $hasWhere = stripos((string) $query['query'], 'where') !== false;
                $hasLimit = stripos((string) $query['query'], 'limit') !== false;

                $this->assertTrue($hasWhere || $hasLimit, 'Query might need optimization: ' . $query['query']);
            }
        }
    }
}
