<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First: Roles and Permissions
        $this->call([
            RolesAndPermissionsSeeder::class,
            PastoralCarePermissionsSeeder::class,
            PastorRoleSeeder::class,
        ]);

        // Second: Users with roles
        $this->call([
            UserSeeder::class,
        ]);

        // Third: All other data in the correct order
        $this->call([
            EmployeeSeeder::class,
            StarSeeder::class,
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
            EpicSeeder::class,
            SprintSeeder::class,
            TaskSeeder::class,
            StockSeeder::class,
            ProjectSeeder::class,
            ProjectTaskHistoricalSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            TrainingSeeder::class,
            TrainingTopicSeeder::class,
            TrainingMaterialSeeder::class,
            TrainingClassSeeder::class,
            TrainingClassScheduleSeeder::class,
            QuizSeeder::class,
            QuizAttemptSeeder::class,
            PastorAvailabilitySeeder::class,
            PastoralCareSeeder::class,
            DepartmentTodoSeeder::class,
            MlrPermissionsSeeder::class,
            PastoralCareThemeSeeder::class,
            OhadaAccountSeeder::class,
            PcgAccountSeeder::class,
            IfrsAccountSeeder::class,
        ]);
    }
}
