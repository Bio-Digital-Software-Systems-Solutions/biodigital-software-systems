<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'create events']);
        Permission::create(['name' => 'view events']);
    }

    public function test_quick_create_modal_can_create_event_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Quick Event',
            'description' => 'Created from quick modal',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'Conference Room',
            'is_public' => true,
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Quick Event',
            'description' => 'Created from quick modal',
            'location' => 'Conference Room',
            'is_public' => true,
        ]);
    }

    public function test_quick_create_modal_can_create_task_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'task',
            'title' => 'Quick Task',
            'description' => 'Task created from modal',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'is_public' => false,
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Quick Task',
            'description' => 'Task created from modal',
            'is_public' => false,
        ]);
    }

    public function test_quick_create_modal_can_create_appointment_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'appointment',
            'title' => 'Quick Appointment',
            'description' => 'Appointment from modal',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addMinutes(30)->format('Y-m-d H:i:s'),
            'location' => 'Office 101',
            'is_public' => true,
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Quick Appointment',
            'description' => 'Appointment from modal',
            'location' => 'Office 101',
        ]);
    }

    public function test_quick_create_with_address_details(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Event with Address',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'Main Hall',
            'is_public' => true,
            'address' => [
                'street' => '123 Main St',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
            ],
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Event with Address',
            'location' => 'Main Hall',
        ]);
        $this->assertDatabaseHas('addresses', [
            'street' => '123 Main St',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
        ]);
    }

    public function test_quick_create_with_max_participants(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Limited Event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'max_participants' => 50,
            'is_public' => true,
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Limited Event',
            'max_participants' => 50,
        ]);
    }

    public function test_quick_create_requires_title(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_quick_create_validates_dates(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        // End date before start date
        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Invalid Dates Event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->format('Y-m-d H:i:s'), // Before start
            'is_public' => true,
        ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_quick_create_validates_type(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'invalid_type',
            'title' => 'Event with Invalid Type',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_quick_create_requires_permission(): void
    {
        $user = User::factory()->create();
        // User does not have 'create events' permission

        $response = $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Unauthorized Event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_quick_create_assigns_random_color(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Colored Event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'is_public' => true,
        ]);

        $event = \App\Models\Event::where('title', 'Colored Event')->first();
        $this->assertNotNull($event->color);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $event->color);
    }

    public function test_quick_create_defaults_to_public(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create events');

        $this->actingAs($user)->post(route('events.store'), [
            'type' => 'event',
            'title' => 'Default Public Event',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Default Public Event',
            'is_public' => true,
        ]);
    }
}
