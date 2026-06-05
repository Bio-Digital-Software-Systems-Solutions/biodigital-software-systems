<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create test permissions
    Permission::firstOrCreate(['name' => 'view articles']);
    Permission::firstOrCreate(['name' => 'edit articles']);
    Permission::firstOrCreate(['name' => 'view events']);
});

it('can run in dry-run mode', function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin']);

    $this->artisan('roles:unify', ['--dry-run' => true])
        ->assertSuccessful();
});

it('renames SuperAdmin to super-admin when target does not exist', function (): void {
    // Create SuperAdmin role
    $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
    $superAdmin->givePermissionTo(['view articles', 'edit articles']);

    // Create a user with SuperAdmin role
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $this->artisan('roles:unify', ['--force' => true])
        ->assertSuccessful();

    // Verify role was renamed
    expect(Role::where('name', 'SuperAdmin')->exists())->toBeFalse();
    expect(Role::where('name', 'super-admin')->exists())->toBeTrue();

    // Verify user still has the role (now renamed)
    $user->refresh();
    expect($user->hasRole('super-admin'))->toBeTrue();

    // Verify permissions were preserved
    $newRole = Role::where('name', 'super-admin')->first();
    expect($newRole->hasPermissionTo('view articles'))->toBeTrue();
    expect($newRole->hasPermissionTo('edit articles'))->toBeTrue();
});

it('migrates users from duplicate PascalCase roles to kebab-case equivalents', function (): void {
    // Create both roles (simulating a duplicate scenario)
    $kebabRole = Role::firstOrCreate(['name' => 'project-manager']);
    $kebabRole->givePermissionTo(['view articles']);

    $pascalRole = Role::firstOrCreate(['name' => 'ProjectManager']);
    $pascalRole->givePermissionTo(['view articles', 'edit articles']);

    // Create a user with PascalCase role
    $user = User::factory()->create();
    $user->assignRole('ProjectManager');

    $this->artisan('roles:unify', ['--force' => true])
        ->assertSuccessful();

    // Verify user was migrated to kebab-case role
    $user->refresh();
    expect($user->hasRole('project-manager'))->toBeTrue();

    // Verify duplicate role was deleted
    expect(Role::where('name', 'ProjectManager')->exists())->toBeFalse();
});

it('renames mlr_agent to care-service-agent', function (): void {
    // Create snake_case legacy role
    $agent = Role::firstOrCreate(['name' => 'mlr_agent']);
    $agent->givePermissionTo(['view articles']);

    // Create a user with the role
    $user = User::factory()->create();
    $user->assignRole('mlr_agent');

    $this->artisan('roles:unify', ['--force' => true])
        ->assertSuccessful();

    // Verify role was renamed to current standard name
    expect(Role::where('name', 'mlr_agent')->exists())->toBeFalse();
    expect(Role::where('name', 'care-service-agent')->exists())->toBeTrue();

    // Verify user still has the role
    $user->refresh();
    expect($user->hasRole('care-service-agent'))->toBeTrue();
});

it('renames legacy mlr-agent to care-service-agent', function (): void {
    $agent = Role::firstOrCreate(['name' => 'mlr-agent']);
    $agent->givePermissionTo(['view articles']);

    $user = User::factory()->create();
    $user->assignRole('mlr-agent');

    $this->artisan('roles:unify', ['--force' => true])
        ->assertSuccessful();

    expect(Role::where('name', 'mlr-agent')->exists())->toBeFalse();
    expect(Role::where('name', 'care-service-agent')->exists())->toBeTrue();

    $user->refresh();
    expect($user->hasRole('care-service-agent'))->toBeTrue();
});

it('reports success when roles are already standardized', function (): void {
    // Create only standardized roles
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'member']);

    $this->artisan('roles:unify', ['--dry-run' => true])
        ->assertSuccessful();
});
