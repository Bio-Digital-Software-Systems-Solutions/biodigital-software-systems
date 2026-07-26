<?php

namespace Database\Seeders;

use App\Enums\Volunteer\VolunteerCategory;
use App\Models\Department;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Database\Seeder;

class VolunteerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing departments
        $departments = Department::all();
        $defaultDepartment = $departments->first();

        // Get a user to be the nominator
        $nominator = User::first();

        // Create featured volunteers (visible on homepage/public)
        Volunteer::factory(3)
            ->active()
            ->featured()
            ->leader()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
            ]);

        // Regular active volunteers
        Volunteer::factory(8)
            ->active()
            ->volunteer()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Leaders
        Volunteer::factory(3)
            ->active()
            ->leader()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Mentors (experienced volunteers)
        Volunteer::factory(2)
            ->active()
            ->mentor()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
            ]);

        // Coordinators
        Volunteer::factory(2)
            ->active()
            ->coordinator()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Ambassador (top-level volunteer)
        Volunteer::factory(1)
            ->active()
            ->ambassador()
            ->featured()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
                'title' => 'Ambassadeur de l\'année',
            ]);

        // Volunteers on break
        Volunteer::factory(2)
            ->onBreak()
            ->volunteer()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Recently recognized volunteers
        Volunteer::factory(3)
            ->active()
            ->volunteer()
            ->recentlyRecognized()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Volunteers expiring soon (need renewal)
        Volunteer::factory(2)
            ->active()
            ->volunteer()
            ->expiringSoon(15)
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Graduated volunteers (completed their service)
        Volunteer::factory(2)
            ->graduated()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // High-level experienced volunteers
        Volunteer::factory(2)
            ->active()
            ->highLevel()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'total_hours_served' => fake()->numberBetween(500, 1000),
            ]);

        // Inactive volunteer
        Volunteer::factory(1)
            ->inactive()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Volunteers in different categories
        foreach (VolunteerCategory::cases() as $category) {
            Volunteer::factory(1)
                ->active()
                ->volunteer()
                ->inCategory($category)
                ->create([
                    'department_id' => $defaultDepartment?->id,
                ]);
        }
    }
}
