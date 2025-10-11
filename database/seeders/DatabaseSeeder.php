<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // Create test users with roles

        $superAdmin = User::factory()->create([
            'first_name' => 'Super Admin',
            'last_name' => 'User',
            'email' => 'super.admin@aig-app.com',
        ]);
        $superAdmin->assignRole('SuperAdmin');

        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@aig-app.com',
        ]);
        $admin->assignRole('Admin');

        $manager = User::factory()->create([
            'first_name' => 'Project',
            'last_name' => 'Manager',
            'email' => 'manager@aig-app.com',
        ]);
        $manager->assignRole('ProjectManager');

        $eventManager = User::factory()->create([
            'first_name' => 'Event',
            'last_name' => 'Manager',
            'email' => 'events@aig-app.com',
        ]);
        $eventManager->assignRole('EventManager');

        $editor = User::factory()->create([
            'first_name' => 'Content',
            'last_name' => 'Editor',
            'email' => 'editor@aig-app.com',
        ]);
        $editor->assignRole('Editor');

        $member = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@aig-app.com',
        ]);
        $member->assignRole('Member');

        // Create additional users for realistic data
        User::factory(15)->create()->each(function ($user) {
            $roles = ['Member', 'Editor', 'EventManager'];
            $user->assignRole($roles[array_rand($roles)]);
        });

        // Seed all data in the correct order
        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            StatusSeeder::class,
            DepartmentSeeder::class,
            GroupSeeder::class,
            LibrarySeeder::class,
            BookSeeder::class,
            EventSeeder::class,
            ArticleSeeder::class,
            ProgramSeeder::class,
            TaskSeeder::class,
            StockSeeder::class,
            ProjectSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            TrainingSeeder::class,
            TrainingTopicSeeder::class,
            TrainingMaterialSeeder::class,
            TrainingClassSeeder::class,
            TrainingClassScheduleSeeder::class,
            QuizSeeder::class,
            QuizAttemptSeeder::class,
        ]);
    }
}
