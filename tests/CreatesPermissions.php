<?php

namespace Tests;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesPermissions
{
    /**
     * Setup default permissions and roles for testing
     */
    protected function setupPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view events',
            'create events',
            'edit events',
            'delete events',
            'attend events',
            'manage event participants',
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'view books',
            'rent books',
            'manage library',
            'use chat',
            'view groups',
            'create groups',
            'edit groups',
            'delete groups',
            'view departments',
            'manage departments',
            'view programs',
            'manage programs',
            'view stocks',
            'manage stocks',
            'view visitors',
            'create visitors',
            'edit visitors',
            'delete visitors',
            'manage integration pathways',
            'view integration scores',
            'respond integration suggestions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $roles = [
            'admin' => Permission::all(),
            'event-manager' => ['view events', 'create events', 'edit events', 'delete events'],
            'writer' => ['view articles', 'create articles', 'edit articles', 'delete articles'],
            'member' => ['view events', 'view articles', 'view books', 'use chat'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $role->syncPermissions($rolePermissions);
        }
    }

    /**
     * Create a specific permission if it doesn't exist
     */
    protected function createPermission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    /**
     * Create a specific role if it doesn't exist
     */
    protected function createRole(string $name, array $permissions = []): Role
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        if ($permissions !== []) {
            $permissionModels = [];
            foreach ($permissions as $permission) {
                $permissionModels[] = Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]);
            }
            $role->syncPermissions($permissionModels);
        }

        return $role;
    }
}
