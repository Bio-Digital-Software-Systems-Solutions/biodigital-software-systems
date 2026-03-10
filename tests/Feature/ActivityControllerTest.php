<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_view_activity_index_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->has('activities')
                ->has('logNames')
                ->has('stats')
                ->has('filters')
            );
    }

    /** @test */
    public function it_shows_activities_in_paginated_format(): void
    {
        // Create some activities by creating and modifying models
        $event = Event::factory()->create(['title' => 'Test Event']);
        $event->update(['title' => 'Updated Event']);
        $event->delete();

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->has('activities.data')
                ->where('activities.current_page', 1)
            );
    }

    /** @test */
    public function it_can_filter_activities_by_type(): void
    {
        // Create activities with different log names
        activity('event_log')->log('Event created');
        activity('article_log')->log('Article created');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', ['type' => 'event_log']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('filters.type', 'event_log')
            );
    }

    /** @test */
    public function it_can_filter_activities_by_date_range(): void
    {
        // Create an activity
        activity()->log('Test activity');

        $today = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', [
                'from' => $today,
                'to' => $today,
            ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('filters.from', $today)
                ->where('filters.to', $today)
            );
    }

    /** @test */
    public function it_can_search_activities_by_description(): void
    {
        activity()->log('Special unique description');
        activity()->log('Another activity');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', ['search' => 'Special unique']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('filters.search', 'Special unique')
            );
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->get(route('activity.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_shows_activity_stats(): void
    {
        // Create some activities
        activity()->log('Activity 1');
        activity()->log('Activity 2');
        activity()->log('Activity 3');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->has('stats.today')
                ->has('stats.this_week')
                ->has('stats.this_month')
                ->has('stats.total')
            );
    }

    /** @test */
    public function it_shows_unique_log_names_for_filtering(): void
    {
        activity('custom_log_1')->log('Activity 1');
        activity('custom_log_2')->log('Activity 2');
        activity('custom_log_1')->log('Activity 3');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->has('logNames')
            );
    }

    /** @test */
    public function it_formats_activity_data_correctly(): void
    {
        Event::factory()->create(['title' => 'Test Event Title']);

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->has('activities.data.0', fn ($activity) => $activity
                    ->has('id')
                    ->has('log_name')
                    ->has('description')
                    ->has('event')
                    ->has('subject_type')
                    ->has('subject_name')
                    ->has('subject_id')
                    ->has('causer')
                    ->has('properties')
                    ->has('icon')
                    ->has('url')
                    ->has('created_at')
                    ->has('time_ago')
                )
            );
    }

    /** @test */
    public function it_includes_causer_information_when_available(): void
    {
        // Clear existing activities first
        Activity::query()->delete();

        // Create activity with causer using the database directly
        $activity = new Activity();
        $activity->log_name = 'test';
        $activity->description = 'User performed an action';
        $activity->causer_type = User::class;
        $activity->causer_id = $this->user->id;
        $activity->save();

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk();

        // Verify the activity has causer in the response
        $data = $response->original->getData()['page']['props'];
        $activities = $data['activities']['data'];

        $this->assertCount(1, $activities);
        $this->assertNotNull($activities[0]['causer']);
        $this->assertEquals($this->user->id, $activities[0]['causer']['id']);
        $this->assertEquals($this->user->name, $activities[0]['causer']['name']);
    }

    /** @test */
    public function it_returns_correct_pagination_info(): void
    {
        // Create more than one page of activities
        for ($i = 0; $i < 25; $i++) {
            activity()->log("Activity $i");
        }

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('activities.current_page', 1)
                ->where('activities.per_page', 20)
                ->has('activities.links')
            );
    }

    /** @test */
    public function it_can_navigate_to_page_2(): void
    {
        // Create more than one page of activities
        for ($i = 0; $i < 25; $i++) {
            activity()->log("Activity $i");
        }

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', ['page' => 2]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('activities.current_page', 2)
            );
    }

    /** @test */
    public function it_handles_empty_activities_gracefully(): void
    {
        // Clear all activities
        Activity::query()->delete();

        $response = $this->actingAs($this->user)
            ->get(route('activity.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('activities.total', 0)
                ->where('stats.total', 0)
            );
    }

    /** @test */
    public function it_filters_by_causer_id(): void
    {
        $otherUser = User::factory()->create();

        // Create activity as current user
        activity()->causedBy($this->user)->log('My activity');

        // Create activity as other user
        activity()->causedBy($otherUser)->log('Other user activity');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', ['causer_id' => $this->user->id]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('filters.causer_id', (string) $this->user->id)
            );
    }

    /** @test */
    public function it_can_combine_multiple_filters(): void
    {
        $today = now()->format('Y-m-d');

        activity('test_log')->log('Searchable activity');

        $response = $this->actingAs($this->user)
            ->get(route('activity.index', [
                'type' => 'test_log',
                'search' => 'Searchable',
                'from' => $today,
                'to' => $today,
            ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Activity/Index')
                ->where('filters.type', 'test_log')
                ->where('filters.search', 'Searchable')
                ->where('filters.from', $today)
                ->where('filters.to', $today)
            );
    }
}
