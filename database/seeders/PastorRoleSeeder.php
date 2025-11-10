<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
            'manage pastor availability',
            'view pastoral care',
            'manage pastoral appointments',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $pastorRole->givePermissionTo($perm);
        }

        $this->command->info('Pastor role and permissions created successfully!');
    }
}