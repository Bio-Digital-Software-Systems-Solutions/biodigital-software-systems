<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventParticipationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'view events']);
    }

    public function test_user_can_register_for_event(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create([
            'max_participants' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('events.toggle-participation', $event));

        $response->assertRedirect();
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_unregister_from_event(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create();
        $event->participants()->attach($user);

        $response = $this->actingAs($user)->post(route('events.toggle-participation', $event));

        $response->assertRedirect();
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_register_for_full_event(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create([
            'max_participants' => 2,
        ]);

        // Fill the event
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();
        $event->participants()->attach([$participant1->id, $participant2->id]);

        $response = $this->actingAs($user)->post(route('events.toggle-participation', $event));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'L\'événement est complet.');
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_register_for_event_without_participant_limit(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create([
            'max_participants' => null,
            'is_public' => true,
        ]);

        $response = $this->actingAs($user)->post(route('events.toggle-participation', $event));

        $response->assertRedirect();
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_toggling_participation_twice_returns_to_original_state(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create();

        // First toggle - register
        $this->actingAs($user)->post(route('events.toggle-participation', $event));
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // Second toggle - unregister
        $this->actingAs($user)->post(route('events.toggle-participation', $event));
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_multiple_users_can_register_for_same_event(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $user1->givePermissionTo('view events');
        $user2->givePermissionTo('view events');
        $user3->givePermissionTo('view events');

        $event = Event::factory()->create([
            'max_participants' => 5,
        ]);

        $this->actingAs($user1)->post(route('events.toggle-participation', $event));
        $this->actingAs($user2)->post(route('events.toggle-participation', $event));
        $this->actingAs($user3)->post(route('events.toggle-participation', $event));

        $this->assertEquals(3, $event->fresh()->participants()->count());
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $user1->id]);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $user2->id]);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $user3->id]);
    }

    public function test_registered_user_appears_in_participants_list(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create();
        $event->participants()->attach($user);

        $response = $this->actingAs($user)->get(route('events.show', $event));

        $response->assertInertia(fn ($page) => $page->component('Events/Show')
            ->has('event.participants', 1)
            ->where('event.participants.0.id', $user->id)
        );
    }

    public function test_guest_cannot_register_for_event(): void
    {
        $event = Event::factory()->create();

        $response = $this->post(route('events.toggle-participation', $event));

        $response->assertRedirect(route('login'));
    }

    public function test_participation_includes_timestamp(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $participant = $event->fresh()->participants()->first();
        $this->assertNotNull($participant->pivot->created_at);
    }

    public function test_event_capacity_is_respected(): void
    {
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->givePermissionTo('view events');
        }

        $event = Event::factory()->create([
            'max_participants' => 2,
        ]);

        // First two users should register successfully
        $this->actingAs($users[0])->post(route('events.toggle-participation', $event));
        $this->actingAs($users[1])->post(route('events.toggle-participation', $event));

        $this->assertEquals(2, $event->fresh()->participants()->count());

        // Third user should be rejected
        $response = $this->actingAs($users[2])->post(route('events.toggle-participation', $event));
        $response->assertSessionHas('error');

        $this->assertEquals(2, $event->fresh()->participants()->count());
    }
}
