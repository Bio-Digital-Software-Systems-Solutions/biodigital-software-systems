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
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'publish articles',
            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            'attend events',
            'manage event participants',
            // Event Tabs Permissions
            'view event gallery',
            'manage tickets',
            'view registrations',
            'manage registrations',
            'checkin events',
            'view event analytics',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'manage appointment participants',
            // Care Service
            'view care service',
            'create care service',
            'edit care service',
            'delete care service',
            'manage care service',
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',
            'create program steps',
            // Projects
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage projects',
            // Agile module
            'view epics',
            'create epics',
            'edit epics',
            'delete epics',
            'view user stories',
            'create user stories',
            'edit user stories',
            'delete user stories',
            'complete user stories',
            'move stories to sprint',
            'view story tasks',
            'create story tasks',
            'edit story tasks',
            'delete story tasks',
            'view acceptance criteria',
            'create acceptance criteria',
            'edit acceptance criteria',
            'delete acceptance criteria',
            'validate acceptance criteria',
            'view test scenarios',
            'create test scenarios',
            'edit test scenarios',
            'delete test scenarios',
            'execute test scenarios',
            'start sprints',
            'close sprints',
            // Stocks
            'view stocks',
            'manage stocks',
            'approve stock requests',
            // Groups
            'view groups',
            'create groups',
            'edit groups',
            'delete groups',
            'manage group members',
            // Visitors
            'view visitors',
            'create visitors',
            'edit visitors',
            'delete visitors',
            'manage integration pathways',
            'view integration scores',
            'respond integration suggestions',
            // Users & Departments
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            'manage departments',
            'assign department members',
            'access all departments',
            'view department statistics',
            // Books & Library
            'view books',
            'rent books',
            'create books',
            'edit books',
            'delete books',
            'manage library',
            'approve rentals',
            // Videos
            'view videos',
            'upload videos',
            'edit videos',
            'delete videos',
            // Trainings
            'view trainings',
            'create trainings',
            'edit trainings',
            'delete trainings',
            'manage trainings',
            'manage training access',
            // Quiz & Evaluations
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'manage quizzes',
            'take quizzes',
            'grade quizzes',
            'view evaluations',
            'create evaluations',
            'edit evaluations',
            'delete evaluations',
            'manage evaluations',
            // Messages
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            // Chat
            'use chat',
            'moderate chat',
            // Contacts
            'view contacts',
            'manage contacts',
            // Hero Slides
            'view hero slides',
            'manage hero slides',
            // Homepage Sections
            'manage homepage sections',
            // Dashboard Access
            'access student dashboard',
            'access teacher dashboard',
            // System
            'view system settings',
            'manage system settings',
            'view reports',
            'generate reports',
            // Workflows
            'view workflows',
            'create workflows',
            'edit workflows',
            'delete workflows',
            'manage workflows',
            'execute workflows',
            // Forms
            'view forms',
            'create forms',
            'edit forms',
            'delete forms',
            'manage forms',
            'submit forms',
            'process form submissions',
            // Needs (Department Needs)
            'view needs',
            'create needs',
            'edit needs',
            'delete needs',
            'approve needs',
            'manage needs',
            // Employees (HR)
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage employees',
            // Stars (Volunteers)
            'view stars',
            'create stars',
            'edit stars',
            'delete stars',
            'manage stars',
            // Availabilities (Mes Disponibilités)
            'view availabilities',
            'create availabilities',
            'edit availabilities',
            'delete availabilities',
            'manage availabilities',
            // Care Service Availability (specific for care service scheduling)
            'manage care service availability',
            // Care service dashboard permissions
            'view care service dashboard',
            'view all care service',
            'transfer care service',
            'view care service statistics',
            'select pastor for care service',
            // Accounting
            'view accounting',
            'manage accounting',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin - All permissions (lowercase)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            // Articles
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'publish articles',
            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            'attend events',
            'manage event participants',
            // Event Tabs
            'view event gallery',
            'manage tickets',
            'view registrations',
            'manage registrations',
            'checkin events',
            'view event analytics',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'manage appointment participants',
            // Care Service
            'view care service',
            'create care service',
            'edit care service',
            'delete care service',
            'manage care service',
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',
            'create program steps',
            // Projects
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage projects',
            // Agile module
            'view epics',
            'create epics',
            'edit epics',
            'delete epics',
            'view user stories',
            'create user stories',
            'edit user stories',
            'delete user stories',
            'complete user stories',
            'move stories to sprint',
            'view story tasks',
            'create story tasks',
            'edit story tasks',
            'delete story tasks',
            'view acceptance criteria',
            'create acceptance criteria',
            'edit acceptance criteria',
            'delete acceptance criteria',
            'validate acceptance criteria',
            'view test scenarios',
            'create test scenarios',
            'edit test scenarios',
            'delete test scenarios',
            'execute test scenarios',
            'start sprints',
            'close sprints',
            // Stocks
            'view stocks',
            'manage stocks',
            'approve stock requests',
            // Groups
            'view groups',
            'create groups',
            'edit groups',
            'delete groups',
            'manage group members',
            // Visitors
            'view visitors',
            'create visitors',
            'edit visitors',
            'delete visitors',
            'manage integration pathways',
            'view integration scores',
            'respond integration suggestions',
            // Users & Departments
            'view users',
            'edit users',
            'view departments',
            'create departments',
            'edit departments',
            'manage departments',
            'assign department members',
            'access all departments',
            'view department statistics',
            // Books & Library
            'view books',
            'rent books',
            'create books',
            'edit books',
            'delete books',
            'manage library',
            'approve rentals',
            // Videos
            'view videos',
            'upload videos',
            'edit videos',
            'delete videos',
            // Trainings
            'view trainings',
            'create trainings',
            'edit trainings',
            'delete trainings',
            'manage trainings',
            'manage training access',
            // Quiz & Evaluations
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'manage quizzes',
            'take quizzes',
            'grade quizzes',
            'view evaluations',
            'create evaluations',
            'edit evaluations',
            'delete evaluations',
            'manage evaluations',
            // Messages
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            // Chat
            'use chat',
            'moderate chat',
            // Contacts
            'view contacts',
            'manage contacts',
            // Hero Slides
            'view hero slides',
            'manage hero slides',
            // Homepage Sections
            'manage homepage sections',
            // Dashboard Access
            'access student dashboard',
            'access teacher dashboard',
            // Reports
            'view reports',
            'generate reports',
            // Workflows
            'view workflows',
            'create workflows',
            'edit workflows',
            'delete workflows',
            'manage workflows',
            'execute workflows',
            // Forms
            'view forms',
            'create forms',
            'edit forms',
            'delete forms',
            'manage forms',
            'submit forms',
            'process form submissions',
            // Needs
            'view needs',
            'create needs',
            'edit needs',
            'delete needs',
            'approve needs',
            'manage needs',
            // Employees
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage employees',
            // Stars (Volunteers)
            'view stars',
            'create stars',
            'edit stars',
            'delete stars',
            'manage stars',
            // Availabilities
            'view availabilities',
            'create availabilities',
            'edit availabilities',
            'delete availabilities',
            'manage availabilities',
            // Care Service Availability
            'manage care service availability',
            // Accounting
            'view accounting',
            'manage accounting',
        ]);

        // Writer - Content management focused
        $writer = Role::firstOrCreate(['name' => 'writer']);
        $writer->syncPermissions([
            // Articles
            'view articles',
            'create articles',
            'edit articles',
            'delete articles',
            'publish articles',
            // Videos
            'view videos',
            'upload videos',
            'edit videos',
            'delete videos',
            // Hero Slides
            'view hero slides',
            'manage hero slides',
            // Books
            'view books',
            'create books',
            'edit books',
            // Appointments
            'view appointments',
            'create appointments',
            // Care Service
            'view care service',
            'create care service',
            'edit care service',
            'manage care service',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Basic viewing
            'view events',
            'attend events',
            'view departments',
            'view groups',
        ]);

        // Project Manager - Projects, tasks, and team management
        $projectManager = Role::firstOrCreate(['name' => 'project-manager']);
        $projectManager->syncPermissions([
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',
            'create program steps',
            // Projects
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage projects',
            // Agile module (all except 'validate acceptance criteria' which is Product Owner's)
            'view epics',
            'create epics',
            'edit epics',
            'delete epics',
            'view user stories',
            'create user stories',
            'edit user stories',
            'delete user stories',
            'complete user stories',
            'move stories to sprint',
            'view story tasks',
            'create story tasks',
            'edit story tasks',
            'delete story tasks',
            'view acceptance criteria',
            'create acceptance criteria',
            'edit acceptance criteria',
            'delete acceptance criteria',
            'view test scenarios',
            'create test scenarios',
            'edit test scenarios',
            'delete test scenarios',
            'execute test scenarios',
            'start sprints',
            'close sprints',
            // Groups & Teams
            'view groups',
            'create groups',
            'edit groups',
            'manage group members',
            // Stocks
            'view stocks',
            'manage stocks',
            // Users & Departments
            'view users',
            'view departments',
            // Events
            'view events',
            'create events',
            'edit events',
            'attend events',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'manage appointment participants',
            // Care Service
            'view care service',
            'create care service',
            'edit care service',
            'delete care service',
            'manage care service',
            // Articles
            'view articles',
            'create articles',
            'edit articles',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Reports
            'view reports',
            'generate reports',
            // Others
            'view books',
            'view videos',
            // Workflows
            'view workflows',
            'create workflows',
            'edit workflows',
            'manage workflows',
            'execute workflows',
            // Forms
            'view forms',
            'create forms',
            'edit forms',
            'manage forms',
            'submit forms',
            'process form submissions',
            // Needs
            'view needs',
            'create needs',
            'edit needs',
            'approve needs',
            'manage needs',
        ]);

        // Event Manager - Events and related activities
        $eventManager = Role::firstOrCreate(['name' => 'event-manager']);
        $eventManager->syncPermissions([
            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            'attend events',
            'manage event participants',
            // Event Tabs
            'view event gallery',
            'manage tickets',
            'view registrations',
            'manage registrations',
            'checkin events',
            'view event analytics',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'manage appointment participants',
            // Groups
            'view groups',
            'manage group members',
            // Users & Departments
            'view users',
            'view departments',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Articles
            'view articles',
            'create articles',
            // Others
            'view books',
            'view videos',
            'view stocks',
        ]);

        // Library Manager - Library and books management
        $libraryManager = Role::firstOrCreate(['name' => 'library-manager']);
        $libraryManager->syncPermissions([
            // Books & Library
            'view books',
            'rent books',
            'create books',
            'edit books',
            'delete books',
            'manage library',
            'approve rentals',
            // Articles for library announcements
            'view articles',
            'create articles',
            'edit articles',
            // Appointments
            'view appointments',
            'create appointments',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Basic viewing
            'view users',
            'view departments',
            'view events',
            'attend events',
            // Reports
            'view reports',
        ]);

        // Group Leader - Manage assigned group
        $groupLeader = Role::firstOrCreate(['name' => 'group-leader']);
        $groupLeader->syncPermissions([
            // Groups
            'view groups',
            'edit groups',
            'manage group members',
            // Events
            'view events',
            'create events',
            'attend events',
            // Appointments
            'view appointments',
            'create appointments',
            // Tasks
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Articles
            'view articles',
            'create articles',
            // Others
            'view books',
            'rent books',
            'view videos',
            'view users',
            'view departments',
            // Visitors
            'view visitors',
            'create visitors',
            'edit visitors',
            'view integration scores',
            'respond integration suggestions',
        ]);

        // Department Leader - Manage department
        $departmentLeader = Role::firstOrCreate(['name' => 'department-leader']);
        $departmentLeader->syncPermissions([
            // Departments
            'view departments',
            'edit departments',
            'manage departments',
            'assign department members',
            'access all departments',
            'view department statistics',
            // Users
            'view users',
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'view programs',
            'create programs',
            'edit programs',
            'create program steps',
            // Events
            'view events',
            'create events',
            'edit events',
            'attend events',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Articles
            'view articles',
            'create articles',
            'edit articles',
            // Others
            'view books',
            'rent books',
            'view videos',
            'view groups',
            'view stocks',
            // Reports
            'view reports',
            // Workflows
            'view workflows',
            'create workflows',
            'edit workflows',
            'manage workflows',
            'execute workflows',
            // Forms
            'view forms',
            'create forms',
            'edit forms',
            'manage forms',
            'submit forms',
            'process form submissions',
            // Needs
            'view needs',
            'create needs',
            'edit needs',
            'approve needs',
            'manage needs',
            // Visitors
            'view visitors',
            'create visitors',
            'edit visitors',
            'view integration scores',
            'respond integration suggestions',
        ]);

        // Impact Family Leader - Manage impact families and related activities
        $impactFamilyLeader = Role::firstOrCreate(['name' => 'impact-family-leader']);
        $impactFamilyLeader->syncPermissions([
            // Groups (Impact Families)
            'view groups',
            'edit groups',
            'manage group members',
            // Events
            'view events',
            'create events',
            'edit events',
            'attend events',
            'manage event participants',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'view programs',
            'view programs',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Articles
            'view articles',
            'create articles',
            'edit articles',
            // Users & Departments
            'view users',
            'view departments',
            // Others
            'view books',
            'rent books',
            'view videos',
            'view stocks',
            // Reports
            'view reports',
        ]);

        // Member - Basic access for regular users
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            // Viewing permissions
            'view articles',
            'view trainings',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view groups',
            // Agile module — read-only
            'view epics',
            'view user stories',
            'view story tasks',
            'view acceptance criteria',
            'view test scenarios',
            // Books
            'rent books',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Appointments
            'view appointments',
            'create appointments',
            // Care Service
            'view care service',
            'create care service',
            // Needs (basic submission)
            'view needs',
            'create needs',
            'submit forms',
            // Availabilities (own availabilities)
            'view availabilities',
            'create availabilities',
            'edit availabilities',
            'delete availabilities',
        ]);

        // Employee - Staff member with extended access
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->syncPermissions([
            // Viewing permissions
            'view articles',
            'view trainings',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view groups',
            'view departments',
            'view users',
            // Books
            'rent books',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            // Care Service
            'view care service',
            'create care service',
            // Tasks
            'view tasks',
            'create tasks',
            'edit tasks',
            // Projects
            'view projects',
            // Needs
            'view needs',
            'create needs',
            'edit needs',
            'submit forms',
            // Reports
            'view reports',
            // Availabilities
            'view availabilities',
            'create availabilities',
            'edit availabilities',
            'delete availabilities',
        ]);

        // Star - Volunteer with specific access
        $star = Role::firstOrCreate(['name' => 'star']);
        $star->syncPermissions([
            // Viewing permissions
            'view articles',
            'view trainings',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view groups',
            'view departments',
            // Books
            'rent books',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Appointments
            'view appointments',
            'create appointments',
            // Care Service
            'view care service',
            'create care service',
            // Tasks (can view and work on assigned tasks)
            'view tasks',
            // Needs (can submit needs)
            'view needs',
            'create needs',
            'submit forms',
            // Availabilities (own availabilities)
            'view availabilities',
            'create availabilities',
            'edit availabilities',
            'delete availabilities',
        ]);

        // Student - Access to student dashboard and training materials
        $student = Role::firstOrCreate(['name' => 'student']);
        $student->syncPermissions([
            // Viewing permissions
            'view articles',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view departments',
            // Appointments
            'view appointments',
            // Books
            'rent books',
            // Trainings
            'view trainings',
            // Quiz & Evaluations
            'take quizzes',
            // Messages & Chat
            'view messages',
            'create messages',
            'delete messages',
            'use chat',
            // Dashboard Access
            'access student dashboard',
        ]);

        // Teacher - Access to teacher dashboard and training class management
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $teacher->syncPermissions([
            // Viewing permissions
            'view articles',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view users',
            'view departments',
            // Appointments
            'view appointments',
            'create appointments',
            // Tasks & Programs
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            // Books
            'view books',
            'rent books',
            // Trainings
            'view trainings',
            'create trainings',
            'edit trainings',
            'manage trainings',
            'manage training access',
            // Quiz & Evaluations
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'manage quizzes',
            'take quizzes',
            'grade quizzes',
            'view evaluations',
            'create evaluations',
            'edit evaluations',
            'delete evaluations',
            'manage evaluations',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Dashboard Access
            'access teacher dashboard',
            // Reports
            'view reports',
        ]);

        // Pastor - Care service management
        $pastor = Role::firstOrCreate(['name' => 'pastor']);
        $pastor->syncPermissions([
            // Viewing permissions
            'view articles',
            'view events',
            'attend events',
            'view videos',
            'view books',
            'view users',
            'view departments',
            // Appointments
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'manage appointment participants',
            // Care Service
            'view care service',
            'create care service',
            'edit care service',
            'delete care service',
            'manage care service',
            // Care Service Availability (for scheduling care service appointments)
            'manage care service availability',
            // Messages & Chat
            'view messages',
            'create messages',
            'edit messages',
            'delete messages',
            'use chat',
            // Groups
            'view groups',
            'manage group members',
            // Reports
            'view reports',
        ]);

        // Super Admin - All permissions (kebab-case standardized)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Care Service Agent - Care service focused role
        $careServiceAgent = Role::firstOrCreate(['name' => 'care-service-agent']);
        $careServiceAgent->syncPermissions([
            'view care service',
            'create care service',
            'edit care service',
            'select pastor for care service',
            'view care service dashboard',
            'view appointments',
            'create appointments',
        ]);

        // Accountant - Accounting and financial management
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->syncPermissions([
            // Accounting
            'view accounting',
            'manage accounting',
            // Reports
            'view reports',
            'generate reports',
            // Basic viewing
            'view articles',
            'view events',
            'attend events',
            'view departments',
            'view users',
            // Messages & Chat
            'view messages',
            'create messages',
            'use chat',
            // Appointments
            'view appointments',
            'create appointments',
        ]);

        // Product Owner - Agile module: manages epics/stories, validates acceptance criteria
        $productOwner = Role::firstOrCreate(['name' => 'product-owner']);
        $productOwner->syncPermissions([
            // Projects (read-only — POs don't manage project admin)
            'view projects',
            // Epics / Stories — full ownership
            'view epics',
            'create epics',
            'edit epics',
            'delete epics',
            'view user stories',
            'create user stories',
            'edit user stories',
            'delete user stories',
            'complete user stories',
            'move stories to sprint',
            // Acceptance criteria — creation and the key "validate" permission
            'view acceptance criteria',
            'create acceptance criteria',
            'edit acceptance criteria',
            'delete acceptance criteria',
            'validate acceptance criteria',
            // Test scenarios — read-only (QA / dev team authors them)
            'view test scenarios',
            // Story tasks — read-only (tech lead decomposes)
            'view story tasks',
            // Sprints — read-only (scrum master runs the lifecycle)
            'view projects',
        ]);
    }
}
