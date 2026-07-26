<?php

namespace Tests\Feature;

use App\Enums\Volunteer\VolunteerCategory;
use App\Enums\Volunteer\VolunteerStatus;
use App\Enums\Volunteer\VolunteerType;
use App\Models\Department;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VolunteerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view volunteers']);
        Permission::create(['name' => 'create volunteers']);
        Permission::create(['name' => 'edit volunteers']);
        Permission::create(['name' => 'delete volunteers']);
        Permission::create(['name' => 'manage volunteers']);

        // Create admin role with all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view volunteers',
            'create volunteers',
            'edit volunteers',
            'delete volunteers',
            'manage volunteers',
        ]);

        // Create member role with view only
        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view volunteers']);
    }

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_user_with_permission_can_view_volunteers_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page->component('Volunteers/Index'));
    }

    public function test_index_returns_correct_data_structure(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Volunteer::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->has('volunteers')
            ->has('filters')
            ->has('statuses')
            ->has('types')
            ->has('categories')
            ->has('departments')
            ->has('stats')
        );
    }

    public function test_guest_cannot_access_volunteers(): void
    {
        $response = $this->get('/volunteers');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_volunteers(): void
    {
        $user = User::factory()->create();
        // No role assigned

        $response = $this->actingAs($user)->get('/volunteers');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Search and Filter Tests
    // ==========================================

    public function test_volunteers_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $userToFind = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Volunteer::factory()->create(['user_id' => $userToFind->id]);
        Volunteer::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/volunteers?search=John');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->where('filters.search', 'John')
        );
    }

    public function test_volunteers_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Volunteer::factory()->active()->count(2)->create();
        Volunteer::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/volunteers?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_volunteers_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Volunteer::factory()->volunteer()->count(2)->create();
        Volunteer::factory()->leader()->create();

        $response = $this->actingAs($user)->get('/volunteers?type=volunteer');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->where('filters.type', 'volunteer')
        );
    }

    public function test_volunteers_can_be_filtered_by_department(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $department = Department::factory()->create();
        Volunteer::factory()->inDepartment($department)->count(2)->create();
        Volunteer::factory()->create();

        $response = $this->actingAs($user)->get("/volunteers?department={$department->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->where('filters.department', (string) $department->id)
        );
    }

    // ==========================================
    // Show Tests
    // ==========================================

    public function test_user_can_view_single_volunteer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($user)->get("/volunteers/{$volunteer->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Show')
            ->has('volunteer')
            ->has('canManage')
        );
    }

    public function test_show_returns_volunteer_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $volunteer = Volunteer::factory()->create([
            'title' => 'Volunteer de l\'accueil',
            'points' => 500,
            'level' => 3,
        ]);

        $response = $this->actingAs($user)->get("/volunteers/{$volunteer->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Show')
            ->where('volunteer.title', 'Volunteer de l\'accueil')
            ->where('volunteer.points', 500)
            ->where('volunteer.level', 3)
        );
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_with_permission_can_view_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/volunteers/create');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Create')
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

        $response = $this->actingAs($user)->get('/volunteers/create');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_admin_can_create_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteerUser = User::factory()->create();
        $department = Department::factory()->create();

        $volunteerData = [
            'user_id' => $volunteerUser->id,
            'department_id' => $department->id,
            'title' => 'Bénévole du mois',
            'status' => VolunteerStatus::ACTIVE->value,
            'type' => VolunteerType::VOLUNTEER->value,
            'category' => VolunteerCategory::SERVICE->value,
            'points' => 100,
            'level' => 1,
            'recognition_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($admin)->post('/volunteers', $volunteerData);

        $response->assertRedirect();
        $this->assertDatabaseHas('volunteers', [
            'user_id' => $volunteerUser->id,
            'title' => 'Bénévole du mois',
        ]);
    }

    public function test_cannot_create_volunteer_for_same_user_twice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteerUser = User::factory()->create();
        Volunteer::factory()->create(['user_id' => $volunteerUser->id]);

        $volunteerData = [
            'user_id' => $volunteerUser->id,
            'title' => 'New Volunteer',
            'status' => VolunteerStatus::ACTIVE->value,
            'type' => VolunteerType::VOLUNTEER->value,
        ];

        $response = $this->actingAs($admin)->post('/volunteers', $volunteerData);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_validation_errors_on_create(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/volunteers', [
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

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($user)->get("/volunteers/{$volunteer->uuid}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Edit')
            ->has('volunteer')
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

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($user)->get("/volunteers/{$volunteer->uuid}/edit");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_admin_can_update_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->create([
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'points' => 200,
            'status' => VolunteerStatus::ACTIVE->value,
            'type' => VolunteerType::LEADER->value,
        ];

        $response = $this->actingAs($admin)->put("/volunteers/{$volunteer->uuid}", $updateData);

        $response->assertRedirect("/volunteers/{$volunteer->uuid}");
        $this->assertDatabaseHas('volunteers', [
            'id' => $volunteer->id,
            'title' => 'Updated Title',
            'points' => 200,
        ]);
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_admin_can_delete_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($admin)->delete("/volunteers/{$volunteer->uuid}");

        $response->assertRedirect('/volunteers');
        $this->assertSoftDeleted('volunteers', [
            'id' => $volunteer->id,
        ]);
    }

    public function test_member_cannot_delete_volunteer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($user)->delete("/volunteers/{$volunteer->uuid}");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseHas('volunteers', [
            'id' => $volunteer->id,
        ]);
    }

    // ==========================================
    // Status Change Tests
    // ==========================================

    public function test_admin_can_activate_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->inactive()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/activate");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::ACTIVE, $volunteer->status);
    }

    public function test_admin_can_deactivate_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/deactivate");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::INACTIVE, $volunteer->status);
    }

    public function test_admin_can_set_volunteer_on_break(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/on-break");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::ON_BREAK, $volunteer->status);
    }

    public function test_admin_can_graduate_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/graduate");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::GRADUATED, $volunteer->status);
    }

    public function test_admin_can_suspend_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/suspend");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::SUSPENDED, $volunteer->status);
    }

    // ==========================================
    // Points Tests
    // ==========================================

    public function test_admin_can_add_points_to_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->create(['points' => 100]);

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/add-points", [
            'points' => 50,
        ]);

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(150, $volunteer->points);
    }

    public function test_adding_points_validates_input(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/add-points", [
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

        $volunteer = Volunteer::factory()->create(['is_featured' => false]);

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/toggle-featured");

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertTrue($volunteer->is_featured);

        // Toggle back
        $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/toggle-featured");
        $volunteer->refresh();
        $this->assertFalse($volunteer->is_featured);
    }

    // ==========================================
    // Renew Tests
    // ==========================================

    public function test_admin_can_renew_volunteer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $volunteer = Volunteer::factory()->expired()->create();

        $response = $this->actingAs($admin)->post("/volunteers/{$volunteer->uuid}/renew", [
            'months' => 12,
        ]);

        $response->assertRedirect();
        $volunteer->refresh();
        $this->assertEquals(VolunteerStatus::ACTIVE, $volunteer->status);
        $this->assertNotNull($volunteer->expiry_date);
        $this->assertTrue($volunteer->expiry_date->isFuture());
    }

    // ==========================================
    // Stats Tests
    // ==========================================

    public function test_stats_are_calculated_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Volunteer::factory()->active()->count(5)->create();
        Volunteer::factory()->featured()->count(2)->create();
        Volunteer::factory()->inactive()->create();
        // Create one volunteer that was recognized this month
        Volunteer::factory()->active()->create([
            'recognition_date' => now()->startOfMonth()->addDays(1),
        ]);

        $response = $this->actingAs($admin)->get('/volunteers');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Volunteers/Index')
            ->where('stats.total', 9)
            ->where('stats.active', 8) // 5 + 2 featured (also active) + 1 new
            ->where('stats.featured', 2)
            ->has('stats.new_this_month')
        );
    }

    // ==========================================
    // Export Tests
    // ==========================================

    public function test_admin_can_export_volunteers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Volunteer::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/volunteers-export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'volunteers' => [
                '*' => [
                    'volunteer_number',
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

        Volunteer::factory()->active()->count(3)->create();
        Volunteer::factory()->inactive()->count(2)->create();

        $response = $this->actingAs($admin)->get('/volunteers-export?status=active');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'volunteers');
    }

    public function test_export_can_be_filtered_by_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Volunteer::factory()->volunteer()->count(3)->create();
        Volunteer::factory()->leader()->count(2)->create();

        $response = $this->actingAs($admin)->get('/volunteers-export?type=volunteer');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'volunteers');
    }
}
