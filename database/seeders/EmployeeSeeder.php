<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing departments or create a default one
        $departments = Department::all();
        $defaultDepartment = $departments->first();

        // Create a manager first (to use as manager for other employees)
        $managerUser = User::firstOrCreate(
            ['email' => 'marie.schmidt@example.com'],
            [
                'first_name' => 'Marie',
                'last_name' => 'Schmidt',
                'email' => 'marie.schmidt@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $manager = Employee::factory()
            ->forUser($managerUser)
            ->active()
            ->fullTime()
            ->probationCompleted()
            ->create([
                'position' => 'Directeur des Ressources Humaines',
                'job_title' => 'HR Director',
                'department_id' => $defaultDepartment?->id,
            ]);

        // Create a variety of employees with different states
        // Active full-time employees
        Employee::factory(5)
            ->active()
            ->fullTime()
            ->probationCompleted()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Part-time employees
        Employee::factory(3)
            ->active()
            ->partTime()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Employees on probation
        Employee::factory(2)
            ->active()
            ->fullTime()
            ->onProbation()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Contractors with expiring contracts
        Employee::factory(2)
            ->active()
            ->contractor()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Interns
        Employee::factory(2)
            ->active()
            ->intern()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Volunteers
        Employee::factory(2)
            ->active()
            ->volunteer()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Employee on leave
        Employee::factory(1)
            ->onLeave()
            ->fullTime()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'manager_id' => $manager->id,
            ]);

        // Inactive employee
        Employee::factory(1)
            ->inactive()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Terminated employee (for history)
        Employee::factory(1)
            ->terminated()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);
    }
}
