<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync
                            {--dry-run : Show what would be created without actually creating}
                            {--force : Force sync without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize roles and permissions from the master configuration. Only adds new roles/permissions without modifying existing ones.';

    /**
     * All permissions defined in the application.
     */
    protected function getAllPermissions(): array
    {
        return [
            // Articles
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            // Events
            'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
            // Appointments
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage appointment participants',
            // Pastoral Care
            'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
            'select pastor for pastoral care', 'manage pastor availability', 'manage pastoral appointments',
            // Tasks & Programs
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view programs', 'create programs', 'edit programs', 'delete programs', 'create program steps',
            // Projects
            'view projects', 'create projects', 'edit projects', 'delete projects', 'manage projects',
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
            'teach',
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
            // Dashboard Access
            'access student dashboard', 'access teacher dashboard',
            // System
            'view system settings', 'manage system settings',
            'view reports', 'generate reports',
            // Workflows
            'view workflows', 'create workflows', 'edit workflows', 'delete workflows', 'manage workflows', 'execute workflows',
            // Forms
            'view forms', 'create forms', 'edit forms', 'delete forms', 'manage forms', 'submit forms',
            // Needs (Department Needs)
            'view needs', 'create needs', 'edit needs', 'delete needs', 'approve needs', 'manage needs',
        ];
    }

    /**
     * All roles with their permissions.
     */
    protected function getRolesWithPermissions(): array
    {
        return [
            'admin' => [
                // Articles
                'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
                // Events
                'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
                // Appointments
                'view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage appointment participants',
                // Pastoral Care
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
                'select pastor for pastoral care',
                // Tasks & Programs
                'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
                'view programs', 'create programs', 'edit programs', 'delete programs', 'create program steps',
                // Projects
                'view projects', 'create projects', 'edit projects', 'delete projects', 'manage projects',
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
                // Dashboard Access
                'access student dashboard', 'access teacher dashboard',
                // Reports
                'view reports', 'generate reports',
                // Workflows
                'view workflows', 'create workflows', 'edit workflows', 'delete workflows', 'manage workflows', 'execute workflows',
                // Forms
                'view forms', 'create forms', 'edit forms', 'delete forms', 'manage forms', 'submit forms',
                // Needs
                'view needs', 'create needs', 'edit needs', 'delete needs', 'approve needs', 'manage needs',
            ],

            'writer' => [
                'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
                'view videos', 'upload videos', 'edit videos', 'delete videos',
                'view hero slides', 'manage hero slides',
                'view books', 'create books', 'edit books',
                'view appointments', 'create appointments',
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'manage pastoral care',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view events', 'attend events', 'view departments', 'view groups',
            ],

            'project-manager' => [
                'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
                'view programs', 'create programs', 'edit programs', 'delete programs', 'create program steps',
                'view projects', 'create projects', 'edit projects', 'delete projects', 'manage projects',
                'view groups', 'create groups', 'edit groups', 'manage group members',
                'view stocks', 'manage stocks',
                'view users', 'view departments',
                'view events', 'create events', 'edit events', 'attend events',
                'view appointments', 'create appointments', 'edit appointments', 'manage appointment participants',
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
                'select pastor for pastoral care',
                'view articles', 'create articles', 'edit articles',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view reports', 'generate reports',
                'view books', 'view videos',
                'view workflows', 'create workflows', 'edit workflows', 'manage workflows', 'execute workflows',
                'view forms', 'create forms', 'edit forms', 'manage forms', 'submit forms',
                'view needs', 'create needs', 'edit needs', 'approve needs', 'manage needs',
            ],

            'event-manager' => [
                'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
                'view appointments', 'create appointments', 'edit appointments', 'manage appointment participants',
                'view groups', 'manage group members',
                'view users', 'view departments',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view articles', 'create articles',
                'view books', 'view videos', 'view stocks',
                'view pastoral care', 'create pastoral care', 'edit pastoral care',
            ],

            'library-manager' => [
                'view books', 'rent books', 'create books', 'edit books', 'delete books', 'manage library', 'approve rentals',
                'view articles', 'create articles', 'edit articles',
                'view appointments', 'create appointments',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view users', 'view departments', 'view events', 'attend events',
                'view reports',
            ],

            'group-leader' => [
                'view groups', 'edit groups', 'manage group members',
                'view events', 'create events', 'attend events',
                'view appointments', 'create appointments',
                'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view articles', 'create articles',
                'view books', 'rent books', 'view videos', 'view users', 'view departments',
            ],

            'department-leader' => [
                'view departments', 'edit departments', 'manage departments', 'assign department members',
                'view users',
                'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
                'view programs', 'create programs', 'edit programs', 'create program steps',
                'view events', 'create events', 'edit events', 'attend events',
                'view appointments', 'create appointments', 'edit appointments',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view articles', 'create articles', 'edit articles',
                'view books', 'rent books', 'view videos', 'view groups', 'view stocks',
                'view reports',
                'view workflows', 'create workflows', 'edit workflows', 'manage workflows', 'execute workflows',
                'view forms', 'create forms', 'edit forms', 'manage forms', 'submit forms',
                'view needs', 'create needs', 'edit needs', 'approve needs', 'manage needs',
            ],

            'impact-family-leader' => [
                'view groups', 'edit groups', 'manage group members',
                'view events', 'create events', 'edit events', 'attend events', 'manage event participants',
                'view appointments', 'create appointments', 'edit appointments',
                'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
                'view programs',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view articles', 'create articles', 'edit articles',
                'view users', 'view departments',
                'view books', 'rent books', 'view videos', 'view stocks',
                'view reports',
            ],

            'member' => [
                'view articles',
                'view trainings',
                'view events', 'attend events', 'view videos', 'view books', 'view groups',
                'rent books',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view appointments', 'create appointments',
                'view pastoral care', 'create pastoral care',
                'view needs', 'create needs', 'submit forms',
            ],

            'student' => [
                'view articles', 'view events', 'attend events', 'view videos', 'view books', 'view departments',
                'view appointments',
                'rent books',
                'view trainings',
                'take quizzes',
                'view messages', 'create messages', 'delete messages',
                'use chat',
                'access student dashboard',
            ],

            'teacher' => [
                'view articles', 'view events', 'attend events', 'view videos', 'view books', 'view users', 'view departments',
                'view appointments', 'create appointments',
                'view tasks', 'create tasks', 'edit tasks', 'assign tasks',
                'rent books',
                'view trainings', 'create trainings', 'edit trainings', 'manage trainings',
                'teach',
                'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'manage quizzes', 'take quizzes', 'grade quizzes',
                'view evaluations', 'create evaluations', 'edit evaluations', 'delete evaluations', 'manage evaluations',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'access teacher dashboard',
                'view reports',
            ],

            'pastor' => [
                'view articles', 'view events', 'attend events', 'view videos', 'view books', 'view users', 'view departments',
                'view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage appointment participants',
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
                'select pastor for pastoral care', 'manage pastor availability', 'manage pastoral appointments',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat',
                'view groups', 'manage group members',
                'view reports',
            ],
        ];
    }

    /**
     * Backward compatibility aliases for PascalCase role names.
     */
    protected function getRoleAliases(): array
    {
        return [
            'SuperAdmin' => '*', // All permissions
            'Admin' => 'admin',
            'Member' => 'member',
            'Student' => 'student',
            'Teacher' => 'teacher',
            'ProjectManager' => 'project-manager',
            'EventManager' => 'event-manager',
            'Editor' => 'writer',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        // Safety check for production
        if (app()->environment('production') && !$isDryRun && !$isForce) {
            if (!$this->confirm('You are about to modify roles and permissions in PRODUCTION. Are you sure?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Clear permission cache
        if (!$isDryRun) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        // Sync permissions
        $permissionsResult = $this->syncPermissions($isDryRun);

        // Sync roles
        $rolesResult = $this->syncRoles($isDryRun);

        // Sync role permissions
        $rolePermissionsResult = $this->syncRolePermissions($isDryRun);

        // Sync role aliases
        $aliasesResult = $this->syncRoleAliases($isDryRun);

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Category', 'Created', 'Already Existed', 'Updated'],
            [
                ['Permissions', $permissionsResult['created'], $permissionsResult['existed'], '-'],
                ['Roles', $rolesResult['created'], $rolesResult['existed'], '-'],
                ['Role Permissions', '-', '-', $rolePermissionsResult['updated']],
                ['Role Aliases', $aliasesResult['created'], $aliasesResult['existed'], $aliasesResult['updated']],
            ]
        );

        if (!$isDryRun) {
            // Clear cache after sync
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->newLine();
            $this->info('✅ Roles and permissions synchronized successfully!');
        } else {
            $this->newLine();
            $this->info('ℹ️  Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Sync permissions from master list.
     */
    protected function syncPermissions(bool $isDryRun): array
    {
        $this->info('🔐 Synchronizing permissions...');

        $allPermissions = $this->getAllPermissions();
        $existingPermissions = Permission::pluck('name')->toArray();

        $created = 0;
        $existed = 0;

        foreach ($allPermissions as $permission) {
            if (in_array($permission, $existingPermissions)) {
                $existed++;
            } else {
                $created++;
                if (!$isDryRun) {
                    Permission::create(['name' => $permission]);
                }
                $this->line("   + Created permission: <info>{$permission}</info>");
            }
        }

        if ($created === 0) {
            $this->line('   No new permissions to create.');
        }

        return ['created' => $created, 'existed' => $existed];
    }

    /**
     * Sync roles from master list.
     */
    protected function syncRoles(bool $isDryRun): array
    {
        $this->info('👥 Synchronizing roles...');

        $allRoles = array_keys($this->getRolesWithPermissions());
        $aliasRoles = array_keys($this->getRoleAliases());
        $allRoles = array_merge($allRoles, $aliasRoles);

        $existingRoles = Role::pluck('name')->toArray();

        $created = 0;
        $existed = 0;

        foreach ($allRoles as $roleName) {
            if (in_array($roleName, $existingRoles)) {
                $existed++;
            } else {
                $created++;
                if (!$isDryRun) {
                    Role::firstOrCreate(['name' => $roleName]);
                }
                $this->line("   + Created role: <info>{$roleName}</info>");
            }
        }

        if ($created === 0) {
            $this->line('   No new roles to create.');
        }

        return ['created' => $created, 'existed' => $existed];
    }

    /**
     * Sync role permissions (add missing permissions to roles).
     */
    protected function syncRolePermissions(bool $isDryRun): array
    {
        $this->info('🔗 Synchronizing role permissions...');

        $rolesWithPermissions = $this->getRolesWithPermissions();
        $updated = 0;

        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $this->warn("   ⚠️  Role '{$roleName}' not found, skipping...");
                continue;
            }

            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $missingPermissions = array_diff($permissions, $currentPermissions);

            if (count($missingPermissions) > 0) {
                $updated++;
                if (!$isDryRun) {
                    $role->givePermissionTo($missingPermissions);
                }
                $this->line("   ↳ <info>{$roleName}</info>: Added " . count($missingPermissions) . ' permission(s)');
                foreach ($missingPermissions as $perm) {
                    $this->line("      + {$perm}");
                }
            }
        }

        if ($updated === 0) {
            $this->line('   All role permissions are up to date.');
        }

        return ['updated' => $updated];
    }

    /**
     * Sync role aliases (PascalCase to lowercase mappings).
     */
    protected function syncRoleAliases(bool $isDryRun): array
    {
        $this->info('🔄 Synchronizing role aliases...');

        $aliases = $this->getRoleAliases();
        $existingRoles = Role::pluck('name')->toArray();
        $created = 0;
        $existed = 0;
        $updated = 0;

        foreach ($aliases as $aliasName => $sourceRoleName) {
            $wasCreated = false;

            if (!in_array($aliasName, $existingRoles)) {
                $created++;
                $wasCreated = true;
                if (!$isDryRun) {
                    Role::firstOrCreate(['name' => $aliasName]);
                }
                $this->line("   + Created alias role: <info>{$aliasName}</info>");
            } else {
                $existed++;
            }

            // Re-fetch the role to ensure we have it
            $aliasRole = !$isDryRun ? Role::where('name', $aliasName)->first() : null;

            // Sync permissions
            if ($sourceRoleName === '*') {
                // SuperAdmin gets all permissions
                $allPermissions = Permission::all();
                if (!$isDryRun && $aliasRole) {
                    $currentCount = $aliasRole->permissions->count();
                    $aliasRole->syncPermissions($allPermissions);
                    if ($currentCount !== $allPermissions->count()) {
                        $updated++;
                        $this->line("   ↳ <info>{$aliasName}</info>: Synced all " . $allPermissions->count() . ' permissions');
                    }
                }
            } else {
                $sourceRole = !$isDryRun ? Role::where('name', $sourceRoleName)->first() : null;
                if ($sourceRole && $aliasRole) {
                    $sourcePermissions = $sourceRole->permissions->pluck('name')->toArray();
                    $currentPermissions = $aliasRole->permissions->pluck('name')->toArray();

                    if (count(array_diff($sourcePermissions, $currentPermissions)) > 0) {
                        $updated++;
                        $aliasRole->syncPermissions($sourceRole->permissions);
                        $this->line("   ↳ <info>{$aliasName}</info>: Synced permissions from {$sourceRoleName}");
                    }
                }
            }
        }

        if ($created === 0 && $updated === 0) {
            $this->line('   All role aliases are up to date.');
        }

        return ['created' => $created, 'existed' => $existed, 'updated' => $updated];
    }
}
