<?php

namespace Database\Seeders;

use App\Enums\Star\StarCategory;
use App\Models\Department;
use App\Models\Star;
use App\Models\User;
use Illuminate\Database\Seeder;

class StarSeeder extends Seeder
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

        // Create featured stars (visible on homepage/public)
        Star::factory(3)
            ->active()
            ->featured()
            ->leader()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
            ]);

        // Regular active volunteers
        Star::factory(8)
            ->active()
            ->volunteer()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Leaders
        Star::factory(3)
            ->active()
            ->leader()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Mentors (experienced volunteers)
        Star::factory(2)
            ->active()
            ->mentor()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
            ]);

        // Coordinators
        Star::factory(2)
            ->active()
            ->coordinator()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Ambassador (top-level volunteer)
        Star::factory(1)
            ->active()
            ->ambassador()
            ->featured()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'nominated_by' => $nominator?->id,
                'title' => 'Ambassadeur de l\'année',
            ]);

        // Stars on break
        Star::factory(2)
            ->onBreak()
            ->volunteer()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Recently recognized stars
        Star::factory(3)
            ->active()
            ->volunteer()
            ->recentlyRecognized()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Stars expiring soon (need renewal)
        Star::factory(2)
            ->active()
            ->volunteer()
            ->expiringSoon(15)
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Graduated stars (completed their service)
        Star::factory(2)
            ->graduated()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // High-level experienced stars
        Star::factory(2)
            ->active()
            ->highLevel()
            ->publicProfile()
            ->create([
                'department_id' => $defaultDepartment?->id,
                'total_hours_served' => fake()->numberBetween(500, 1000),
            ]);

        // Inactive star
        Star::factory(1)
            ->inactive()
            ->create([
                'department_id' => $defaultDepartment?->id,
            ]);

        // Stars in different categories
        foreach (StarCategory::cases() as $category) {
            Star::factory(1)
                ->active()
                ->volunteer()
                ->inCategory($category)
                ->create([
                    'department_id' => $defaultDepartment?->id,
                ]);
        }
    }
}
