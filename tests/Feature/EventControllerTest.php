<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::create(['name' => 'view events']);
        Permission::create(['name' => 'create events']);
        Permission::create(['name' => 'edit events']);
        Permission::create(['name' => 'delete events']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view events', 'create events', 'edit events', 'delete events']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view events']);
    }

    public function test_authenticated_user_can_view_events_index()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Events/Index'));
    }

    public function test_authenticated_user_with_permission_can_create_event()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/events/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Events/Create'));
    }

    public function test_user_without_permission_cannot_create_event()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/events/create');

        // Laravel redirects when permission denied (can be 403 or 302)
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect()
        );
    }

    public function test_user_can_store_event()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_date' => '2024-12-01 10:00:00',
            'end_date' => '2024-12-01 12:00:00',
            'location' => 'Test Location',
            'max_participants' => 50,
            'is_public' => true,
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_store_event_with_address()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Test Event with Address',
            'description' => 'Test Description',
            'start_date' => '2024-12-01 10:00:00',
            'end_date' => '2024-12-01 12:00:00',
            'location' => 'Test Location',
            'max_participants' => 50,
            'is_public' => true,
            'address' => [
                'street' => '123 Test Street',
                'city' => 'Test City',
                'postal_code' => '12345',
                'country' => 'Test Country',
            ],
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'title' => 'Test Event with Address',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('addresses', [
            'street' => '123 Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
        ]);
    }

    public function test_event_creation_validates_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/events', []);

        $response->assertSessionHasErrors(['title', 'start_date', 'end_date']);
    }

    public function test_event_creation_validates_end_date_after_start_date()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Test Event',
            'start_date' => '2024-12-01 12:00:00',
            'end_date' => '2024-12-01 10:00:00', // Before start date
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_event_creation_validates_max_participants_minimum()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Test Event',
            'start_date' => '2024-12-01 10:00:00',
            'end_date' => '2024-12-01 12:00:00',
            'max_participants' => 0, // Invalid minimum
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertSessionHasErrors(['max_participants']);
    }

    public function test_user_can_view_single_event()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/events/{$event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Events/Show')
            ->has('event.title')
        );
    }

    public function test_user_can_toggle_participation()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $event = Event::factory()->create([
            'max_participants' => 10,
            'is_public' => true,
        ]);

        // Join event
        $response = $this->actingAs($user)->post("/events/{$event->uuid}/toggle-participation");

        $response->assertRedirect();

        // Refresh event to get updated participants
        $event->refresh();
        $this->assertTrue($event->participants()->where('user_id', $user->id)->exists());

        // Leave event
        $response = $this->actingAs($user)->post("/events/{$event->uuid}/toggle-participation");

        $response->assertRedirect();
        $this->assertFalse($event->participants()->where('user_id', $user->id)->exists());
    }

    public function test_user_cannot_join_full_event()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $event = Event::factory()->create([
            'max_participants' => 1,
            'is_public' => true,
        ]);

        // Fill the event
        $otherUser = User::factory()->create();
        $event->participants()->attach($otherUser->id);

        $response = $this->actingAs($user)->post("/events/{$event->uuid}/toggle-participation");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse($event->participants()->where('user_id', $user->id)->exists());
    }

    public function test_user_can_update_own_event()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'location' => $event->location,
            'max_participants' => $event->max_participants,
            'is_public' => $event->is_public,
            'status' => 'planned',
        ];

        $response = $this->actingAs($user)->put("/events/{$event->uuid}", $updateData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_update_event_with_address()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $address = Address::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'address_id' => $address->id,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'location' => $event->location,
            'max_participants' => $event->max_participants,
            'is_public' => $event->is_public,
            'status' => 'planned',
            'address' => [
                'street' => 'Updated Street',
                'city' => 'Updated City',
                'postal_code' => '54321',
                'country' => 'Updated Country',
            ],
        ];

        $response = $this->actingAs($user)->put("/events/{$event->uuid}", $updateData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => 'Updated Street',
            'city' => 'Updated City',
        ]);
    }

    public function test_user_can_update_event_status()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
        ]);

        $updateData = [
            'title' => $event->title,
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'location' => $event->location,
            'max_participants' => $event->max_participants,
            'is_public' => $event->is_public,
            'status' => 'ongoing',
        ];

        $response = $this->actingAs($user)->put("/events/{$event->uuid}", $updateData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'ongoing',
        ]);
    }

    public function test_event_update_validates_status_values()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'title' => $event->title,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($user)->put("/events/{$event->uuid}", $updateData);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_user_can_delete_own_event()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/events/{$event->uuid}");

        $response->assertRedirect('/events');
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_guest_cannot_access_events()
    {
        $response = $this->get('/events');
        $response->assertRedirect('/login');
    }
}
