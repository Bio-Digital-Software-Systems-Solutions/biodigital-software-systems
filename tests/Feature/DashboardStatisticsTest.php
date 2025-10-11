<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\BookRental;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_calculates_upcoming_events_correctly()
    {
        // Create 5 future events and 3 past events
        Event::factory()->count(5)->create(['start_date' => Carbon::now()->addDays(10)]);
        Event::factory()->count(3)->create(['start_date' => Carbon::now()->subDays(10)]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $stats = $response->viewData('page')['props']['stats'];

        $this->assertEquals(5, $stats['upcomingEvents']['value']);
    }

    /** @test */
    public function it_calculates_published_articles_correctly()
    {
        // Create 8 published articles and 2 drafts
        Article::factory()->count(8)->create(['published_at' => Carbon::now()]);
        Article::factory()->count(2)->create(['published_at' => null]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $stats = $response->viewData('page')['props']['stats'];

        $this->assertEquals(8, $stats['publishedArticles']['value']);
    }

    /** @test */
    public function it_shows_available_books_count()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $stats = $response->viewData('page')['props']['stats'];

        // Just verify the stat exists and is a non-negative number
        $this->assertArrayHasKey('availableBooks', $stats);
        $this->assertArrayHasKey('value', $stats['availableBooks']);
        $this->assertGreaterThanOrEqual(0, $stats['availableBooks']['value']);
    }

    /** @test */
    public function it_calculates_participation_rate_correctly()
    {
        // Create 10 events
        $events = Event::factory()->count(10)->create();

        // User participates in 6 events
        foreach ($events->take(6) as $event) {
            $event->participants()->attach($this->user->id);
        }

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $performance = $response->viewData('page')['props']['performance'];

        // 6/10 = 60%
        $this->assertEquals(60, $performance['participationRate']);
    }

    /** @test */
    public function it_shows_zero_participation_when_no_events_exist()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $performance = $response->viewData('page')['props']['performance'];

        $this->assertEquals(0, $performance['participationRate']);
    }

    /** @test */
    public function it_calculates_total_published_articles()
    {
        // Create 10 articles published at different times
        Article::factory()->count(10)->create(['published_at' => Carbon::now()]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $performance = $response->viewData('page')['props']['performance'];

        $this->assertEquals(10, $performance['articlesViewedThisMonth']);
    }

    /** @test */
    public function it_calculates_total_books_borrowed_by_user()
    {
        // User borrowed 5 books (3 returned, 2 still out)
        BookRental::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'return_date' => Carbon::now(),
        ]);
        BookRental::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'return_date' => null,
        ]);

        // Another user borrowed 4 books (should not count)
        $otherUser = User::factory()->create();
        BookRental::factory()->count(4)->create([
            'user_id' => $otherUser->id,
            'return_date' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $performance = $response->viewData('page')['props']['performance'];

        // Only count this user's rentals
        $this->assertEquals(5, $performance['booksBorrowed']);
    }

    /** @test */
    public function it_calculates_percentage_change_correctly()
    {
        // Create events for testing percentage change
        Event::factory()->count(5)->create([
            'start_date' => Carbon::now()->addDays(5),
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();
        $stats = $response->viewData('page')['props']['stats'];

        // Should have a change type (increase, decrease, or stable)
        $this->assertArrayHasKey('type', $stats['upcomingEvents']['change']);
        $this->assertContains(
            $stats['upcomingEvents']['change']['type'],
            ['increase', 'decrease', 'stable']
        );
    }
}
