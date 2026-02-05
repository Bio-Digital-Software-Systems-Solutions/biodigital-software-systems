<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================================
        // Super Admin
        // ========================================
        $superAdmin = User::firstOrCreate(
            ['email' => 'super.admin@aig-app.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'uuid' => Str::uuid(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'birth_date' => '1980-01-15',
            ]
        );
        if (! $superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }

        // ========================================
        // Admin
        // ========================================
        $admin = User::firstOrCreate(
            ['email' => 'admin@aig-app.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'uuid' => Str::uuid(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'birth_date' => '1985-06-20',
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // ========================================
        // Pastors (3 pastors)
        // ========================================
        $pastors = [
            ['first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean.dupont@icc-munich.de', 'birth_date' => '1975-03-10'],
            ['first_name' => 'Pierre', 'last_name' => 'Martin', 'email' => 'pierre.martin@icc-munich.de', 'birth_date' => '1968-11-25'],
            ['first_name' => 'Marie', 'last_name' => 'Bernard', 'email' => 'marie.bernard@icc-munich.de', 'birth_date' => '1982-07-14'],
        ];

        foreach ($pastors as $pastorData) {
            $pastor = User::firstOrCreate(
                ['email' => $pastorData['email']],
                [
                    'first_name' => $pastorData['first_name'],
                    'last_name' => $pastorData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $pastorData['birth_date'],
                ]
            );
            if (! $pastor->hasRole('pastor')) {
                $pastor->assignRole('pastor');
            }
        }

        // ========================================
        // Teachers (4 teachers)
        // ========================================
        $teachers = [
            ['first_name' => 'François', 'last_name' => 'Leclerc', 'email' => 'francois.leclerc@icc-munich.de', 'birth_date' => '1978-04-05'],
            ['first_name' => 'Sophie', 'last_name' => 'Moreau', 'email' => 'sophie.moreau@icc-munich.de', 'birth_date' => '1983-09-18'],
            ['first_name' => 'Antoine', 'last_name' => 'Petit', 'email' => 'antoine.petit@icc-munich.de', 'birth_date' => '1976-12-01'],
            ['first_name' => 'Claire', 'last_name' => 'Dubois', 'email' => 'claire.dubois@icc-munich.de', 'birth_date' => '1990-02-28'],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $teacherData['birth_date'],
                ]
            );
            if (! $teacher->hasRole('teacher')) {
                $teacher->assignRole('teacher');
            }
        }

        // ========================================
        // Students (8 students)
        // ========================================
        $students = [
            ['first_name' => 'Lucas', 'last_name' => 'Blanc', 'email' => 'lucas.blanc@icc-munich.de', 'birth_date' => '1998-05-12'],
            ['first_name' => 'Emma', 'last_name' => 'Roux', 'email' => 'emma.roux@icc-munich.de', 'birth_date' => '2000-08-23'],
            ['first_name' => 'Noah', 'last_name' => 'Garcia', 'email' => 'noah.garcia@icc-munich.de', 'birth_date' => '1999-01-07'],
            ['first_name' => 'Léa', 'last_name' => 'Martinez', 'email' => 'lea.martinez@icc-munich.de', 'birth_date' => '2001-11-30'],
            ['first_name' => 'Hugo', 'last_name' => 'Lopez', 'email' => 'hugo.lopez@icc-munich.de', 'birth_date' => '1997-06-15'],
            ['first_name' => 'Chloé', 'last_name' => 'Girard', 'email' => 'chloe.girard@icc-munich.de', 'birth_date' => '2002-03-20'],
            ['first_name' => 'Louis', 'last_name' => 'Andre', 'email' => 'louis.andre@icc-munich.de', 'birth_date' => '1996-09-08'],
            ['first_name' => 'Jade', 'last_name' => 'Leroy', 'email' => 'jade.leroy@icc-munich.de', 'birth_date' => '2003-04-17'],
        ];

        foreach ($students as $studentData) {
            $student = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $studentData['birth_date'],
                ]
            );
            if (! $student->hasRole('student')) {
                $student->assignRole('student');
            }
        }

        // ========================================
        // Stars (Volunteers - 5 stars)
        // ========================================
        $stars = [
            ['first_name' => 'Thomas', 'last_name' => 'Mercier', 'email' => 'thomas.mercier@icc-munich.de', 'birth_date' => '1992-07-22'],
            ['first_name' => 'Camille', 'last_name' => 'Simon', 'email' => 'camille.simon@icc-munich.de', 'birth_date' => '1988-10-14'],
            ['first_name' => 'Maxime', 'last_name' => 'Laurent', 'email' => 'maxime.laurent@icc-munich.de', 'birth_date' => '1995-02-05'],
            ['first_name' => 'Julie', 'last_name' => 'Lefebvre', 'email' => 'julie.lefebvre@icc-munich.de', 'birth_date' => '1991-12-28'],
            ['first_name' => 'Alexandre', 'last_name' => 'Michel', 'email' => 'alexandre.michel@icc-munich.de', 'birth_date' => '1987-08-03'],
        ];

        foreach ($stars as $starData) {
            $star = User::firstOrCreate(
                ['email' => $starData['email']],
                [
                    'first_name' => $starData['first_name'],
                    'last_name' => $starData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $starData['birth_date'],
                ]
            );
            if (! $star->hasRole('star')) {
                $star->assignRole('star');
            }
        }

        // ========================================
        // Event Managers (2 event managers)
        // ========================================
        $eventManagers = [
            ['first_name' => 'Nicolas', 'last_name' => 'Fournier', 'email' => 'nicolas.fournier@icc-munich.de', 'birth_date' => '1986-05-19'],
            ['first_name' => 'Isabelle', 'last_name' => 'Morel', 'email' => 'isabelle.morel@icc-munich.de', 'birth_date' => '1984-01-11'],
        ];

        foreach ($eventManagers as $managerData) {
            $manager = User::firstOrCreate(
                ['email' => $managerData['email']],
                [
                    'first_name' => $managerData['first_name'],
                    'last_name' => $managerData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $managerData['birth_date'],
                ]
            );
            if (! $manager->hasRole('event-manager')) {
                $manager->assignRole('event-manager');
            }
        }

        // ========================================
        // Project Managers (2 project managers)
        // ========================================
        $projectManagers = [
            ['first_name' => 'David', 'last_name' => 'Robert', 'email' => 'david.robert@icc-munich.de', 'birth_date' => '1981-09-25'],
            ['first_name' => 'Nathalie', 'last_name' => 'Richard', 'email' => 'nathalie.richard@icc-munich.de', 'birth_date' => '1979-06-07'],
        ];

        foreach ($projectManagers as $pmData) {
            $pm = User::firstOrCreate(
                ['email' => $pmData['email']],
                [
                    'first_name' => $pmData['first_name'],
                    'last_name' => $pmData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $pmData['birth_date'],
                ]
            );
            if (! $pm->hasRole('project-manager')) {
                $pm->assignRole('project-manager');
            }
        }

        // ========================================
        // Writers / Content Editors (2 writers)
        // ========================================
        $writers = [
            ['first_name' => 'Olivier', 'last_name' => 'Durand', 'email' => 'olivier.durand@icc-munich.de', 'birth_date' => '1989-04-16'],
            ['first_name' => 'Céline', 'last_name' => 'Bonnet', 'email' => 'celine.bonnet@icc-munich.de', 'birth_date' => '1993-11-02'],
        ];

        foreach ($writers as $writerData) {
            $writer = User::firstOrCreate(
                ['email' => $writerData['email']],
                [
                    'first_name' => $writerData['first_name'],
                    'last_name' => $writerData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $writerData['birth_date'],
                ]
            );
            if (! $writer->hasRole('writer')) {
                $writer->assignRole('writer');
            }
        }

        // ========================================
        // Members (Regular church members - 10 members)
        // ========================================
        $members = [
            ['first_name' => 'Member', 'last_name' => 'Test', 'email' => 'member@icc-munich.de', 'birth_date' => '1990-01-01'],
            ['first_name' => 'Philippe', 'last_name' => 'Garnier', 'email' => 'philippe.garnier@icc-munich.de', 'birth_date' => '1970-03-08'],
            ['first_name' => 'Sandrine', 'last_name' => 'Faure', 'email' => 'sandrine.faure@icc-munich.de', 'birth_date' => '1985-07-21'],
            ['first_name' => 'Julien', 'last_name' => 'Lemoine', 'email' => 'julien.lemoine@icc-munich.de', 'birth_date' => '1994-10-30'],
            ['first_name' => 'Valérie', 'last_name' => 'Rousseau', 'email' => 'valerie.rousseau@icc-munich.de', 'birth_date' => '1978-05-14'],
            ['first_name' => 'Sébastien', 'last_name' => 'Vincent', 'email' => 'sebastien.vincent@icc-munich.de', 'birth_date' => '1983-12-09'],
            ['first_name' => 'Aurélie', 'last_name' => 'Muller', 'email' => 'aurelie.muller@icc-munich.de', 'birth_date' => '1991-02-18'],
            ['first_name' => 'Mathieu', 'last_name' => 'Lefevre', 'email' => 'mathieu.lefevre@icc-munich.de', 'birth_date' => '1973-08-26'],
            ['first_name' => 'Stéphanie', 'last_name' => 'Chevalier', 'email' => 'stephanie.chevalier@icc-munich.de', 'birth_date' => '1989-01-03'],
            ['first_name' => 'Romain', 'last_name' => 'Francois', 'email' => 'romain.francois@icc-munich.de', 'birth_date' => '1996-06-11'],
            ['first_name' => 'Marine', 'last_name' => 'Legrand', 'email' => 'marine.legrand@icc-munich.de', 'birth_date' => '2000-09-24'],
        ];

        foreach ($members as $memberData) {
            $member = User::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'first_name' => $memberData['first_name'],
                    'last_name' => $memberData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $memberData['birth_date'],
                ]
            );
            if (! $member->hasRole('member')) {
                $member->assignRole('member');
            }
        }

        // ========================================
        // Department Leaders (2 department leaders)
        // ========================================
        $departmentLeaders = [
            ['first_name' => 'Christophe', 'last_name' => 'Henry', 'email' => 'christophe.henry@icc-munich.de', 'birth_date' => '1977-11-17'],
            ['first_name' => 'Véronique', 'last_name' => 'Masson', 'email' => 'veronique.masson@icc-munich.de', 'birth_date' => '1980-04-29'],
        ];

        foreach ($departmentLeaders as $leaderData) {
            $leader = User::firstOrCreate(
                ['email' => $leaderData['email']],
                [
                    'first_name' => $leaderData['first_name'],
                    'last_name' => $leaderData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $leaderData['birth_date'],
                ]
            );
            if (! $leader->hasRole('department-leader')) {
                $leader->assignRole('department-leader');
            }
        }

        // ========================================
        // Group Leaders (2 group leaders)
        // ========================================
        $groupLeaders = [
            ['first_name' => 'Patrick', 'last_name' => 'Brunet', 'email' => 'patrick.brunet@icc-munich.de', 'birth_date' => '1982-08-12'],
            ['first_name' => 'Caroline', 'last_name' => 'Guerin', 'email' => 'caroline.guerin@icc-munich.de', 'birth_date' => '1986-03-25'],
        ];

        foreach ($groupLeaders as $leaderData) {
            $leader = User::firstOrCreate(
                ['email' => $leaderData['email']],
                [
                    'first_name' => $leaderData['first_name'],
                    'last_name' => $leaderData['last_name'],
                    'uuid' => Str::uuid(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'birth_date' => $leaderData['birth_date'],
                ]
            );
            if (! $leader->hasRole('group-leader')) {
                $leader->assignRole('group-leader');
            }
        }

        // ========================================
        // Library Manager (1)
        // ========================================
        $libraryManager = User::firstOrCreate(
            ['email' => 'bibliotheque@icc-munich.de'],
            [
                'first_name' => 'Margot',
                'last_name' => 'Dupuis',
                'uuid' => Str::uuid(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'birth_date' => '1988-06-30',
            ]
        );
        if (! $libraryManager->hasRole('library-manager')) {
            $libraryManager->assignRole('library-manager');
        }

        // ========================================
        // Summary
        // ========================================
        $this->command->info('✅ UserSeeder completed successfully!');
        $this->command->info('   - 1 Super Admin');
        $this->command->info('   - 1 Admin');
        $this->command->info('   - 3 Pastors');
        $this->command->info('   - 4 Teachers');
        $this->command->info('   - 8 Students');
        $this->command->info('   - 5 Stars (Volunteers)');
        $this->command->info('   - 2 Event Managers');
        $this->command->info('   - 2 Project Managers');
        $this->command->info('   - 2 Writers');
        $this->command->info('   - 11 Members');
        $this->command->info('   - 2 Department Leaders');
        $this->command->info('   - 2 Group Leaders');
        $this->command->info('   - 1 Library Manager');
        $this->command->info('   Total: 44 users');
        $this->command->newLine();
        $this->command->info('🔐 Default password for all users: password');
    }
}
