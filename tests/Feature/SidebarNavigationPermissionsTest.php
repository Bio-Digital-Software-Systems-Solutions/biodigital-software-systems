<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run the seeder to create all roles and permissions
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/**
 * Helper function to get user permissions from Inertia props
 */
function getUserPermissionsFromInertia($response): array
{
    $page = $response->viewData('page');

    return collect($page['props']['auth']['user']['permissions'] ?? [])
        ->map(fn ($p) => is_string($p) ? $p : $p['name'])
        ->toArray();
}

/**
 * Helper function to check if user has specific permission in Inertia props
 */
function userHasPermissionInProps($response, string $permission): bool
{
    $permissions = getUserPermissionsFromInertia($response);

    return in_array($permission, $permissions);
}

// ============================================
// Admin Role Tests
// ============================================

it('admin user has all navigation permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $requiredPermissions = [
        'view events',
        'view appointments',
        'view pastoral care',
        'view articles',
        'view books',
        'view trainings',
        'manage trainings',
        'access teacher dashboard',
        'access student dashboard',
        'use chat',
        'view projects',
        'view departments',
        'view reports',
        'view groups',
        'view programs',
        'view stocks',
        'view messages',
        'view workflows',
        'view forms',
        'view needs',
        'manage pastor availability',
    ];

    foreach ($requiredPermissions as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Admin should have permission: {$permission}");
    }
});

// ============================================
// SuperAdmin Role Tests
// ============================================

it('super admin has all permissions including user management', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.roles', function ($roles) {
            $rolesArray = $roles instanceof \Illuminate\Support\Collection ? $roles->toArray() : (array) $roles;

            return in_array('super-admin', $rolesArray);
        })
    );

    // SuperAdmin has all permissions
    $allPermissions = Permission::all()->pluck('name')->toArray();
    foreach ($allPermissions as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("SuperAdmin should have permission: {$permission}");
    }
});

// ============================================
// Member Role Tests
// ============================================

it('member role has correct limited navigation permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $response = $this->actingAs($user)->get('/articles');
    $response->assertStatus(200);

    // Permissions member SHOULD have
    $shouldHave = [
        'view articles',
        'view trainings',
        'view events',
        'view books',
        'view groups',
        'view messages',
        'use chat',
        'view appointments',
        'view pastoral care',
        'view needs',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Member should have permission: {$permission}");
    }

    // Permissions member should NOT have
    $shouldNotHave = [
        'manage trainings',
        'access teacher dashboard',
        'access student dashboard',
        'view projects',
        'view departments',
        'view programs',
        'view stocks',
        'view workflows',
        'view forms',
        'view reports',
        'manage pastor availability',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Member should NOT have permission: {$permission}");
    }
});

// ============================================
// Student Role Tests
// ============================================

it('student role has student dashboard access', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)->get('/events');
    $response->assertStatus(200);

    // Student specific permissions
    $shouldHave = [
        'view articles',
        'view events',
        'view books',
        'view trainings',
        'access student dashboard',
        'take quizzes',
        'view messages',
        'use chat',
        'view appointments',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Student should have permission: {$permission}");
    }

    // Student should NOT have teacher permissions
    $shouldNotHave = [
        'access teacher dashboard',
        'manage trainings',
        'create trainings',
        'view projects',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Student should NOT have permission: {$permission}");
    }
});

// ============================================
// Teacher Role Tests
// ============================================

it('teacher role has teacher dashboard and training management access', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    $response = $this->actingAs($user)->get('/trainings');
    $response->assertStatus(200);

    // Teacher specific permissions
    $shouldHave = [
        'view trainings',
        'create trainings',
        'edit trainings',
        'manage trainings',
        'access teacher dashboard',
        'view reports',
        'view quizzes',
        'create quizzes',
        'grade quizzes',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Teacher should have permission: {$permission}");
    }

    // Teacher should NOT have student dashboard access
    expect(userHasPermissionInProps($response, 'access student dashboard'))
        ->toBeFalse('Teacher should NOT have access student dashboard permission');
});

