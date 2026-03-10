<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PastEventRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $superAdmin;

    protected User $regularUser;

    protected Event $pastEvent;

    protected Event $futureEvent;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view events']);
        Permission::create(['name' => 'create events']);
        Permission::create(['name' => 'edit events']);
        Permission::create(['name' => 'delete events']);
        Permission::create(['name' => 'attend events']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view events', 'create events', 'edit events', 'delete events', 'attend events']);

        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view events', 'attend events']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('member');

        // Create events
        $this->pastEvent = Event::factory()->create([
            'title' => 'Past Event',
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->subDays(2)->addHours(2),
            'user_id' => $this->admin->id,
            'is_public' => true, // Make sure the event is public
        ]);

        $this->futureEvent = Event::factory()->create([
            'title' => 'Future Event',
            'start_date' => Carbon::now()->addDays(2),
            'end_date' => Carbon::now()->addDays(2)->addHours(2),
            'user_id' => $this->admin->id,
            'is_public' => true, // Make sure the event is public
        ]);
    }

    public function test_admin_cannot_edit_past_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('events.update', $this->pastEvent->uuid), [
                'title' => 'Updated Past Event',
                'description' => 'Updated description',
                'start_date' => $this->pastEvent->start_date->format('Y-m-d H:i:s'),
                'end_date' => $this->pastEvent->end_date->format('Y-m-d H:i:s'),
                'location' => 'Updated location',
                'is_public' => true,
                'status' => 'completed',
            ]);

        // Should be denied by authorization policy
        $response->assertStatus(403);

        // Verify event was not updated
        $this->pastEvent->refresh();
        $this->assertEquals('Past Event', $this->pastEvent->title);
    }

    public function test_super_admin_can_edit_past_event(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('events.update', $this->pastEvent->uuid), [
                'title' => 'Updated Past Event by SuperAdmin',
                'description' => 'Updated description',
                'start_date' => $this->pastEvent->start_date->format('Y-m-d H:i:s'),
                'end_date' => $this->pastEvent->end_date->format('Y-m-d H:i:s'),
                'location' => 'Updated location',
                'is_public' => true,
                'status' => 'completed',
            ]);

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('message', 'Événement mis à jour avec succès.');

        // Verify event was updated
        $this->pastEvent->refresh();
        $this->assertEquals('Updated Past Event by SuperAdmin', $this->pastEvent->title);
    }

    public function test_admin_can_edit_future_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('events.update', $this->futureEvent->uuid), [
                'title' => 'Updated Future Event',
                'description' => 'Updated description',
                'start_date' => $this->futureEvent->start_date->format('Y-m-d H:i:s'),
                'end_date' => $this->futureEvent->end_date->format('Y-m-d H:i:s'),
                'location' => 'Updated location',
                'is_public' => true,
                'status' => 'planned',
            ]);

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('message', 'Événement mis à jour avec succès.');

        // Verify event was updated
        $this->futureEvent->refresh();
        $this->assertEquals('Updated Future Event', $this->futureEvent->title);
    }

    public function test_admin_cannot_delete_past_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('events.destroy', $this->pastEvent->uuid));

        // Should be denied by authorization policy
        $response->assertStatus(403);

        // Verify event was not deleted
        $this->assertDatabaseHas('events', [
            'id' => $this->pastEvent->id,
            'title' => 'Past Event',
        ]);
    }

    public function test_super_admin_can_delete_past_event(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('events.destroy', $this->pastEvent->uuid));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('message', 'Événement supprimé avec succès.');

        // Verify event was deleted
        $this->assertDatabaseMissing('events', [
            'id' => $this->pastEvent->id,
        ]);
    }

    public function test_admin_can_delete_future_event(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('events.destroy', $this->futureEvent->uuid));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('message', 'Événement supprimé avec succès.');

        // Verify event was deleted
        $this->assertDatabaseMissing('events', [
            'id' => $this->futureEvent->id,
        ]);
    }

    public function test_regular_user_cannot_join_past_event(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('events.join', $this->pastEvent->uuid));

        // Should be denied by authorization policy
        $response->assertStatus(403);

        // Verify user was not added to event
        $this->assertFalse($this->pastEvent->participants->contains($this->regularUser));
    }

    public function test_super_admin_can_join_past_event(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('events.join', $this->pastEvent->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Vous participez maintenant à l\'événement.');

        // Verify super admin was added to event
        $this->pastEvent->refresh();
        $this->assertTrue($this->pastEvent->participants->contains($this->superAdmin));
    }

    public function test_regular_user_can_join_future_event(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('events.join', $this->futureEvent->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Vous participez maintenant à l\'événement.');

        // Verify user was added to event
        $this->futureEvent->refresh();
        $this->assertTrue($this->futureEvent->participants->contains($this->regularUser));
    }

    public function test_regular_user_cannot_leave_past_event(): void
    {
        // First add user to past event directly in database
        $this->pastEvent->participants()->attach($this->regularUser);

        $response = $this->actingAs($this->regularUser)
            ->delete(route('events.leave', $this->pastEvent->uuid));

        // Should be denied by authorization policy
        $response->assertStatus(403);

        // Verify user is still in event
        $this->pastEvent->refresh();
        $this->assertTrue($this->pastEvent->participants->contains($this->regularUser));
    }

    public function test_super_admin_can_leave_past_event(): void
    {
        // First add super admin to past event
        $this->pastEvent->participants()->attach($this->superAdmin);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('events.leave', $this->pastEvent->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Vous avez quitté l\'événement.');

        // Verify super admin was removed from event
        $this->pastEvent->refresh();
        $this->assertFalse($this->pastEvent->participants->contains($this->superAdmin));
    }

    public function test_event_model_can_be_modified_by_method(): void
    {
        // Test regular admin with future event
        $this->assertTrue($this->futureEvent->canBeModifiedBy($this->admin));

        // Test regular admin with past event
        $this->assertFalse($this->pastEvent->canBeModifiedBy($this->admin));

        // Test super admin with past event
        $this->assertTrue($this->pastEvent->canBeModifiedBy($this->superAdmin));

        // Test super admin with future event
        $this->assertTrue($this->futureEvent->canBeModifiedBy($this->superAdmin));

        // Test with null user
        $this->assertFalse($this->pastEvent->canBeModifiedBy());
    }

    public function test_event_model_can_accept_participation_changes_method(): void
    {
        // Test regular user with future event
        $this->assertTrue($this->futureEvent->canAcceptParticipationChanges($this->regularUser));

        // Test regular user with past event
        $this->assertFalse($this->pastEvent->canAcceptParticipationChanges($this->regularUser));

        // Test super admin with past event
        $this->assertTrue($this->pastEvent->canAcceptParticipationChanges($this->superAdmin));

        // Test super admin with future event
        $this->assertTrue($this->futureEvent->canAcceptParticipationChanges($this->superAdmin));

        // Test with null user
        $this->assertFalse($this->pastEvent->canAcceptParticipationChanges());
    }

    public function test_toggle_participation_respects_past_event_restrictions(): void
    {
        // Test regular user cannot toggle participation on past event
        $response = $this->actingAs($this->regularUser)
            ->post(route('events.toggle-participation', $this->pastEvent->uuid));

        // Should be denied by authorization policy
        $response->assertStatus(403);

        // Test super admin can toggle participation on past event
        $response = $this->actingAs($this->superAdmin)
            ->post(route('events.toggle-participation', $this->pastEvent->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('message');

        // Test regular user can toggle participation on future event
        $response = $this->actingAs($this->regularUser)
            ->post(route('events.toggle-participation', $this->futureEvent->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('message');
    }
}
