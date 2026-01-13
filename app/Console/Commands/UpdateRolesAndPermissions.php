<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\CacheService;

class UpdateRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     * # Prévisualiser les changements 
     * php artisan permissions:sync --dry-run
     * Appliquer les changements
     * php artisan permissions:sync --force
     *
     * @var string
     */
    protected $signature = 'permissions:update
                            {--force : Force update without confirmation}
                            {--dry-run : Show what would be updated without making changes}
                            {--reset-super-admin : Reset SuperAdmin to have all permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update roles and permissions for production deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting roles and permissions update...');

        // Check if dry run mode
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $resetSuperAdmin = $this->option('reset-super-admin');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Clear permission cache first
        if (!$dryRun) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->info('✅ Permission cache cleared');
        }

        try {
            // 1. Create missing permissions
            $this->createMissingPermissions($dryRun);

            // 2. Create missing roles
            $this->createMissingRoles($dryRun);

            // 3. Update role permissions
            $this->updateRolePermissions($dryRun);

            // 4. Reset SuperAdmin if requested
            if ($resetSuperAdmin) {
                $this->resetSuperAdminPermissions($dryRun);
            }

            // 5. Clear application caches
            if (!$dryRun) {
                $this->clearApplicationCaches();
            }

            $this->info('🎉 Roles and permissions update completed successfully!');

            if (!$dryRun) {
                $this->displaySummary();
            }

        } catch (\Exception $e) {
            $this->error('❌ Error during update: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Create missing permissions based on the seeder
     */
    private function createMissingPermissions($dryRun = false)
    {
        $this->info('📋 Creating missing permissions...');

        $permissions = [
            // Articles
            'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
            // Events
            'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
            // Appointments
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage appointment participants',
            // Pastoral Care
            'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
            // Pastor specific (from PastorRoleSeeder)
            'manage pastor availability', 'manage pastoral appointments',
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

        $created = 0;
        foreach ($permissions as $permission) {
            $exists = Permission::where('name', $permission)->exists();

            if (!$exists) {
                if ($dryRun) {
                    $this->line("  Would create: {$permission}");
                } else {
                    Permission::create(['name' => $permission]);
                    $this->line("  ✅ Created: {$permission}");
                }
                $created++;
            }
        }

        if ($created === 0) {
            $this->info('  ✅ All permissions already exist');
        } else {
            $this->info("  📊 {$created} permission(s) " . ($dryRun ? 'would be created' : 'created'));
        }
    }

    /**
     * Create missing roles
     */
    private function createMissingRoles($dryRun = false)
    {
        $this->info('👥 Creating missing roles...');

        $roles = [
            'admin', 'writer', 'project-manager', 'event-manager', 'library-manager',
            'group-leader', 'department-leader', 'impact-family-leader', 'member',
            'student', 'teacher', 'pastor',
            // PascalCase aliases
            'SuperAdmin', 'Admin', 'Member', 'Student', 'Teacher', 'ProjectManager', 'EventManager', 'Editor'
        ];

        $created = 0;
        foreach ($roles as $roleName) {
            $exists = Role::where('name', $roleName)->exists();

            if (!$exists) {
                if ($dryRun) {
                    $this->line("  Would create role: {$roleName}");
                } else {
                    Role::create(['name' => $roleName]);
                    $this->line("  ✅ Created role: {$roleName}");
                }
                $created++;
            }
        }

        if ($created === 0) {
            $this->info('  ✅ All roles already exist');
        } else {
            $this->info("  📊 {$created} role(s) " . ($dryRun ? 'would be created' : 'created'));
        }
    }

    /**
     * Update role permissions
     */
    private function updateRolePermissions($dryRun = false)
    {
        $this->info('🔧 Updating role permissions...');

        // Define role permission mappings (simplified version of RolesAndPermissionsSeeder)
        $rolePermissions = [
            'admin' => [
                'view articles', 'create articles', 'edit articles', 'delete articles', 'publish articles',
                'view events', 'create events', 'edit events', 'delete events', 'attend events', 'manage event participants',
                'view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage appointment participants',
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
                'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
                'view programs', 'create programs', 'edit programs', 'delete programs',
                'view stocks', 'manage stocks', 'approve stock requests',
                'view groups', 'create groups', 'edit groups', 'delete groups', 'manage group members',
                'view users', 'edit users',
                'view departments', 'create departments', 'edit departments', 'manage departments', 'assign department members',
                'view books', 'rent books', 'create books', 'edit books', 'delete books', 'manage library', 'approve rentals',
                'view videos', 'upload videos', 'edit videos', 'delete videos',
                'view trainings', 'create trainings', 'edit trainings', 'delete trainings', 'manage trainings',
                'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'manage quizzes', 'take quizzes', 'grade quizzes',
                'view evaluations', 'create evaluations', 'edit evaluations', 'delete evaluations', 'manage evaluations',
                'view messages', 'create messages', 'edit messages', 'delete messages',
                'use chat', 'moderate chat',
                'view contacts', 'manage contacts',
                'view hero slides', 'manage hero slides',
                'view reports', 'generate reports',
            ],
            'pastor' => [
                'view pastoral care', 'create pastoral care', 'edit pastoral care', 'delete pastoral care', 'manage pastoral care',
                'manage pastor availability', 'manage pastoral appointments',
                'view appointments', 'create appointments', 'edit appointments',
                'view events', 'attend events',
                'view articles', 'use chat',
                'view users', 'view departments',
            ],
        ];

        $updated = 0;
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $currentPermissions = $role->permissions->pluck('name')->toArray();
                $permissionsToAdd = array_diff($permissions, $currentPermissions);

                if (!empty($permissionsToAdd)) {
                    if ($dryRun) {
                        $this->line("  Would update {$roleName}: " . implode(', ', $permissionsToAdd));
                    } else {
                        $role->givePermissionTo($permissionsToAdd);
                        $this->line("  ✅ Updated {$roleName}: " . implode(', ', $permissionsToAdd));
                    }
                    $updated++;
                }
            }
        }

        if ($updated === 0) {
            $this->info('  ✅ All role permissions are up to date');
        } else {
            $this->info("  📊 {$updated} role(s) " . ($dryRun ? 'would be updated' : 'updated'));
        }
    }

    /**
     * Reset SuperAdmin to have all permissions
     */
    private function resetSuperAdminPermissions($dryRun = false)
    {
        $this->info('🔒 Resetting SuperAdmin permissions...');

        $superAdmin = Role::where('name', 'SuperAdmin')->first();

        if (!$superAdmin) {
            if ($dryRun) {
                $this->line('  Would create SuperAdmin role');
            } else {
                $superAdmin = Role::create(['name' => 'SuperAdmin']);
                $this->line('  ✅ Created SuperAdmin role');
            }
        }

        if ($superAdmin || !$dryRun) {
            $allPermissions = Permission::all();
            $totalPermissions = $allPermissions->count();

            if ($dryRun) {
                $this->line("  Would assign all {$totalPermissions} permissions to SuperAdmin");
            } else {
                if (!$superAdmin) {
                    $superAdmin = Role::where('name', 'SuperAdmin')->first();
                }
                $superAdmin->syncPermissions($allPermissions);
                $this->line("  ✅ SuperAdmin now has all {$totalPermissions} permissions");
            }
        }
    }

    /**
     * Clear application caches
     */
    private function clearApplicationCaches()
    {
        $this->info('🧹 Clearing application caches...');

        // Clear Laravel caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');

        // Clear custom caches
        CacheService::forgetPattern('user_management.*');
        CacheService::forgetPattern('permissions.*');
        CacheService::forgetPattern('roles.*');
        CacheService::forgetByTag('permissions');

        $this->info('  ✅ All caches cleared');
    }

    /**
     * Display summary of current state
     */
    private function displaySummary()
    {
        $this->info('📊 Current Summary:');

        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $superAdmin = Role::where('name', 'SuperAdmin')->with('permissions')->first();

        $this->table([
            'Metric', 'Count'
        ], [
            ['Total Permissions', $totalPermissions],
            ['Total Roles', $totalRoles],
            ['SuperAdmin Permissions', $superAdmin ? $superAdmin->permissions->count() : 0],
            ['SuperAdmin Complete?', ($superAdmin && $superAdmin->permissions->count() === $totalPermissions) ? '✅ Yes' : '❌ No'],
        ]);
    }
}
