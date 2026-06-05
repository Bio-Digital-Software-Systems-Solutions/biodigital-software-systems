<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CareServiceDashboardPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Care service dashboard permissions
        $dashboardPermissions = [
            'view care service dashboard',
            'view all care service',
            'transfer care service',
            'view care service statistics',
        ];

        foreach ($dashboardPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Care service agent role (standardized kebab-case)
        $agentRole = Role::firstOrCreate(['name' => 'care-service-agent']);

        // Note: care-service-agent does NOT have "view all care service" - they only see their own appointments
        $agentRole->syncPermissions([
            'view care service dashboard',
            'transfer care service',
            'view care service statistics',
            'view care service',
            'create care service',
            'edit care service',
            'select pastor for care service',
        ]);

        $this->command->info('✅ Created care service agent role with permissions');

        // Also give dashboard permissions to admin, super-admin roles
        $adminRoles = Role::whereIn('name', ['admin', 'super-admin'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($dashboardPermissions);
            $this->command->info("✅ Assigned care service dashboard permissions to {$adminRole->name} role");
        }

        $pastorRole = Role::where('name', 'pastor')->first();
        if ($pastorRole) {
            $pastorRole->givePermissionTo([
                'view care service dashboard',
                'view care service statistics',
            ]);
            $this->command->info('✅ Assigned limited care service dashboard permissions to pastor role');
        }

        $this->command->info('🎉 Care service dashboard permissions and role successfully configured!');
    }
}
