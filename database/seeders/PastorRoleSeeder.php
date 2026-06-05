<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PastorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the pastor role if it doesn't exist
        $pastorRole = Role::firstOrCreate(['name' => 'pastor']);

        // Create pastor-specific permissions if they don't exist
        $permissions = [
            'manage care service availability',
            'view care service',
            'manage care service appointments',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $pastorRole->givePermissionTo($perm);
        }

        $this->command->info('Pastor role and permissions created successfully!');
    }
}
