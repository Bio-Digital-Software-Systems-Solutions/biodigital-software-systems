<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeacherRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teacher role if it doesn't exist
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);

        // Create teach permission if it doesn't exist
        $teachPermission = Permission::firstOrCreate(['name' => 'teach']);

        // Assign permission to teacher role
        $teacherRole->givePermissionTo($teachPermission);

        $this->command->info('Teacher role and permissions created successfully!');
    }
}
