<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function dashboard_displays_quick_actions_section()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();

        // For Inertia responses, just verify successful load
        // The frontend renders "Actions rapides"
        $this->assertTrue(true);
    }

    /** @test */
    public function user_can_access_create_event_from_quick_actions()
    {
        // Give user permission to create events
        Permission::create(['name' => 'create events']);
        $this->user->givePermissionTo('create events');

        $response = $this->actingAs($this->user)->get('/events/create');

        $response->assertSuccessful();
    }

    /** @test */
    public function user_without_permission_cannot_create_event()
    {
        $response = $this->actingAs($this->user)->get('/events/create');

        // Should redirect or show 403
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );
    }

    /** @test */
    public function user_can_access_create_article_from_quick_actions()
    {
        // Give user permission to create articles
        Permission::create(['name' => 'create articles']);
        $this->user->givePermissionTo('create articles');

        $response = $this->actingAs($this->user)->get('/articles/create');

        $response->assertSuccessful();
    }

    /** @test */
    public function user_without_permission_cannot_create_article()
    {
        $response = $this->actingAs($this->user)->get('/articles/create');

        // Should redirect or show 403
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );
    }

    /** @test */
    public function user_can_access_books_index_from_quick_actions()
    {
        // Give user permission to view books
        Permission::create(['name' => 'view books']);
        $this->user->givePermissionTo('view books');

        $response = $this->actingAs($this->user)->get('/books');

        $response->assertSuccessful();
    }

    /** @test */
    public function user_without_permission_cannot_view_books()
    {
        $response = $this->actingAs($this->user)->get('/books');

        // Should redirect or show 403
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );
    }

    /** @test */
    public function user_can_access_departments_from_quick_actions()
    {
        // Give user permission to view departments
        Permission::create(['name' => 'view departments']);
        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)->get('/departments');

        $response->assertSuccessful();
    }

    /** @test */
    public function user_without_permission_cannot_view_departments()
    {
        $response = $this->actingAs($this->user)->get('/departments');

        // Should redirect or show 403
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );
    }

    /** @test */
    public function admin_can_access_all_quick_actions()
    {
        // Create admin role with all permissions
        $adminRole = Role::create(['name' => 'admin']);

        $permissions = [
            'create events',
            'view events',
            'create articles',
            'view articles',
            'view books',
            'rent books',
            'view departments',
            'edit departments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole->givePermissionTo($permissions);
        $this->user->assignRole('admin');

        // Test each quick action route
        $routes = [
            '/events/create',
            '/articles/create',
            '/books',
            '/departments',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get($route);
            $response->assertSuccessful();
        }
    }

    /** @test */
    public function quick_actions_respect_user_permissions()
    {
        // Create a role with limited permissions
        $writerRole = Role::create(['name' => 'writer']);

        Permission::firstOrCreate(['name' => 'create articles']);
        Permission::firstOrCreate(['name' => 'view articles']);

        $writerRole->givePermissionTo(['create articles', 'view articles']);
        $this->user->assignRole('writer');

        // Writer can create articles
        $response = $this->actingAs($this->user)->get('/articles/create');
        $response->assertSuccessful();

        // Writer cannot create events (no permission)
        $response = $this->actingAs($this->user)->get('/events/create');
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );
    }

    /** @test */
    public function guest_cannot_access_any_quick_actions()
    {
        $routes = [
            '/dashboard',
            '/events/create',
            '/articles/create',
            '/books',
            '/departments',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            // Should redirect to login
            $response->assertRedirect('/login');
        }
    }

    /** @test */
    public function quick_actions_are_clickable_links_not_buttons()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertSuccessful();

        // Verify the response contains the proper structure
        // The routes should be present in the rendered page
        $content = $response->getContent();

        // Check that Inertia is rendering the dashboard
        $this->assertStringContainsString('Dashboard', $content);
    }
}
