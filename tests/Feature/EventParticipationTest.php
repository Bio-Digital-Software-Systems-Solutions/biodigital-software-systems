<?php

namespace Tests\Feature;

use App\Enums\Event\ParticipantRole;
use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventRegistration;
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
            'is_public' => true,
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
            'is_public' => true,
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

        $event = Event::factory()->create(['is_public' => true]);

        // First toggle - register
        $this->actingAs($user)->post(route('events.toggle-participation', $event));
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // Reload event to have fresh participants collection
        $event->refresh();

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
            'is_public' => true,
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
            'is_public' => true,
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

    // ============================================
    // Tests for participant/registration synchronization
    // ============================================

    public function test_registration_is_created_when_user_joins_event(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        // Check participant exists
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // Check registration was also created
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::CONFIRMED->value,
            'participant_role' => ParticipantRole::ATTENDEE->value,
        ]);

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($registration);
        $this->assertEquals('John', $registration->first_name);
        $this->assertEquals('Doe', $registration->last_name);
        $this->assertEquals('john.doe@example.com', $registration->email);
        $this->assertEquals(1, $registration->quantity);
    }

    public function test_registration_is_cancelled_when_user_leaves_event(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        // Join first
        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        // Verify registration exists
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::CONFIRMED->value,
        ]);

        // Leave the event
        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        // Check participant removed
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // Check registration was cancelled
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::CANCELLED->value,
        ]);
    }

    public function test_join_route_creates_registration(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        $this->actingAs($user)->post(route('events.join', $event));

        // Check both participant and registration exist
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::CONFIRMED->value,
        ]);
    }

    public function test_leave_route_cancels_registration(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        // Join first using join route
        $this->actingAs($user)->post(route('events.join', $event));

        // Reload the event to have updated participants
        $event->refresh();

        // Leave using leave route (DELETE method)
        $this->actingAs($user)->delete(route('events.leave', $event));

        // Check participant removed
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        // Check registration cancelled
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::CANCELLED->value,
        ]);
    }

    public function test_registrations_count_matches_participants_count(): void
    {
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->givePermissionTo('view events');
        }

        $event = Event::factory()->create(['is_public' => true]);

        foreach ($users as $user) {
            $this->actingAs($user)->post(route('events.toggle-participation', $event));
        }

        $event->refresh();

        $participantCount = $event->participants()->count();
        $registrationCount = EventRegistration::where('event_id', $event->id)
            ->where('status', RegistrationStatus::CONFIRMED)
            ->count();

        $this->assertEquals(3, $participantCount);
        $this->assertEquals(3, $registrationCount);
        $this->assertEquals($participantCount, $registrationCount);
    }

    public function test_duplicate_registration_is_prevented(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        // Manually create a registration first
        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? $user->name,
            'last_name' => $user->last_name ?? '',
            'email' => $user->email,
            'status' => RegistrationStatus::CONFIRMED,
            'participant_role' => ParticipantRole::ATTENDEE,
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'currency' => 'EUR',
        ]);

        // Now add to participants without triggering the controller (simulating old data)
        $event->participants()->attach($user);
        $event->refresh();

        // Use toggle to leave (should detect existing registration and cancel it)
        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        // The registration should be cancelled (not duplicated)
        $cancelledCount = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', RegistrationStatus::CANCELLED)
            ->count();

        $this->assertEquals(1, $cancelledCount);
    }

    public function test_joining_event_does_not_create_duplicate_registration(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view events');

        $event = Event::factory()->create(['is_public' => true]);

        // Join the event twice (first toggle on, second should toggle off)
        $this->actingAs($user)->post(route('events.toggle-participation', $event));

        // Count confirmed registrations
        $confirmedCount = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', RegistrationStatus::CONFIRMED)
            ->count();

        $this->assertEquals(1, $confirmedCount);

        // Join again via join route (should say already participating)
        $response = $this->actingAs($user)->post(route('events.join', $event));
        $response->assertSessionHas('message', 'Vous participez déjà à cet événement.');

        // Still only one registration
        $confirmedCount = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', RegistrationStatus::CONFIRMED)
            ->count();

        $this->assertEquals(1, $confirmedCount);
    }
}
