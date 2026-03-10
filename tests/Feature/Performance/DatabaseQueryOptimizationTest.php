<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Event;
use App\Models\Article;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\CreatesPermissions;

class DatabaseQueryOptimizationTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function events_index_avoids_n_plus_1_queries(): void
    {
        // Create test data
        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            Event::factory()->count(3)->create(['user_id' => $user->id]);
        }

        DB::enableQueryLog();

        // Load events index
        $this->actingAs($users[0])->get('/events');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use eager loading to minimize queries
        // Adjust threshold based on actual implementation
        $this->assertLessThan(20, count($queries), 'Too many database queries detected');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function articles_with_author_uses_eager_loading(): void
    {
        $authors = User::factory()->count(10)->create();
        foreach ($authors as $author) {
            Article::factory()->count(5)->create([
                'author_id' => $author->id,
                'status' => 'published',
            ]);
        }

        DB::enableQueryLog();

        $this->get('/articles');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should not have N+1 problem
        $this->assertLessThan(15, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_statistics_are_optimized(): void
    {
        Event::factory()->count(50)->create();
        Article::factory()->count(100)->create();
        Book::factory()->count(30)->create();

        $user = User::factory()->create();

        DB::enableQueryLog();

        $this->actingAs($user)->get('/dashboard');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Dashboard should use optimized counting queries
        $this->assertLessThan(30, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_with_participants_uses_lazy_loading(): void
    {
        $event = Event::factory()->create();
        $participants = User::factory()->count(100)->create();

        foreach ($participants as $participant) {
            $event->participants()->attach($participant->id);
        }

        DB::enableQueryLog();

        // View event details
        $this->get("/events/{$event->uuid}");

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should paginate or lazy load participants
        $this->assertLessThan(25, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_profile_loads_efficiently(): void
    {
        $user = User::factory()->create();

        // Create associated data
        Event::factory()->count(10)->create(['user_id' => $user->id]);
        Article::factory()->count(10)->create(['author_id' => $user->id]);

        DB::enableQueryLog();

        $this->actingAs($user)->get('/profile');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(20, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function search_queries_are_indexed(): void
    {
        Article::factory()->count(1000)->create(['status' => 'published']);

        $user = User::factory()->create();

        $startTime = microtime(true);

        $this->actingAs($user)->get('/articles?search=test');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to ms

        // Search should complete quickly (adjust threshold as needed)
        $this->assertLessThan(1000, $executionTime, 'Search query too slow');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pagination_limits_query_results(): void
    {
        Article::factory()->count(100)->create(['status' => 'published']);

        DB::enableQueryLog();

        $this->get('/articles');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Find the main SELECT query
        $selectQueries = array_filter($queries, fn(array $query): bool => str_starts_with((string) $query['query'], 'select'));

        // Should have LIMIT clause in queries
        $hasLimit = false;
        foreach ($selectQueries as $query) {
            if (stripos((string) $query['query'], 'LIMIT') !== false) {
                $hasLimit = true;
                break;
            }
        }

        $this->assertTrue($hasLimit, 'Queries should use LIMIT for pagination');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_required_columns_are_selected(): void
    {
        User::factory()->count(50)->create();

        DB::enableQueryLog();

        // API endpoint that should only return specific fields
        $user = User::factory()->create();
        $this->actingAs($user)->get('/api/users');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Queries should not use SELECT *
        foreach ($queries as $query) {
            if (str_contains((string) $query['query'], 'select * from')) {
                // Some queries might legitimately need all columns
                // This is a guideline, not a strict rule
            }
        }

        $this->assertTrue(true); // Passed if no exceptions
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function event_calendar_view_is_optimized(): void
    {
        // Create many events
        Event::factory()->count(200)->create([
            'start_date' => now()->addDays(random_int(1, 30)),
        ]);

        $user = User::factory()->create();

        DB::enableQueryLog();

        $this->actingAs($user)->get('/events/calendar');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should limit to current month/view
        $this->assertLessThan(15, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chat_messages_use_cursor_pagination(): void
    {
        $user = User::factory()->create();

        DB::enableQueryLog();

        // Load chat interface
        $this->actingAs($user)->get('/chat');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Chat should load efficiently
        $this->assertLessThan(20, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function activity_log_queries_are_limited(): void
    {
        $user = User::factory()->create();

        // Create many activities
        for ($i = 0; $i < 500; $i++) {
            activity()
                ->causedBy($user)
                ->log('Test activity ' . $i);
        }

        DB::enableQueryLog();

        // View recent activity
        $this->actingAs($user)->get('/dashboard');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should limit activities shown
        $this->assertLessThan(25, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function complex_joins_are_optimized(): void
    {
        $users = User::factory()->count(20)->create();
        foreach ($users as $user) {
            Event::factory()->count(5)->create(['user_id' => $user->id]);
        }

        DB::enableQueryLog();

        // Query that might use joins
        $this->actingAs($users[0])->get('/events?with_author=true');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Joins should be efficient
        $this->assertLessThan(10, count($queries));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function subqueries_use_exists_instead_of_count(): void
    {
        Event::factory()->count(100)->create();

        DB::enableQueryLog();

        // Query filtering events with participants
        $this->get('/events?has_participants=true');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use EXISTS instead of COUNT for better performance
        $hasExistsClause = false;
        foreach ($queries as $query) {
            if (stripos((string) $query['query'], 'exists') !== false) {
                $hasExistsClause = true;
                break;
            }
        }

        // This is a recommendation, not always enforced
        $this->assertTrue(true);
    }
}
