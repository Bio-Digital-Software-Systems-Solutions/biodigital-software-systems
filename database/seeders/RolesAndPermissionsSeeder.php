<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Articles
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            // Events
            'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view programs', 'create programs', 'edit programs', 'delete programs',
            // Stocks
            'view stocks', 'manage stocks', 'approve stock requests',
            // Groups
            'view groups', 'create groups', 'edit groups', 'delete groups', 'manage group members',
            // Users & Departments
            'view users', 'create users', 'edit users', 'delete users',
            'view departments', 'create departments', 'edit departments', 'delete departments', 'manage departments', 'assign department members',
            // Books & Library
            'view books', 'rent books', 'create books', 'edit books', 'delete books', 'manage library', 'approve rentals',
            // Videos
            'view videos', 'upload videos', 'edit videos', 'delete videos',
            // Trainings
            'view trainings', 'create trainings', 'edit trainings', 'delete trainings', 'manage trainings',
            // Quiz & Evaluations
            'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'manage quizzes', 'take quizzes', 'grade quizzes',
            'view evaluations', 'create evaluations', 'edit evaluations', 'delete evaluations', 'manage evaluations',
            // Messages
            'view messages', 'create messages', 'edit messages', 'delete messages',
            // Chat
            'use chat', 'moderate chat',
            // Contacts
            'view contacts', 'manage contacts',
            // Hero Slides
            'view hero slides', 'manage hero slides',
            // System
            'view system settings', 'manage system settings',
            'view reports', 'generate reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin - All permissions (lowercase)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            // Articles
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            // Events
            'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view programs', 'create programs', 'edit programs', 'delete programs',
            // Stocks
            'view stocks', 'manage stocks', 'approve stock requests',
            // Groups
            'view groups', 'create groups', 'edit groups', 'delete groups', 'manage group members',
            // Users & Departments
            'view users', 'edit users',
            'view departments', 'create departments', 'edit departments', 'manage departments', 'assign department members',
            // Books & Library
            'view books', 'rent books', 'create books', 'edit books', 'delete books', 'manage library', 'approve rentals',
            // Videos
            'view videos', 'upload videos', 'edit videos', 'delete videos',
            // Trainings
            'view trainings', 'create trainings', 'edit trainings', 'delete trainings', 'manage trainings',
            // Quiz & Evaluations
            'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'manage quizzes', 'take quizzes', 'grade quizzes',
            'view evaluations', 'create evaluations', 'edit evaluations', 'delete evaluations', 'manage evaluations',
            // Messages
            'view messages', 'create messages', 'edit messages', 'delete messages',
            // Chat
            'use chat', 'moderate chat',
            // Contacts
            'view contacts', 'manage contacts',
            // Hero Slides
            'view hero slides', 'manage hero slides',
            // Reports
            'view reports', 'generate reports',
        ]);

        // Writer - Content management focused
        $writer = Role::firstOrCreate(['name' => 'writer']);
        $writer->syncPermissions([
            // Articles
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            // Videos
            'view videos', 'upload videos', 'edit videos', 'delete videos',
            // Hero Slides
            'view hero slides', 'manage hero slides',
            // Books
            'view books', 'create books', 'edit books',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Basic viewing
            'view events', 'attend events', 'view departments', 'view groups',
        ]);

        // Project Manager - Projects, tasks, and team management
        $projectManager = Role::firstOrCreate(['name' => 'project-manager']);
        $projectManager->syncPermissions([
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view programs', 'create programs', 'edit programs', 'delete programs',
            // Groups & Teams
            'view groups', 'create groups', 'edit groups', 'manage group members',
            // Stocks
            'view stocks', 'manage stocks',
            // Users & Departments
            'view users', 'view departments',
            // Events
            'view events', 'create events', 'edit events', 'attend events',
            // Articles
            'view articles', 'create articles', 'edit articles',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Reports
            'view reports', 'generate reports',
            // Others
            'view books', 'view videos',
        ]);

        // Event Manager - Events and related activities
        $eventManager = Role::firstOrCreate(['name' => 'event-manager']);
        $eventManager->syncPermissions([
            // Events
            'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
            // Groups
            'view groups', 'manage group members',
            // Users & Departments
            'view users', 'view departments',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Articles
            'view articles', 'create articles',
            // Others
            'view books', 'view videos', 'view stocks',
        ]);

        // Library Manager - Library and books management
        $libraryManager = Role::firstOrCreate(['name' => 'library-manager']);
        $libraryManager->syncPermissions([
            // Books & Library
            'view books', 'rent books', 'create books', 'edit books', 'delete books', 'manage library', 'approve rentals',
            // Articles for library announcements
            'view articles', 'create articles', 'edit articles',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Basic viewing
            'view users', 'view departments', 'view events', 'attend events',
            // Reports
            'view reports',
        ]);

        // Group Leader - Manage assigned group
        $groupLeader = Role::firstOrCreate(['name' => 'group-leader']);
        $groupLeader->syncPermissions([
            // Groups
            'view groups', 'edit groups', 'manage group members',
            // Events
            'view events', 'create events', 'attend events',
            // Tasks
            'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Articles
            'view articles', 'create articles',
            // Others
            'view books', 'rent books', 'view videos', 'view users', 'view departments',
        ]);

        // Department Leader - Manage department
        $departmentLeader = Role::firstOrCreate(['name' => 'department-leader']);
        $departmentLeader->syncPermissions([
            // Departments
            'view departments', 'edit departments', 'manage departments', 'assign department members',
            // Users
            'view users',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
            'view programs', 'create programs', 'edit programs',
            // Events
            'view events', 'create events', 'edit events', 'attend events',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Articles
            'view articles', 'create articles', 'edit articles',
            // Others
            'view books', 'rent books', 'view videos', 'view groups', 'view stocks',
            // Reports
            'view reports',
        ]);

        // Impact Family Leader - Manage impact families and related activities
        $impactFamilyLeader = Role::firstOrCreate(['name' => 'impact-family-leader']);
        $impactFamilyLeader->syncPermissions([
            // Groups (Impact Families)
            'view groups', 'edit groups', 'manage group members',
            // Events
            'view events', 'create events', 'edit events', 'attend events', 'manage event participants',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
            'view programs', 'view programs',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Articles
            'view articles', 'create articles', 'edit articles',
            // Users & Departments
            'view users', 'view departments',
            // Others
            'view books', 'rent books', 'view videos', 'view stocks',
            // Reports
            'view reports',
        ]);

        // Member - Basic access for regular users
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            // Viewing permissions
            'view articles',
            'view trainings',
            //'view events', 'view videos', 'view books', 'view departments', 'view groups', 'view programs', 'view tasks',
            // Books
            //'rent books',
            // Messages & Chat
            //'view messages', 'create messages', 'delete messages',
            //'use chat',
        ]);

        // Student - Access to student dashboard and training materials
        $student = Role::firstOrCreate(['name' => 'student']);
        $student->syncPermissions([
            // Viewing permissions
            'view articles', 'view events', 'attend events', 'view videos', 'view books', 'view departments',
            // Books
            'rent books',
            // Trainings
            'view trainings',
            // Quiz & Evaluations
            'take quizzes',
            // Messages & Chat
            'view messages', 'create messages', 'delete messages',
            'use chat',
        ]);

        // Teacher - Access to teacher dashboard and training class management
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $teacher->syncPermissions([
            // Viewing permissions
            'view articles', 'view events', 'attend events', 'view videos', 'view books', 'view users', 'view departments',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
            // Books
            'view books', 'rent books',
            // Trainings
            'view trainings', 'create trainings', 'edit trainings', 'manage trainings',
            // Quiz & Evaluations
            'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'manage quizzes', 'take quizzes', 'grade quizzes',
            'view evaluations', 'create evaluations', 'edit evaluations', 'delete evaluations', 'manage evaluations',
            // Messages & Chat
            'view messages', 'create messages', 'edit messages', 'delete messages',
            'use chat',
            // Reports
            'view reports',
        ]);

        // Backward compatibility aliases for PascalCase role names
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $superAdmin->syncPermissions(Permission::all());

        $adminAlias = Role::firstOrCreate(['name' => 'Admin']);
        $adminAlias->syncPermissions($admin->permissions);

        $memberAlias = Role::firstOrCreate(['name' => 'Member']);
        $memberAlias->syncPermissions($member->permissions);

        $studentAlias = Role::firstOrCreate(['name' => 'Student']);
        $studentAlias->syncPermissions($student->permissions);

        $teacherAlias = Role::firstOrCreate(['name' => 'Teacher']);
        $teacherAlias->syncPermissions($teacher->permissions);

        $projectManagerAlias = Role::firstOrCreate(['name' => 'ProjectManager']);
        $projectManagerAlias->syncPermissions($projectManager->permissions);

        $eventManagerAlias = Role::firstOrCreate(['name' => 'EventManager']);
        $eventManagerAlias->syncPermissions($eventManager->permissions);

        $editorAlias = Role::firstOrCreate(['name' => 'Editor']);
        $editorAlias->syncPermissions($writer->permissions);
    }
}
