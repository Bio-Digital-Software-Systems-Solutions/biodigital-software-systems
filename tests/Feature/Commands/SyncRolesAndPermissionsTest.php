<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SyncRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear permission cache before each test
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_run_sync_command_successfully(): void
    {
        $exitCode = Artisan::call('permissions:sync', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_creates_all_expected_permissions(): void
    {
        // Initially no permissions
        $this->assertEquals(0, Permission::count());

        Artisan::call('permissions:sync', ['--force' => true]);

        // Verify permissions were created
        $this->assertGreaterThan(100, Permission::count());

        // Check some specific permissions exist
        $expectedPermissions = [
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'view events',
            'manage departments',
            'view workflows',
            'manage workflows',
            'view pastoral care',
            'manage pastoral care',
            'select pastor for pastoral care',
            'manage pastor availability',
            'teach',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist"
            );
        }
    }

    /** @test */
    public function it_creates_all_expected_roles(): void
    {
        // Initially no roles
        $this->assertEquals(0, Role::count());

        Artisan::call('permissions:sync', ['--force' => true]);

        // Check expected roles exist
        $expectedRoles = [
            'admin',
            'writer',
            'project-manager',
            'event-manager',
            'library-manager',
            'group-leader',
            'department-leader',
            'impact-family-leader',
            'member',
            'student',
            'teacher',
            'pastor',
            // Aliases
            'SuperAdmin',
            'Admin',
            'Member',
            'Student',
            'Teacher',
            'ProjectManager',
            'EventManager',
            'Editor',
        ];

        foreach ($expectedRoles as $role) {
            $this->assertTrue(
                Role::where('name', $role)->exists(),
                "Role '{$role}' should exist"
            );
        }
    }

    /** @test */
    public function it_does_not_duplicate_existing_permissions(): void
    {
        // Create some permissions beforehand
        Permission::create(['name' => 'view articles']);
        Permission::create(['name' => 'create articles']);

        $initialCount = Permission::count();
        $this->assertEquals(2, $initialCount);

        Artisan::call('permissions:sync', ['--force' => true]);

        // The existing permissions should not be duplicated
        $this->assertEquals(1, Permission::where('name', 'view articles')->count());
        $this->assertEquals(1, Permission::where('name', 'create articles')->count());
    }

    /** @test */
    public function it_does_not_duplicate_existing_roles(): void
    {
        // Create some roles beforehand
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        Artisan::call('permissions:sync', ['--force' => true]);

        // The existing roles should not be duplicated
        $this->assertEquals(1, Role::where('name', 'admin')->count());
        $this->assertEquals(1, Role::where('name', 'member')->count());
    }

    /** @test */
    public function it_assigns_correct_permissions_to_admin_role(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $admin = Role::findByName('admin');

        // Admin should have many permissions
        $this->assertGreaterThan(50, $admin->permissions->count());

        // Check specific admin permissions
        $this->assertTrue($admin->hasPermissionTo('view articles'));
        $this->assertTrue($admin->hasPermissionTo('manage departments'));
        $this->assertTrue($admin->hasPermissionTo('manage workflows'));
        $this->assertTrue($admin->hasPermissionTo('approve stock requests'));
        $this->assertTrue($admin->hasPermissionTo('moderate chat'));
    }

    /** @test */
    public function it_assigns_correct_permissions_to_member_role(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $member = Role::findByName('member');

        // Member should have basic permissions
        $this->assertTrue($member->hasPermissionTo('view articles'));
        $this->assertTrue($member->hasPermissionTo('view events'));
        $this->assertTrue($member->hasPermissionTo('attend events'));
        $this->assertTrue($member->hasPermissionTo('use chat'));
        $this->assertTrue($member->hasPermissionTo('rent books'));

        // Member should NOT have admin-level permissions
        $this->assertFalse($member->hasPermissionTo('manage departments'));
        $this->assertFalse($member->hasPermissionTo('delete users'));
        $this->assertFalse($member->hasPermissionTo('manage workflows'));
    }

    /** @test */
    public function it_assigns_correct_permissions_to_teacher_role(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $teacher = Role::findByName('teacher');

        // Teacher should have teaching-specific permissions
        $this->assertTrue($teacher->hasPermissionTo('teach'));
        $this->assertTrue($teacher->hasPermissionTo('view trainings'));
        $this->assertTrue($teacher->hasPermissionTo('create trainings'));
        $this->assertTrue($teacher->hasPermissionTo('manage trainings'));
        $this->assertTrue($teacher->hasPermissionTo('grade quizzes'));
        $this->assertTrue($teacher->hasPermissionTo('access teacher dashboard'));
    }

    /** @test */
    public function it_assigns_correct_permissions_to_pastor_role(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $pastor = Role::findByName('pastor');

        // Pastor should have pastoral care permissions
        $this->assertTrue($pastor->hasPermissionTo('view pastoral care'));
        $this->assertTrue($pastor->hasPermissionTo('manage pastoral care'));
        $this->assertTrue($pastor->hasPermissionTo('select pastor for pastoral care'));
        $this->assertTrue($pastor->hasPermissionTo('manage pastor availability'));
        $this->assertTrue($pastor->hasPermissionTo('manage pastoral appointments'));
    }

    /** @test */
    public function it_creates_super_admin_with_all_permissions(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $superAdmin = Role::findByName('SuperAdmin');
        $allPermissions = Permission::all();

        // SuperAdmin should have all permissions
        $this->assertEquals($allPermissions->count(), $superAdmin->permissions->count());
    }

    /** @test */
    public function it_syncs_alias_roles_with_source_roles(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        // Admin alias should have same permissions as admin
        $admin = Role::findByName('admin');
        $adminAlias = Role::findByName('Admin');
        $this->assertEquals(
            $admin->permissions->pluck('name')->sort()->values()->toArray(),
            $adminAlias->permissions->pluck('name')->sort()->values()->toArray()
        );

        // Member alias should have same permissions as member
        $member = Role::findByName('member');
        $memberAlias = Role::findByName('Member');
        $this->assertEquals(
            $member->permissions->pluck('name')->sort()->values()->toArray(),
            $memberAlias->permissions->pluck('name')->sort()->values()->toArray()
        );

        // Editor alias should have same permissions as writer
        $writer = Role::findByName('writer');
        $editor = Role::findByName('Editor');
        $this->assertEquals(
            $writer->permissions->pluck('name')->sort()->values()->toArray(),
            $editor->permissions->pluck('name')->sort()->values()->toArray()
        );
    }

    /** @test */
    public function it_adds_missing_permissions_to_existing_roles(): void
    {
        // Create admin role with only one permission
        Permission::create(['name' => 'view articles']);
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo('view articles');

        $this->assertEquals(1, $admin->permissions->count());

        Artisan::call('permissions:sync', ['--force' => true]);

        // Refresh from database
        $admin = Role::findByName('admin');

        // Admin should now have many more permissions
        $this->assertGreaterThan(50, $admin->permissions->count());
        $this->assertTrue($admin->hasPermissionTo('view articles'));
        $this->assertTrue($admin->hasPermissionTo('manage departments'));
    }

    /** @test */
    public function it_does_not_remove_existing_permissions_from_roles(): void
    {
        // Create a custom permission not in the master list
        Permission::create(['name' => 'custom permission']);

        // Create admin role with custom permission
        Permission::create(['name' => 'view articles']);
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['view articles', 'custom permission']);

        Artisan::call('permissions:sync', ['--force' => true]);

        // Refresh from database
        $admin = Role::findByName('admin');

        // Admin should still have the custom permission
        $this->assertTrue($admin->hasPermissionTo('custom permission'));
    }

    /** @test */
    public function it_supports_dry_run_mode(): void
    {
        // Initially no permissions or roles
        $this->assertEquals(0, Permission::count());
        $this->assertEquals(0, Role::count());

        Artisan::call('permissions:sync', ['--dry-run' => true]);

        // Nothing should have been created
        $this->assertEquals(0, Permission::count());
        $this->assertEquals(0, Role::count());
    }

    /** @test */
    public function it_shows_created_permissions_in_output(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Synchronizing permissions', $output);
        $this->assertStringContainsString('Created permission', $output);
    }

    /** @test */
    public function it_shows_created_roles_in_output(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Synchronizing roles', $output);
        $this->assertStringContainsString('Created role', $output);
    }

    /** @test */
    public function it_shows_summary_in_output(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Summary', $output);
        $this->assertStringContainsString('Permissions', $output);
        $this->assertStringContainsString('Roles', $output);
    }

    /** @test */
    public function it_can_be_run_multiple_times_idempotently(): void
    {
        // First run
        Artisan::call('permissions:sync', ['--force' => true]);

        $permissionCountAfterFirst = Permission::count();
        $roleCountAfterFirst = Role::count();

        // Second run
        Artisan::call('permissions:sync', ['--force' => true]);

        // Counts should be the same
        $this->assertEquals($permissionCountAfterFirst, Permission::count());
        $this->assertEquals($roleCountAfterFirst, Role::count());

        // Third run
        Artisan::call('permissions:sync', ['--force' => true]);

        // Still the same
        $this->assertEquals($permissionCountAfterFirst, Permission::count());
        $this->assertEquals($roleCountAfterFirst, Role::count());
    }

    /** @test */
    public function it_assigns_workflow_permissions_to_relevant_roles(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        // Admin should have workflow permissions
        $admin = Role::findByName('admin');
        $this->assertTrue($admin->hasPermissionTo('view workflows'));
        $this->assertTrue($admin->hasPermissionTo('create workflows'));
        $this->assertTrue($admin->hasPermissionTo('manage workflows'));

        // Project manager should have workflow permissions
        $projectManager = Role::findByName('project-manager');
        $this->assertTrue($projectManager->hasPermissionTo('view workflows'));
        $this->assertTrue($projectManager->hasPermissionTo('create workflows'));
        $this->assertTrue($projectManager->hasPermissionTo('execute workflows'));

        // Department leader should have workflow permissions
        $departmentLeader = Role::findByName('department-leader');
        $this->assertTrue($departmentLeader->hasPermissionTo('view workflows'));
        $this->assertTrue($departmentLeader->hasPermissionTo('manage workflows'));

        // Member should NOT have workflow permissions
        $member = Role::findByName('member');
        $this->assertFalse($member->hasPermissionTo('view workflows'));
        $this->assertFalse($member->hasPermissionTo('manage workflows'));
    }

    /** @test */
    public function it_assigns_needs_permissions_to_relevant_roles(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        // Admin should have all needs permissions
        $admin = Role::findByName('admin');
        $this->assertTrue($admin->hasPermissionTo('view needs'));
        $this->assertTrue($admin->hasPermissionTo('create needs'));
        $this->assertTrue($admin->hasPermissionTo('approve needs'));
        $this->assertTrue($admin->hasPermissionTo('manage needs'));

        // Member should have basic needs permissions
        $member = Role::findByName('member');
        $this->assertTrue($member->hasPermissionTo('view needs'));
        $this->assertTrue($member->hasPermissionTo('create needs'));
        $this->assertFalse($member->hasPermissionTo('approve needs'));
    }

    /** @test */
    public function it_clears_permission_cache_after_sync(): void
    {
        // Create a permission manually
        Permission::create(['name' => 'test permission']);

        // Run sync (which should clear cache)
        Artisan::call('permissions:sync', ['--force' => true]);

        // The newly created permissions should be accessible without manual cache clear
        $admin = Role::findByName('admin');
        $this->assertTrue($admin->hasPermissionTo('view articles'));
    }

    /** @test */
    public function it_handles_project_manager_permissions_correctly(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $projectManager = Role::findByName('project-manager');

        // Project management
        $this->assertTrue($projectManager->hasPermissionTo('view projects'));
        $this->assertTrue($projectManager->hasPermissionTo('create projects'));
        $this->assertTrue($projectManager->hasPermissionTo('manage projects'));

        // Tasks
        $this->assertTrue($projectManager->hasPermissionTo('view tasks'));
        $this->assertTrue($projectManager->hasPermissionTo('assign tasks'));

        // Reports
        $this->assertTrue($projectManager->hasPermissionTo('view reports'));
        $this->assertTrue($projectManager->hasPermissionTo('generate reports'));
    }

    /** @test */
    public function it_handles_department_leader_permissions_correctly(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        $departmentLeader = Role::findByName('department-leader');

        // Department management
        $this->assertTrue($departmentLeader->hasPermissionTo('view departments'));
        $this->assertTrue($departmentLeader->hasPermissionTo('edit departments'));
        $this->assertTrue($departmentLeader->hasPermissionTo('manage departments'));
        $this->assertTrue($departmentLeader->hasPermissionTo('assign department members'));

        // Forms
        $this->assertTrue($departmentLeader->hasPermissionTo('view forms'));
        $this->assertTrue($departmentLeader->hasPermissionTo('create forms'));
        $this->assertTrue($departmentLeader->hasPermissionTo('manage forms'));
    }

    /** @test */
    public function it_returns_correct_exit_code(): void
    {
        $exitCode = Artisan::call('permissions:sync', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_counts_permissions_correctly(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        // We expect approximately 116 permissions based on the defined list
        $count = Permission::count();
        $this->assertGreaterThanOrEqual(110, $count);
        $this->assertLessThanOrEqual(130, $count);
    }

    /** @test */
    public function it_creates_all_base_and_alias_roles(): void
    {
        Artisan::call('permissions:sync', ['--force' => true]);

        // Base roles
        $baseRoles = [
            'admin', 'writer', 'project-manager', 'event-manager',
            'library-manager', 'group-leader', 'department-leader',
            'impact-family-leader', 'member', 'student', 'teacher', 'pastor',
        ];

        // Alias roles
        $aliasRoles = [
            'SuperAdmin', 'Admin', 'Member', 'Student',
            'Teacher', 'ProjectManager', 'EventManager', 'Editor',
        ];

        foreach ($baseRoles as $role) {
            $this->assertTrue(
                Role::where('name', $role)->exists(),
                "Base role '{$role}' should exist"
            );
        }

        foreach ($aliasRoles as $role) {
            $this->assertTrue(
                Role::where('name', $role)->exists(),
                "Alias role '{$role}' should exist"
            );
        }
    }
}
