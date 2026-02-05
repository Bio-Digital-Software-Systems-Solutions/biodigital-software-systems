<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventTabPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $eventCreator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base event permissions
        Permission::create(['name' => 'view events']);
        Permission::create(['name' => 'create events']);
        Permission::create(['name' => 'edit events']);
        Permission::create(['name' => 'delete events']);

        // Create tab-specific permissions
        Permission::create(['name' => 'view event gallery']);
        Permission::create(['name' => 'manage tickets']);
        Permission::create(['name' => 'view registrations']);
        Permission::create(['name' => 'manage registrations']);
        Permission::create(['name' => 'checkin events']);
        Permission::create(['name' => 'view event analytics']);

        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view events', 'create events', 'edit events', 'delete events',
            'view event gallery', 'manage tickets', 'view registrations',
            'manage registrations', 'checkin events', 'view event analytics',
        ]);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view events']);

        $eventManagerRole = Role::create(['name' => 'event-manager']);
        $eventManagerRole->givePermissionTo([
            'view events', 'create events', 'edit events', 'delete events',
            'view event gallery', 'manage tickets', 'view registrations',
            'manage registrations', 'checkin events', 'view event analytics',
        ]);

        // Create event creator and event
        $this->eventCreator = User::factory()->create();
        $this->eventCreator->assignRole('member');

        $this->event = Event::factory()->create([
            'user_id' => $this->eventCreator->id,
        ]);
    }

    /**
     * Test that SuperAdmin can access all tabs
     */
    public function test_super_admin_has_all_tab_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('tabPermissions')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewAnalytics', true)
        );
    }

    /**
     * Test that event creator can access all tabs
     */
    public function test_event_creator_has_all_tab_permissions(): void
    {
        $response = $this->actingAs($this->eventCreator)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('tabPermissions')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewAnalytics', true)
        );
    }

    /**
     * Test that admin can access all tabs
     */
    public function test_admin_has_all_tab_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('tabPermissions')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewAnalytics', true)
        );
    }

    /**
     * Test that member without specific permissions cannot access tabs
     */
    public function test_member_without_permissions_cannot_access_tabs(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $response = $this->actingAs($member)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('tabPermissions')
            ->where('tabPermissions.canViewGallery', false)
            ->where('tabPermissions.canManageTickets', false)
            ->where('tabPermissions.canViewRegistrations', false)
            ->where('tabPermissions.canCheckIn', false)
            ->where('tabPermissions.canViewAnalytics', false)
        );
    }

    /**
     * Test that user with 'view event gallery' permission can access gallery tab
     */
    public function test_user_with_view_event_gallery_permission_can_access_gallery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('view event gallery');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', false)
        );
    }

    /**
     * Test that user with 'manage tickets' permission can access tickets tab
     */
    public function test_user_with_manage_tickets_permission_can_access_tickets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('manage tickets');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewGallery', false)
        );
    }

    /**
     * Test that user with 'view registrations' permission can access registrations tab
     */
    public function test_user_with_view_registrations_permission_can_access_registrations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('view registrations');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canViewGallery', false)
        );
    }

    /**
     * Test that user with 'manage registrations' permission can access registrations tab
     */
    public function test_user_with_manage_registrations_permission_can_access_registrations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('manage registrations');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true) // manage registrations also grants check-in
        );
    }

    /**
     * Test that user with 'checkin events' permission can access check-in tab
     */
    public function test_user_with_checkin_events_permission_can_access_checkin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('checkin events');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewGallery', false)
        );
    }

    /**
     * Test that user with 'view event analytics' permission can access analytics tab
     */
    public function test_user_with_view_event_analytics_permission_can_access_analytics(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo('view event analytics');

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewAnalytics', true)
            ->where('tabPermissions.canViewGallery', false)
        );
    }

    /**
     * Test that event-manager role has all tab permissions
     */
    public function test_event_manager_has_all_tab_permissions(): void
    {
        $eventManager = User::factory()->create();
        $eventManager->assignRole('event-manager');

        $response = $this->actingAs($eventManager)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('tabPermissions')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewAnalytics', true)
        );
    }

    /**
     * Test that user can have multiple specific permissions
     */
    public function test_user_can_have_multiple_specific_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->givePermissionTo(['view event gallery', 'view registrations']);

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canManageTickets', false)
            ->where('tabPermissions.canCheckIn', false)
            ->where('tabPermissions.canViewAnalytics', false)
        );
    }

    /**
     * Test that unauthenticated user is redirected
     */
    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get("/events/{$this->event->uuid}");

        $response->assertRedirect('/login');
    }

    /**
     * Test that another event creator cannot access tabs on different event
     */
    public function test_another_event_creator_cannot_access_tabs_on_different_event(): void
    {
        // Create another user who created a different event
        $anotherCreator = User::factory()->create();
        $anotherCreator->assignRole('member');

        $anotherEvent = Event::factory()->create([
            'user_id' => $anotherCreator->id,
        ]);

        // This creator should not have access to the first event's tabs
        $response = $this->actingAs($anotherCreator)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewGallery', false)
            ->where('tabPermissions.canManageTickets', false)
            ->where('tabPermissions.canViewRegistrations', false)
            ->where('tabPermissions.canCheckIn', false)
            ->where('tabPermissions.canViewAnalytics', false)
        );
    }

    /**
     * Test that permissions are correctly returned for different events
     */
    public function test_permissions_are_correctly_returned_for_different_events(): void
    {
        $anotherCreator = User::factory()->create();
        $anotherCreator->assignRole('member');

        $anotherEvent = Event::factory()->create([
            'user_id' => $anotherCreator->id,
        ]);

        // Creator should have all permissions on their own event
        $response = $this->actingAs($anotherCreator)->get("/events/{$anotherEvent->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewGallery', true)
            ->where('tabPermissions.canManageTickets', true)
            ->where('tabPermissions.canViewRegistrations', true)
            ->where('tabPermissions.canCheckIn', true)
            ->where('tabPermissions.canViewAnalytics', true)
        );
    }

    /**
     * Test permission combinations dataset
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('permissionCombinationsProvider')]
    public function test_permission_combinations(array $permissions, array $expectedAccess): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        $response = $this->actingAs($user)->get("/events/{$this->event->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('tabPermissions.canViewGallery', $expectedAccess['gallery'])
            ->where('tabPermissions.canManageTickets', $expectedAccess['tickets'])
            ->where('tabPermissions.canViewRegistrations', $expectedAccess['registrations'])
            ->where('tabPermissions.canCheckIn', $expectedAccess['checkin'])
            ->where('tabPermissions.canViewAnalytics', $expectedAccess['analytics'])
        );
    }

    public static function permissionCombinationsProvider(): array
    {
        return [
            'no permissions' => [
                'permissions' => [],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => false,
                    'registrations' => false,
                    'checkin' => false,
                    'analytics' => false,
                ],
            ],
            'only gallery' => [
                'permissions' => ['view event gallery'],
                'expectedAccess' => [
                    'gallery' => true,
                    'tickets' => false,
                    'registrations' => false,
                    'checkin' => false,
                    'analytics' => false,
                ],
            ],
            'only tickets' => [
                'permissions' => ['manage tickets'],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => true,
                    'registrations' => false,
                    'checkin' => false,
                    'analytics' => false,
                ],
            ],
            'only view registrations' => [
                'permissions' => ['view registrations'],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => false,
                    'registrations' => true,
                    'checkin' => false,
                    'analytics' => false,
                ],
            ],
            'manage registrations grants both registrations and checkin' => [
                'permissions' => ['manage registrations'],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => false,
                    'registrations' => true,
                    'checkin' => true,
                    'analytics' => false,
                ],
            ],
            'only checkin' => [
                'permissions' => ['checkin events'],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => false,
                    'registrations' => false,
                    'checkin' => true,
                    'analytics' => false,
                ],
            ],
            'only analytics' => [
                'permissions' => ['view event analytics'],
                'expectedAccess' => [
                    'gallery' => false,
                    'tickets' => false,
                    'registrations' => false,
                    'checkin' => false,
                    'analytics' => true,
                ],
            ],
            'gallery and analytics' => [
                'permissions' => ['view event gallery', 'view event analytics'],
                'expectedAccess' => [
                    'gallery' => true,
                    'tickets' => false,
                    'registrations' => false,
                    'checkin' => false,
                    'analytics' => true,
                ],
            ],
            'all permissions' => [
                'permissions' => [
                    'view event gallery',
                    'manage tickets',
                    'view registrations',
                    'checkin events',
                    'view event analytics',
                ],
                'expectedAccess' => [
                    'gallery' => true,
                    'tickets' => true,
                    'registrations' => true,
                    'checkin' => true,
                    'analytics' => true,
                ],
            ],
        ];
    }
}
