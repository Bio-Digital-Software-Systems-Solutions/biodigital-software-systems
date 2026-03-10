<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all necessary permissions
        $permissions = [
            'view articles', 'create articles', 'edit articles', 'delete articles',
            'view events', 'create events', 'edit events', 'delete events',
            'view books', 'create books', 'edit books', 'delete books',
            'view trainings', 'create trainings', 'edit trainings', 'delete trainings', 'manage trainings',
            'view programs', 'create programs', 'edit programs', 'delete programs',
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view groups', 'create groups', 'edit groups', 'delete groups',
            'view stocks', 'manage stocks',
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Member role with limited permissions
        $memberRole = Role::create(['name' => RoleEnum::MEMBER->value]);
        $memberRole->givePermissionTo(['view articles', 'view trainings']);

        // Create Student role with more permissions
        $studentRole = Role::create(['name' => RoleEnum::STUDENT->value]);
        $studentRole->givePermissionTo([
            'view articles', 'view events', 'view books',
            'view trainings', 'view messages', 'create messages',
            'use chat'
        ]);

        // Create Teacher role with training management permissions
        $teacherRole = Role::create(['name' => RoleEnum::TEACHER->value]);
        $teacherRole->givePermissionTo([
            'view articles', 'view events', 'view books', 'view trainings',
            'create trainings', 'edit trainings', 'manage trainings',
            'view messages', 'create messages', 'edit messages', 'use chat'
        ]);

        // Create Admin role with all permissions
        $adminRole = Role::create(['name' => RoleEnum::ADMIN->value]);
        $adminRole->givePermissionTo(Permission::all());

        // Create SuperAdmin role with all permissions
        $superAdminRole = Role::create(['name' => RoleEnum::SUPER_ADMIN->value]);
        $superAdminRole->givePermissionTo(Permission::all());
    }

    public function test_member_receives_correct_permissions_in_inertia_props(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        $response = $this->actingAs($user)->get('/articles');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions', 2) // Member should have 2 permissions
            ->where('auth.user.permissions', function ($permissions): bool {
                $permissionsArray = $permissions instanceof \Illuminate\Support\Collection ? $permissions->toArray() : $permissions;
                return in_array('view articles', $permissionsArray) &&
                       in_array('view trainings', $permissionsArray);
            })
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::MEMBER->value, $rolesArray);
            })
        );
    }

    public function test_student_receives_correct_permissions_in_inertia_props(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::STUDENT->value);

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', function ($permissions): bool {
                $permissionsArray = $permissions instanceof \Illuminate\Support\Collection ? $permissions->toArray() : $permissions;
                return in_array('view articles', $permissionsArray) &&
                       in_array('view events', $permissionsArray) &&
                       in_array('view books', $permissionsArray) &&
                       in_array('view trainings', $permissionsArray) &&
                       in_array('use chat', $permissionsArray);
            })
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::STUDENT->value, $rolesArray);
            })
        );
    }

    public function test_teacher_receives_correct_permissions_in_inertia_props(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TEACHER->value);

        $response = $this->actingAs($user)->get('/trainings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', function ($permissions): bool {
                $permissionsArray = $permissions instanceof \Illuminate\Support\Collection ? $permissions->toArray() : $permissions;
                return in_array('view trainings', $permissionsArray) &&
                       in_array('create trainings', $permissionsArray) &&
                       in_array('edit trainings', $permissionsArray) &&
                       in_array('manage trainings', $permissionsArray);
            })
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::TEACHER->value, $rolesArray);
            })
        );
    }

    public function test_admin_receives_all_permissions_in_inertia_props(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::ADMIN->value);

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions')
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::ADMIN->value, $rolesArray);
            })
        );
    }

    public function test_super_admin_receives_all_permissions_in_inertia_props(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions')
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::SUPER_ADMIN->value, $rolesArray);
            })
        );
    }

    public function test_member_cannot_access_events_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        $response = $this->actingAs($user)->get('/events');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_member_can_access_articles_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        $response = $this->actingAs($user)->get('/articles');

        $response->assertStatus(200);
    }

    public function test_member_is_redirected_from_trainings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        // Members are redirected by RestrictMemberFromAdminDashboard middleware
        $response = $this->actingAs($user)->get('/trainings');

        $response->assertStatus(302);
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_student_can_access_events_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::STUDENT->value);

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
    }

    public function test_teacher_can_create_trainings(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::TEACHER->value);

        $response = $this->actingAs($user)->get('/trainings/create');

        $response->assertStatus(200);
    }

    public function test_member_is_redirected_from_training_creation(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        // Members are redirected by RestrictMemberFromAdminDashboard middleware
        $response = $this->actingAs($user)->get('/trainings/create');

        $response->assertStatus(302);
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_user_with_multiple_roles_receives_combined_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole([RoleEnum::MEMBER->value, RoleEnum::STUDENT->value]);

        $response = $this->actingAs($user)->get('/events');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', function ($permissions): bool {
                $permissionsArray = $permissions instanceof \Illuminate\Support\Collection ? $permissions->toArray() : $permissions;
                // Should have permissions from both Member and Student roles
                return in_array('view articles', $permissionsArray) &&
                       in_array('view trainings', $permissionsArray) &&
                       in_array('view events', $permissionsArray) &&
                       in_array('view books', $permissionsArray);
            })
            ->where('auth.user.roles', function ($roles): bool {
                $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : $roles;
                return in_array(RoleEnum::MEMBER->value, $rolesArray) && in_array(RoleEnum::STUDENT->value, $rolesArray);
            })
        );
    }

    public function test_permissions_are_cached_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MEMBER->value);

        // First request
        $response1 = $this->actingAs($user)->get('/articles');
        $response1->assertStatus(200);

        // Add new permission
        $user->givePermissionTo('view events');

        // Second request should reflect new permission
        $response2 = $this->actingAs($user)->get('/events');
        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', function ($permissions): bool {
                $permissionsArray = $permissions instanceof \Illuminate\Support\Collection ? $permissions->toArray() : $permissions;
                return in_array('view events', $permissionsArray);
            })
        );
    }
}
