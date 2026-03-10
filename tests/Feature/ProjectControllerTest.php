<?php

namespace Tests\Feature;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Star\StarStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Star;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::create(['name' => 'view projects']);
        Permission::create(['name' => 'create projects']);
        Permission::create(['name' => 'edit projects']);
        Permission::create(['name' => 'delete projects']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view projects', 'create projects', 'edit projects', 'delete projects']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view projects']);
    }

    public function test_authenticated_user_can_view_projects_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Projects/Dashboard'));
    }

    public function test_authenticated_user_can_view_projects_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/projects/all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Projects/Index'));
    }

    public function test_authenticated_user_with_permission_can_access_create_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('users')
            ->has('employees')
            ->has('stars')
        );
    }

    public function test_create_page_loads_users_employees_and_stars(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create additional users
        User::factory()->create();

        // Create an active employee
        $employeeUser = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => EmployeeStatus::ACTIVE,
            'position' => 'Developer',
        ]);

        // Create an active star
        $starUser = User::factory()->create();
        Star::factory()->create([
            'user_id' => $starUser->id,
            'status' => StarStatus::ACTIVE,
            'title' => 'Guest Speaker',
        ]);

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('users', 4) // user, otherUser, employeeUser, starUser
            ->has('employees', 1)
            ->has('stars', 1)
            ->where('employees.0.type', 'employee')
            ->where('stars.0.type', 'star')
        );
    }

    public function test_user_without_permission_cannot_access_create_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/projects/create');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect()
        );
    }

    public function test_user_can_store_project(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'planning',
            'priority' => 'high',
            'color' => '#3B82F6',
            'start_date' => '2026-01-15',
            'end_date' => '2026-03-15',
            'budget' => 10000.00,
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'planning',
            'priority' => 'high',
            'project_manager_id' => $user->id, // Should default to current user
        ]);
    }

    public function test_user_can_store_project_with_project_manager(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $manager = User::factory()->create();

        $projectData = [
            'name' => 'Project with Manager',
            'description' => 'Test Description',
            'status' => 'active',
            'priority' => 'medium',
            'color' => '#10B981',
            'project_manager_id' => $manager->id,
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Project with Manager',
            'project_manager_id' => $manager->id,
        ]);

        // Manager should be attached as lead member
        $project = Project::where('name', 'Project with Manager')->first();
        $this->assertTrue(
            $project->members()->where('user_id', $manager->id)->wherePivot('is_lead', true)->exists()
        );
    }

    public function test_user_can_store_project_with_participants(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $projectData = [
            'name' => 'Project with Participants',
            'description' => 'Test Description',
            'status' => 'planning',
            'priority' => 'high',
            'participants' => [$participant1->id, $participant2->id],
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Project with Participants',
        ]);

        $project = Project::where('name', 'Project with Participants')->first();

        // Both participants should be attached as non-lead members in project_members
        $this->assertTrue(
            $project->members()->where('user_id', $participant1->id)->wherePivot('is_lead', false)->exists()
        );
        $this->assertTrue(
            $project->members()->where('user_id', $participant2->id)->wherePivot('is_lead', false)->exists()
        );

        // Both participants should also exist in project_participants table (for Show page display)
        $this->assertDatabaseHas('project_participants', [
            'project_id' => $project->id,
            'user_id' => $participant1->id,
            'role' => 'member',
        ]);
        $this->assertDatabaseHas('project_participants', [
            'project_id' => $project->id,
            'user_id' => $participant2->id,
            'role' => 'member',
        ]);
    }

    public function test_project_manager_is_not_duplicated_in_participants(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $manager = User::factory()->create();
        $participant = User::factory()->create();

        $projectData = [
            'name' => 'Project Manager Test',
            'description' => 'Test Description',
            'status' => 'planning',
            'priority' => 'medium',
            'project_manager_id' => $manager->id,
            'participants' => [$manager->id, $participant->id], // Manager is also in participants
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertRedirect();

        $project = Project::where('name', 'Project Manager Test')->first();

        // Manager should only appear once (as lead)
        $this->assertEquals(
            1,
            $project->members()->where('user_id', $manager->id)->count()
        );

        // Manager should be lead
        $this->assertTrue(
            $project->members()->where('user_id', $manager->id)->wherePivot('is_lead', true)->exists()
        );

        // Participant should be attached as non-lead
        $this->assertTrue(
            $project->members()->where('user_id', $participant->id)->wherePivot('is_lead', false)->exists()
        );
    }

    public function test_project_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/projects', []);

        $response->assertSessionHasErrors(['name', 'description', 'status', 'priority']);
    }

    public function test_project_creation_validates_description_minimum_length(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'Too short', // Less than 10 characters
            'status' => 'planning',
            'priority' => 'medium',
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_project_creation_accepts_valid_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'This is a valid description with more than 10 characters',
            'status' => 'planning',
            'priority' => 'medium',
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionDoesntHaveErrors(['description']);
        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'This is a valid description with more than 10 characters',
        ]);
    }

    public function test_project_creation_validates_status_values(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'invalid_status',
            'priority' => 'medium',
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_project_creation_validates_priority_values(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'planning',
            'priority' => 'invalid_priority',
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['priority']);
    }

    public function test_project_creation_validates_end_date_after_start_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'planning',
            'priority' => 'medium',
            'start_date' => '2026-03-15',
            'end_date' => '2026-01-15', // Before start date
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_project_creation_validates_budget_minimum(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'planning',
            'priority' => 'medium',
            'budget' => -100, // Negative budget
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['budget']);
    }

    public function test_project_creation_validates_project_manager_exists(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'planning',
            'priority' => 'medium',
            'project_manager_id' => 99999, // Non-existent user
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['project_manager_id']);
    }

    public function test_project_creation_validates_participants_exist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $projectData = [
            'name' => 'Test Project',
            'description' => 'A valid description for the test project',
            'status' => 'planning',
            'priority' => 'medium',
            'participants' => [99999, 99998], // Non-existent users
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $response->assertSessionHasErrors(['participants.0', 'participants.1']);
    }

    public function test_user_can_view_single_project(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $project = Project::factory()->create([
            'project_manager_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.name')
        );
    }

    public function test_user_can_update_project(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $project = Project::factory()->create([
            'project_manager_id' => $user->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'status' => 'active',
            'priority' => 'high',
            'color' => '#EF4444',
        ];

        $response = $this->actingAs($user)->put("/projects/{$project->uuid}", $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'status' => 'active',
        ]);
    }

    public function test_user_can_delete_project(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $project = Project::factory()->create([
            'project_manager_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/projects/{$project->uuid}");

        $response->assertRedirect('/projects');
        // Project uses SoftDeletes, so check it's soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_project(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $project = Project::factory()->create();

        $response = $this->actingAs($user)->delete("/projects/{$project->uuid}");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect()
        );
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_guest_cannot_access_projects(): void
    {
        $response = $this->get('/projects');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_create_project(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'status' => 'planning',
            'priority' => 'medium',
        ];

        $response = $this->post('/projects', $projectData);
        $response->assertRedirect('/login');
    }

    public function test_create_page_only_loads_active_employees(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create an active employee
        $activeEmployeeUser = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $activeEmployeeUser->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        // Create an inactive employee
        $inactiveEmployeeUser = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $inactiveEmployeeUser->id,
            'status' => EmployeeStatus::INACTIVE,
        ]);

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('employees', 1)
            ->where('employees.0.id', $activeEmployeeUser->id)
        );
    }

    public function test_create_page_only_loads_active_stars(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create an active star
        $activeStarUser = User::factory()->create();
        Star::factory()->create([
            'user_id' => $activeStarUser->id,
            'status' => StarStatus::ACTIVE,
        ]);

        // Create an inactive star
        $inactiveStarUser = User::factory()->create();
        Star::factory()->create([
            'user_id' => $inactiveStarUser->id,
            'status' => StarStatus::INACTIVE,
        ]);

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('stars', 1)
            ->where('stars.0.id', $activeStarUser->id)
        );
    }

    public function test_project_board_page_loads_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $project = Project::factory()->create([
            'project_manager_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->uuid}/board");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Projects/Board'));
    }
}
