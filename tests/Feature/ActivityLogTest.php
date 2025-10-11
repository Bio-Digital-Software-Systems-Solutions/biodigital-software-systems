<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for testing
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_logs_model_creation(): void
    {
        // Create an event
        $event = Event::factory()->create([
            'title' => 'Test Event',
            'user_id' => $this->user->id,
        ]);

        // Check if activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Event::class,
            'subject_id' => $event->id,
            'event' => 'created',
        ]);

        // Retrieve the activity
        $activity = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNotNull($activity->properties);
    }

    /** @test */
    public function it_logs_model_update(): void
    {
        // Create an event
        $event = Event::factory()->create([
            'title' => 'Original Title',
            'user_id' => $this->user->id,
        ]);

        // Update the event
        $event->update([
            'title' => 'Updated Title',
        ]);

        // Check if update was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Event::class,
            'subject_id' => $event->id,
            'event' => 'updated',
        ]);

        // Retrieve the update activity
        $activity = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);

        // Check that only dirty attributes are logged
        $properties = $activity->properties;
        $this->assertTrue($properties->has('attributes'));
        $this->assertTrue($properties->has('old'));

        // Verify the title change was logged
        $this->assertEquals('Updated Title', $properties['attributes']['title'] ?? null);
        $this->assertEquals('Original Title', $properties['old']['title'] ?? null);
    }

    /** @test */
    public function it_logs_model_deletion(): void
    {
        // Create an event
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $eventId = $event->id;

        // Delete the event
        $event->delete();

        // Check if deletion was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Event::class,
            'subject_id' => $eventId,
            'event' => 'deleted',
        ]);
    }

    /** @test */
    public function it_only_logs_dirty_attributes(): void
    {
        // Create an event
        $event = Event::factory()->create([
            'title' => 'Test Event',
            'description' => 'Test Description',
            'user_id' => $this->user->id,
        ]);

        // Get the initial activity count
        $initialActivityCount = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->count();

        // Update without changing anything
        $event->update([
            'title' => 'Test Event', // Same value
        ]);

        // Should not create a new activity log since nothing changed
        $finalActivityCount = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->count();

        $this->assertEquals($initialActivityCount, $finalActivityCount);

        // Now update with actual changes
        $event->update([
            'title' => 'New Title',
        ]);

        // Should create a new activity log
        $newActivityCount = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->count();

        $this->assertGreaterThan($finalActivityCount, $newActivityCount);
    }

    /** @test */
    public function it_logs_all_fillable_attributes(): void
    {
        // Create an event with multiple attributes
        $event = Event::factory()->create([
            'title' => 'Test Event',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'user_id' => $this->user->id,
        ]);

        // Retrieve the activity
        $activity = Activity::where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);

        $properties = $activity->properties;

        // Check that fillable attributes are logged
        $this->assertTrue($properties->has('attributes'));
        $attributes = $properties['attributes'];

        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('location', $attributes);
    }
}
