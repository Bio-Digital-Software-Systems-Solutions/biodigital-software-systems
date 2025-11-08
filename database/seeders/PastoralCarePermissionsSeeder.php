<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PastoralCarePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure all pastoral care permissions exist
        $pastoralPermissions = [
            'view pastoral care',
            'create pastoral care',
            'edit pastoral care',
            'delete pastoral care',
            'manage pastoral care'
        ];

        foreach ($pastoralPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create pastor role if it doesn't exist
        $pastorRole = Role::firstOrCreate(['name' => 'pastor']);

        // Assign permissions to roles
        $rolePermissions = [
            'admin' => ['view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care'],
            'pastor' => ['view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care'],
            'project-manager' => ['view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care'],
            'writer' => ['view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care'],
            'event-manager' => ['view pastoral care', 'create pastoral care', 'edit pastoral care'],
            'member' => ['view pastoral care'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                // Remove existing pastoral care permissions first to avoid duplicates
                $existingPastoralPermissions = $role->permissions()
                    ->where('name', 'like', '%pastoral care%')
                    ->pluck('name')
                    ->toArray();

                if (!empty($existingPastoralPermissions)) {
                    $role->revokePermissionTo($existingPastoralPermissions);
                }

                // Assign new permissions
                $role->givePermissionTo($permissions);

                $this->command->info("✅ Assigned pastoral care permissions to role: {$roleName}");
            } else {
                $this->command->warn("⚠️  Role '{$roleName}' not found, skipping...");
            }
        }

        $this->command->info("🎉 Pastoral care permissions successfully configured!");
    }
}