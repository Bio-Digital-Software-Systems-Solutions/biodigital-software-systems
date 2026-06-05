<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CareServicePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure all care service permissions exist
        $pastoralPermissions = [
            'view care service',
            'create care service',
            'edit care service',
            'delete care service',
            'manage care service',
            'select pastor for care service',
        ];

        foreach ($pastoralPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create pastor role if it doesn't exist
        Role::firstOrCreate(['name' => 'pastor']);

        // Assign permissions to roles
        // Note: 'select pastor for care service' - allows users to choose a specific pastor
        // Users without this permission will see all available slots from all pastors automatically
        $rolePermissions = [
            'admin' => ['view care service', 'create care service', 'edit care service', 'delete care service', 'manage care service', 'select pastor for care service'],
            'pastor' => ['view care service', 'create care service', 'edit care service', 'delete care service', 'manage care service', 'select pastor for care service'],
            'project-manager' => ['view care service', 'create care service', 'edit care service', 'delete care service', 'manage care service', 'select pastor for care service'],
            'writer' => ['view care service', 'create care service', 'edit care service', 'delete care service', 'manage care service'],
            'event-manager' => ['view care service', 'create care service', 'edit care service'],
            'member' => ['view care service'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                // Remove existing care service permissions first to avoid duplicates
                $existingPastoralPermissions = $role->permissions()
                    ->where('name', 'like', '%care service%')
                    ->pluck('name')
                    ->toArray();

                if (! empty($existingPastoralPermissions)) {
                    $role->revokePermissionTo($existingPastoralPermissions);
                }

                // Assign new permissions
                $role->givePermissionTo($permissions);

                $this->command->info("✅ Assigned care service permissions to role: {$roleName}");
            } else {
                $this->command->warn("⚠️  Role '{$roleName}' not found, skipping...");
            }
        }

        $this->command->info('🎉 Care service permissions successfully configured!');
    }
}
