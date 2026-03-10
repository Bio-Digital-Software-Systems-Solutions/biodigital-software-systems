<?php

namespace Tests\Feature;

use App\Enums\Star\StarCategory;
use App\Enums\Star\StarStatus;
use App\Enums\Star\StarType;
use App\Models\Department;
use App\Models\Star;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view stars']);
        Permission::create(['name' => 'create stars']);
        Permission::create(['name' => 'edit stars']);
        Permission::create(['name' => 'delete stars']);
        Permission::create(['name' => 'manage stars']);

        // Create admin role with all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view stars',
            'create stars',
            'edit stars',
            'delete stars',
            'manage stars',
        ]);

        // Create member role with view only
        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view stars']);
    }

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_user_with_permission_can_view_stars_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/stars');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page->component('Stars/Index'));
    }

    public function test_index_returns_correct_data_structure(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Star::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/stars');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->has('stars')
            ->has('filters')
            ->has('statuses')
            ->has('types')
            ->has('categories')
            ->has('departments')
            ->has('stats')
        );
    }

    public function test_guest_cannot_access_stars(): void
    {
        $response = $this->get('/stars');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_stars(): void
    {
        $user = User::factory()->create();
        // No role assigned

        $response = $this->actingAs($user)->get('/stars');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Search and Filter Tests
    // ==========================================

    public function test_stars_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $userToFind = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Star::factory()->create(['user_id' => $userToFind->id]);
        Star::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/stars?search=John');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->where('filters.search', 'John')
        );
    }

    public function test_stars_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Star::factory()->active()->count(2)->create();
        Star::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/stars?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_stars_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Star::factory()->volunteer()->count(2)->create();
        Star::factory()->leader()->create();

        $response = $this->actingAs($user)->get('/stars?type=volunteer');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->where('filters.type', 'volunteer')
        );
    }

    public function test_stars_can_be_filtered_by_department(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $department = Department::factory()->create();
        Star::factory()->inDepartment($department)->count(2)->create();
        Star::factory()->create();

        $response = $this->actingAs($user)->get("/stars?department={$department->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->where('filters.department', (string) $department->id)
        );
    }

    // ==========================================
    // Show Tests
    // ==========================================

    public function test_user_can_view_single_star(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $star = Star::factory()->create();

        $response = $this->actingAs($user)->get("/stars/{$star->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Show')
            ->has('star')
            ->has('canManage')
        );
    }

    public function test_show_returns_star_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $star = Star::factory()->create([
            'title' => 'Star de l\'accueil',
            'points' => 500,
            'level' => 3,
        ]);

        $response = $this->actingAs($user)->get("/stars/{$star->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Show')
            ->where('star.title', 'Star de l\'accueil')
            ->where('star.points', 500)
            ->where('star.level', 3)
        );
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_with_permission_can_view_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/stars/create');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Create')
            ->has('users')
            ->has('departments')
            ->has('nominators')
            ->has('statuses')
            ->has('types')
            ->has('categories')
        );
    }

    public function test_user_without_permission_cannot_view_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/stars/create');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_admin_can_create_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $starUser = User::factory()->create();
        $department = Department::factory()->create();

        $starData = [
            'user_id' => $starUser->id,
            'department_id' => $department->id,
            'title' => 'Bénévole du mois',
            'status' => StarStatus::ACTIVE->value,
            'type' => StarType::VOLUNTEER->value,
            'category' => StarCategory::SERVICE->value,
            'points' => 100,
            'level' => 1,
            'recognition_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($admin)->post('/stars', $starData);

        $response->assertRedirect();
        $this->assertDatabaseHas('stars', [
            'user_id' => $starUser->id,
            'title' => 'Bénévole du mois',
        ]);
    }

    public function test_cannot_create_star_for_same_user_twice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $starUser = User::factory()->create();
        Star::factory()->create(['user_id' => $starUser->id]);

        $starData = [
            'user_id' => $starUser->id,
            'title' => 'New Star',
            'status' => StarStatus::ACTIVE->value,
            'type' => StarType::VOLUNTEER->value,
        ];

        $response = $this->actingAs($admin)->post('/stars', $starData);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_validation_errors_on_create(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/stars', [
            'user_id' => '',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['user_id', 'status']);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_user_with_permission_can_view_edit_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $star = Star::factory()->create();

        $response = $this->actingAs($user)->get("/stars/{$star->uuid}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Edit')
            ->has('star')
            ->has('departments')
            ->has('nominators')
            ->has('statuses')
            ->has('types')
            ->has('categories')
        );
    }

    public function test_user_without_permission_cannot_view_edit_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $star = Star::factory()->create();

        $response = $this->actingAs($user)->get("/stars/{$star->uuid}/edit");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_admin_can_update_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->create([
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'points' => 200,
            'status' => StarStatus::ACTIVE->value,
            'type' => StarType::LEADER->value,
        ];

        $response = $this->actingAs($admin)->put("/stars/{$star->uuid}", $updateData);

        $response->assertRedirect("/stars/{$star->uuid}");
        $this->assertDatabaseHas('stars', [
            'id' => $star->id,
            'title' => 'Updated Title',
            'points' => 200,
        ]);
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_admin_can_delete_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->create();

        $response = $this->actingAs($admin)->delete("/stars/{$star->uuid}");

        $response->assertRedirect('/stars');
        $this->assertSoftDeleted('stars', [
            'id' => $star->id,
        ]);
    }

    public function test_member_cannot_delete_star(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $star = Star::factory()->create();

        $response = $this->actingAs($user)->delete("/stars/{$star->uuid}");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseHas('stars', [
            'id' => $star->id,
        ]);
    }

    // ==========================================
    // Status Change Tests
    // ==========================================

    public function test_admin_can_activate_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->inactive()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/activate");

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::ACTIVE, $star->status);
    }

    public function test_admin_can_deactivate_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/deactivate");

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::INACTIVE, $star->status);
    }

    public function test_admin_can_set_star_on_break(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/on-break");

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::ON_BREAK, $star->status);
    }

    public function test_admin_can_graduate_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/graduate");

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::GRADUATED, $star->status);
    }

    public function test_admin_can_suspend_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/suspend");

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::SUSPENDED, $star->status);
    }

    // ==========================================
    // Points Tests
    // ==========================================

    public function test_admin_can_add_points_to_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->create(['points' => 100]);

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/add-points", [
            'points' => 50,
        ]);

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(150, $star->points);
    }

    public function test_adding_points_validates_input(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/add-points", [
            'points' => 0,
        ]);

        $response->assertSessionHasErrors('points');
    }

    // ==========================================
    // Featured Toggle Tests
    // ==========================================

    public function test_admin_can_toggle_featured_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->create(['is_featured' => false]);

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/toggle-featured");

        $response->assertRedirect();
        $star->refresh();
        $this->assertTrue($star->is_featured);

        // Toggle back
        $this->actingAs($admin)->post("/stars/{$star->uuid}/toggle-featured");
        $star->refresh();
        $this->assertFalse($star->is_featured);
    }

    // ==========================================
    // Renew Tests
    // ==========================================

    public function test_admin_can_renew_star(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $star = Star::factory()->expired()->create();

        $response = $this->actingAs($admin)->post("/stars/{$star->uuid}/renew", [
            'months' => 12,
        ]);

        $response->assertRedirect();
        $star->refresh();
        $this->assertEquals(StarStatus::ACTIVE, $star->status);
        $this->assertNotNull($star->expiry_date);
        $this->assertTrue($star->expiry_date->isFuture());
    }

    // ==========================================
    // Stats Tests
    // ==========================================

    public function test_stats_are_calculated_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Star::factory()->active()->count(5)->create();
        Star::factory()->featured()->count(2)->create();
        Star::factory()->inactive()->create();
        // Create one star that was recognized this month
        Star::factory()->active()->create([
            'recognition_date' => now()->startOfMonth()->addDays(1),
        ]);

        $response = $this->actingAs($admin)->get('/stars');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Stars/Index')
            ->where('stats.total', 9)
            ->where('stats.active', 8) // 5 + 2 featured (also active) + 1 new
            ->where('stats.featured', 2)
            ->has('stats.new_this_month')
        );
    }

    // ==========================================
    // Export Tests
    // ==========================================

    public function test_admin_can_export_stars(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Star::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/stars-export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stars' => [
                '*' => [
                    'star_number',
                    'name',
                    'email',
                    'title',
                    'department',
                    'status',
                    'type',
                    'category',
                    'level',
                    'points',
                    'total_hours_served',
                    'recognition_date',
                ],
            ],
        ]);
    }

    public function test_export_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Star::factory()->active()->count(3)->create();
        Star::factory()->inactive()->count(2)->create();

        $response = $this->actingAs($admin)->get('/stars-export?status=active');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'stars');
    }

    public function test_export_can_be_filtered_by_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Star::factory()->volunteer()->count(3)->create();
        Star::factory()->leader()->count(2)->create();

        $response = $this->actingAs($admin)->get('/stars-export?type=volunteer');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'stars');
    }
}
