<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventCrudFormTest extends TestCase
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

        $eventManagerRole = Role::create(['name' => 'event-manager']);
        $eventManagerRole->givePermissionTo(['view events', 'create events', 'edit events']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view events']);
    }

    public function test_create_form_displays_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/events/create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Events/Create')
            );
    }

    public function test_edit_form_displays_with_event_data()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $address = Address::factory()->create([
            'street' => '123 Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
        ]);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'max_participants' => 50,
            'is_public' => true,
            'status' => 'planned',
            'address_id' => $address->id,
        ]);

        $response = $this->actingAs($user)->get("/events/{$event->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Events/Edit')
                ->has('event')
                ->where('event.title', 'Test Event')
                ->where('event.description', 'Test Description')
                ->where('event.location', 'Test Location')
                ->where('event.max_participants', 50)
                ->where('event.is_public', true)
                ->where('event.status', 'planned')
                ->has('event.address')
                ->where('event.address.street', '123 Test Street')
                ->where('event.address.city', 'Test City')
            );
    }

    public function test_create_form_submission_with_complete_data()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Complete Test Event',
            'description' => 'Complete test description',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'location' => 'Complete Test Location',
            'max_participants' => '100',
            'is_public' => true,
            'address' => [
                'street' => '456 Complete Street',
                'city' => 'Complete City',
                'postal_code' => '67890',
                'country' => 'Complete Country',
            ],
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('events', [
            'title' => 'Complete Test Event',
            'description' => 'Complete test description',
            'location' => 'Complete Test Location',
            'max_participants' => 100,
            'is_public' => true,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('addresses', [
            'street' => '456 Complete Street',
            'city' => 'Complete City',
            'postal_code' => '67890',
            'country' => 'Complete Country',
        ]);
    }

    public function test_create_form_submission_with_minimal_data()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $eventData = [
            'title' => 'Minimal Test Event',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'is_public' => false,
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('events', [
            'title' => 'Minimal Test Event',
            'description' => null,
            'location' => null,
            'max_participants' => null,
            'is_public' => false,
            'user_id' => $user->id,
        ]);
    }

    public function test_edit_form_submission_updates_event()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $address = Address::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'status' => 'planned',
            'address_id' => $address->id,
        ]);

        $updateData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated description',
            'start_date' => $event->start_date->format('Y-m-d\TH:i'),
            'end_date' => $event->end_date->format('Y-m-d\TH:i'),
            'location' => 'Updated Location',
            'max_participants' => '75',
            'is_public' => false,
            'status' => 'ongoing',
            'address' => [
                'street' => 'Updated Street',
                'city' => 'Updated City',
                'postal_code' => 'UP001',
                'country' => 'Updated Country',
            ],
        ];

        $response = $this->actingAs($user)->put("/events/{$event->id}", $updateData);

        $response->assertRedirect('/events')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'description' => 'Updated description',
            'location' => 'Updated Location',
            'max_participants' => 75,
            'is_public' => false,
            'status' => 'ongoing',
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => 'Updated Street',
            'city' => 'Updated City',
            'postal_code' => 'UP001',
            'country' => 'Updated Country',
        ]);
    }

    public function test_form_validation_errors_are_displayed()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Test create form validation
        $response = $this->actingAs($user)->post('/events', [
            'title' => '', // Required field missing
            'start_date' => '',
            'end_date' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'start_date', 'end_date']);

        // Test update form validation
        $event = Event::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/events/{$event->id}", [
            'title' => '', // Required field missing
            'start_date' => '2024-12-01T12:00',
            'end_date' => '2024-12-01T10:00', // End before start
            'status' => 'invalid_status', // Invalid status
        ]);

        $response->assertSessionHasErrors(['title', 'end_date', 'status']);
    }

    public function test_date_time_formatting_in_forms()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2024-12-01 10:30:00',
            'end_date' => '2024-12-01 14:45:00',
        ]);

        $response = $this->actingAs($user)->get("/events/{$event->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Events/Edit')
                ->where('event.start_date', '2024-12-01T10:30:00.000000Z')
                ->where('event.end_date', '2024-12-01T14:45:00.000000Z')
            );
    }

    public function test_address_toggle_functionality()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create event with empty address data - should not create address
        $eventData = [
            'title' => 'Event Without Address',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'address' => [
                'street' => '',
                'city' => '',
                'postal_code' => '',
                'country' => '',
            ],
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events');

        $event = Event::where('title', 'Event Without Address')->first();
        $this->assertNull($event->address_id);

        // Create event with partial address data - should create address
        $eventDataWithAddress = [
            'title' => 'Event With Partial Address',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'address' => [
                'street' => 'Some Street',
                'city' => 'Some City',
                'postal_code' => '',
                'country' => 'Some Country',
            ],
        ];

        $response = $this->actingAs($user)->post('/events', $eventDataWithAddress);

        $response->assertRedirect('/events');

        $eventWithAddress = Event::where('title', 'Event With Partial Address')->first();
        $this->assertNotNull($eventWithAddress->address_id);
        $this->assertDatabaseHas('addresses', [
            'id' => $eventWithAddress->address_id,
            'street' => 'Some Street',
            'city' => 'Some City',
            'country' => 'Some Country',
        ]);
    }

    public function test_public_private_event_setting()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Test public event creation (default)
        $publicEventData = [
            'title' => 'Public Event',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'is_public' => true,
        ];

        $response = $this->actingAs($user)->post('/events', $publicEventData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'title' => 'Public Event',
            'is_public' => true,
        ]);

        // Test private event creation
        $privateEventData = [
            'title' => 'Private Event',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'is_public' => false,
        ];

        $response = $this->actingAs($user)->post('/events', $privateEventData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'title' => 'Private Event',
            'is_public' => false,
        ]);
    }

    public function test_max_participants_validation()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Test with valid max participants
        $eventData = [
            'title' => 'Limited Event',
            'description' => 'Test description',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'max_participants' => '25',
        ];

        $response = $this->actingAs($user)->post('/events', $eventData);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('events', [
            'title' => 'Limited Event',
            'max_participants' => 25,
        ]);

        // Test with invalid max participants (negative)
        $invalidEventData = [
            'title' => 'Invalid Event',
            'description' => 'Test description',
            'start_date' => '2024-12-01T10:00',
            'end_date' => '2024-12-01T12:00',
            'max_participants' => '-5',
        ];

        $response = $this->actingAs($user)->post('/events', $invalidEventData);

        $response->assertSessionHasErrors(['max_participants']);
    }

    public function test_event_form_access_permissions()
    {
        // Test member role cannot access create form
        $member = User::factory()->create();
        $member->assignRole('member');

        $response = $this->actingAs($member)->get('/events/create');
        $response->assertStatus(403);

        // Test event-manager can access create form
        $eventManager = User::factory()->create();
        $eventManager->assignRole('event-manager');

        $response = $this->actingAs($eventManager)->get('/events/create');
        $response->assertStatus(200);

        // Test admin can access create form
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/events/create');
        $response->assertStatus(200);
    }
}
