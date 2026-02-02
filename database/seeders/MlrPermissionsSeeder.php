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

        // Create MLR Agent role
        $mlrAgentRole = Role::firstOrCreate(['name' => 'mlr_agent']);

        // Assign all MLR permissions to mlr_agent role
        $mlrAgentRole->syncPermissions([
            // MLR-specific permissions
            'view mlr dashboard',
            'view all pastoral care',
            'transfer pastoral care',
            'view pastoral care statistics',
            // Base pastoral care permissions
            'view pastoral care',
            'create pastoral care',
            'edit pastoral care',
            'manage pastoral care',
            'select pastor for pastoral care',
        ]);

        $this->command->info('✅ Created MLR Agent role with permissions');

        // Also give MLR permissions to admin, SuperAdmin and pastor roles
        $adminRoles = Role::whereIn('name', ['admin', 'SuperAdmin'])->get();
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