// ============================================
// Pastor Role Tests
// ============================================

it('pastor role has pastoral care and availability management access', function () {
    $user = User::factory()->create();
    $user->assignRole('pastor');

    $response = $this->actingAs($user)->get('/appointments');
    $response->assertStatus(200);

    // Pastor specific permissions
    $shouldHave = [
        'view pastoral care',
        'create pastoral care',
        'edit pastoral care',
        'delete pastoral care',
        'manage pastoral care',
        'manage pastor availability',
        'view appointments',
        'create appointments',
        'edit appointments',
        'delete appointments',
        'view groups',
        'view reports',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Pastor should have permission: {$permission}");
    }

    // Pastor should NOT have training management
    $shouldNotHave = [
        'manage trainings',
        'access teacher dashboard',
        'access student dashboard',
        'view projects',
        'view programs',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Pastor should NOT have permission: {$permission}");
    }
});

// ============================================
// Project Manager Role Tests
// ============================================

it('project manager has project and workflow management access', function () {
    $user = User::factory()->create();
    $user->assignRole('project-manager');

    $response = $this->actingAs($user)->get('/projects');
    $response->assertStatus(200);

    // Project manager specific permissions
    $shouldHave = [
        'view projects',
        'create projects',
        'edit projects',
        'manage projects',
        'view programs',
        'view workflows',
        'view forms',
        'view needs',
        'view reports',
        'view stocks',
        'view groups',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Project Manager should have permission: {$permission}");
    }
});

// ============================================
// Event Manager Role Tests
// ============================================

it('event manager has event management access but not projects', function () {
    $user = User::factory()->create();
    $user->assignRole('event-manager');

    $response = $this->actingAs($user)->get('/events');
    $response->assertStatus(200);

    // Event manager specific permissions
    $shouldHave = [
        'view events',
        'create events',
        'edit events',
        'delete events',
        'manage event participants',
        'view event analytics',
        'view appointments',
        'view stocks',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Event Manager should have permission: {$permission}");
    }

    // Event manager should NOT have project permissions
    $shouldNotHave = [
        'view projects',
        'view programs',
        'view workflows',
        'view forms',
        'view reports',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Event Manager should NOT have permission: {$permission}");
    }
});

// ============================================
// Department Leader Role Tests
// ============================================

it('department leader has department and needs management access', function () {
    $user = User::factory()->create();
    $user->assignRole('department-leader');

    $response = $this->actingAs($user)->get('/departments');
    $response->assertStatus(200);

    // Department leader specific permissions
    $shouldHave = [
        'view departments',
        'edit departments',
        'manage departments',
        'view workflows',
        'view forms',
        'view needs',
        'view programs',
        'view stocks',
        'view reports',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Department Leader should have permission: {$permission}");
    }
});

// ============================================
// Employee Role Tests
// ============================================

it('employee has extended viewing permissions including projects', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');

    $response = $this->actingAs($user)->get('/events');
    $response->assertStatus(200);

    // Employee specific permissions
    $shouldHave = [
        'view projects',
        'view departments',
        'view needs',
        'view reports',
        'view tasks',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Employee should have permission: {$permission}");
    }

    // Employee should NOT have management permissions
    $shouldNotHave = [
        'manage trainings',
        'view workflows',
        'view forms',
        'view programs',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Employee should NOT have permission: {$permission}");
    }
});

// ============================================
// Library Manager Role Tests
// ============================================

it('library manager has book management access', function () {
    $user = User::factory()->create();
    $user->assignRole('library-manager');

    $response = $this->actingAs($user)->get('/books');
    $response->assertStatus(200);

    // Library manager specific permissions
    $shouldHave = [
        'view books',
        'create books',
        'edit books',
        'delete books',
        'manage library',
        'approve rentals',
        'view reports',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Library Manager should have permission: {$permission}");
    }

    // Library manager should NOT have project/workflow permissions
    $shouldNotHave = [
        'view projects',
        'view workflows',
        'view programs',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Library Manager should NOT have permission: {$permission}");
    }
});

