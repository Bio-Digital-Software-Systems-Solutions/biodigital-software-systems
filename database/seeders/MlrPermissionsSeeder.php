<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MlrPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create MLR-specific permissions
        $mlrPermissions = [
            'view mlr dashboard',
            'view all pastoral care',
            'transfer pastoral care',
            'view pastoral care statistics',
        ];

        foreach ($mlrPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create MLR Agent role (standardized kebab-case)
        $mlrAgentRole = Role::firstOrCreate(['name' => 'mlr-agent']);

        // Assign MLR permissions to mlr-agent role
        // Note: mlr-agent does NOT have "view all pastoral care" - they only see their own appointments
        $mlrAgentRole->syncPermissions([
            // MLR-specific permissions (excluding "view all pastoral care")
            'view mlr dashboard',
            'transfer pastoral care',
            'view pastoral care statistics',
            // Base pastoral care permissions
            'view pastoral care',
            'create pastoral care',
            'edit pastoral care',
            'select pastor for pastoral care',
        ]);

        $this->command->info('✅ Created MLR Agent role with permissions');

        // Also give MLR permissions to admin, super-admin and pastor roles
        $adminRoles = Role::whereIn('name', ['admin', 'super-admin'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($mlrPermissions);
            $this->command->info("✅ Assigned MLR permissions to {$adminRole->name} role");
        }

        $pastorRole = Role::where('name', 'pastor')->first();
        if ($pastorRole) {
            $pastorRole->givePermissionTo([
                'view mlr dashboard',
                'view pastoral care statistics',
            ]);
            $this->command->info('✅ Assigned limited MLR permissions to pastor role');
        }

        $this->command->info('🎉 MLR permissions and role successfully configured!');
    }
}
