<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventParticipantSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'create events']);
    }

    public function test_event_can_be_created_with_participants(): void
    {
        $creator = User::factory()->create();
        $creator->givePermissionTo('create events');

        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('events.store'), [
            'title' => 'Event with Participants',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
            'participant_ids' => [$participant1->id, $participant2->id],
        ]);

        $response->assertRedirect(route('events.index'));

        $event = \App\Models\Event::where('title', 'Event with Participants')->first();
        $this->assertNotNull($event);
        $this->assertEquals(2, $event->participants()->count());
        $this->assertTrue($event->participants->contains($participant1));
        $this->assertTrue($event->participants->contains($participant2));
    }

    public function test_event_can_be_created_without_participants(): void
    {
        $creator = User::factory()->create();
        $creator->givePermissionTo('create events');

        $response = $this->actingAs($creator)->post(route('events.store'), [
            'title' => 'Event without Participants',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
        ]);

        $response->assertRedirect(route('events.index'));

        $event = \App\Models\Event::where('title', 'Event without Participants')->first();
        $this->assertNotNull($event);
        $this->assertEquals(0, $event->participants()->count());
    }

    public function test_validation_fails_for_invalid_participant_ids(): void
    {
        $creator = User::factory()->create();
        $creator->givePermissionTo('create events');

        $response = $this->actingAs($creator)->post(route('events.store'), [
            'title' => 'Event with Invalid Participants',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
            'participant_ids' => [99999, 88888], // Non-existent user IDs
        ]);

        $response->assertSessionHasErrors('participant_ids.0');
    }

    public function test_multiple_participants_can_be_added_at_creation(): void
    {
        $creator = User::factory()->create();
        $creator->givePermissionTo('create events');

        $participants = User::factory()->count(5)->create();

        $response = $this->actingAs($creator)->post(route('events.store'), [
            'title' => 'Event with Multiple Participants',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
            'participant_ids' => $participants->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('events.index'));

        $event = \App\Models\Event::where('title', 'Event with Multiple Participants')->first();
        $this->assertEquals(5, $event->participants()->count());

        foreach ($participants as $participant) {
            $this->assertTrue($event->participants->contains($participant));
        }
    }

    public function test_api_users_endpoint_returns_users(): void
    {
        $user = User::factory()->create();

        User::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('api.users.index'));

        $response->assertOk();
        $response->assertJsonCount(4); // 1 authenticated user + 3 created
    }

    public function test_api_users_endpoint_can_search(): void
    {
        $user = User::factory()->create();

        User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Johnson', 'email' => 'bob@example.com']);

        $response = $this->actingAs($user)->get(route('api.users.index', ['search' => 'john']));

        $response->assertOk();
        $data = $response->json();

        $this->assertGreaterThanOrEqual(2, count($data)); // Should find "John Doe" and "Bob Johnson"
    }

    public function test_api_users_endpoint_limits_results(): void
    {
        $user = User::factory()->create();

        User::factory()->count(60)->create();

        $response = $this->actingAs($user)->get(route('api.users.index'));

        $response->assertOk();
        $data = $response->json();

        $this->assertLessThanOrEqual(50, count($data));
    }

    public function test_guest_cannot_access_users_api(): void
    {
        $response = $this->get(route('api.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_participants_are_attached_with_timestamps(): void
    {
        $creator = User::factory()->create();
        $creator->givePermissionTo('create events');

        $participant = User::factory()->create();

        $this->actingAs($creator)->post(route('events.store'), [
            'title' => 'Event with Timestamped Participant',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
            'participant_ids' => [$participant->id],
        ]);

        $event = \App\Models\Event::where('title', 'Event with Timestamped Participant')->first();
        $attachedParticipant = $event->participants()->first();

        $this->assertNotNull($attachedParticipant->pivot->created_at);
        $this->assertNotNull($attachedParticipant->pivot->updated_at);
    }
}