// ============================================
// Writer Role Tests
// ============================================

it('writer has content management access', function () {
    $user = User::factory()->create();
    $user->assignRole('writer');

    $response = $this->actingAs($user)->get('/articles');
    $response->assertStatus(200);

    // Writer specific permissions
    $shouldHave = [
        'view articles',
        'create articles',
        'edit articles',
        'delete articles',
        'publish articles',
        'view videos',
        'upload videos',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Writer should have permission: {$permission}");
    }

    // Writer should NOT have project/department management
    $shouldNotHave = [
        'view projects',
        'view programs',
        'view workflows',
        'view needs',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Writer should NOT have permission: {$permission}");
    }
});

// ============================================
// Star (Volunteer) Role Tests
// ============================================

it('star volunteer has basic needs submission access', function () {
    $user = User::factory()->create();
    $user->assignRole('star');

    $response = $this->actingAs($user)->get('/events');
    $response->assertStatus(200);

    // Star specific permissions
    $shouldHave = [
        'view needs',
        'create needs',
        'view departments',
        'view tasks',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("Star should have permission: {$permission}");
    }

    // Star should NOT have management permissions
    $shouldNotHave = [
        'view projects',
        'view programs',
        'view workflows',
        'view forms',
        'view reports',
    ];

    foreach ($shouldNotHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeFalse("Star should NOT have permission: {$permission}");
    }
});

// ============================================
// Multiple Roles Tests
// ============================================

it('user with multiple roles receives combined permissions', function () {
    $user = User::factory()->create();
    $user->assignRole(['member', 'teacher']);

    $response = $this->actingAs($user)->get('/trainings');
    $response->assertStatus(200);

    // Should have permissions from both roles
    $shouldHave = [
        // From member
        'view articles',
        'view needs',
        // From teacher
        'manage trainings',
        'access teacher dashboard',
        'view reports',
    ];

    foreach ($shouldHave as $permission) {
        expect(userHasPermissionInProps($response, $permission))
            ->toBeTrue("User with multiple roles should have permission: {$permission}");
    }
});

// ============================================
// Route Access Control Tests
// ============================================

it('member cannot access user management route', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $response = $this->actingAs($user)->get('/user-management');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('super admin can access user management route', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $response = $this->actingAs($user)->get('/user-management');
    $response->assertStatus(200);
});

it('member cannot access pastoral availability route', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $response = $this->actingAs($user)->get('/pastoral-availability');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('pastor can access pastoral availability route', function () {
    $user = User::factory()->create();
    $user->assignRole('pastor');

    $response = $this->actingAs($user)->get('/pastoral-availability');
    // Should be accessible (200) or redirect to specific area
    expect($response->status())->toBeIn([200, 302]);
});

it('student cannot access teacher dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)->get('/teacher/dashboard');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('teacher can access teacher dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    $response = $this->actingAs($user)->get('/teacher/dashboard');
    $response->assertStatus(200);
});

it('teacher cannot access student dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    $response = $this->actingAs($user)->get('/student/dashboard');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('student can access student dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)->get('/student/dashboard');
    $response->assertStatus(200);
});

it('member cannot access projects route', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $response = $this->actingAs($user)->get('/projects');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('project manager can access projects route', function () {
    $user = User::factory()->create();
    $user->assignRole('project-manager');

    $response = $this->actingAs($user)->get('/projects');
    $response->assertStatus(200);
});

it('member cannot access workflows route', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $response = $this->actingAs($user)->get('/workflows');
    // Should be forbidden or redirected
    expect($response->status())->toBeIn([302, 403]);
});

it('department leader can access workflows route', function () {
    $user = User::factory()->create();
    $user->assignRole('department-leader');

    $response = $this->actingAs($user)->get('/workflows');
    $response->assertStatus(200);
});
