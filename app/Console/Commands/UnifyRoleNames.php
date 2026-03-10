<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UnifyRoleNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:unify
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unify role names to kebab-case format and remove duplicate roles';

    /**
     * Role mappings: old name => new standardized name
     * null means the role should be deleted (duplicate of another)
     */
    private array $roleMappings = [
        // PascalCase to kebab-case conversions
        'SuperAdmin' => 'super-admin',
        'Admin' => 'admin',           // Keep as lowercase (already exists)
        'Member' => 'member',         // Keep as lowercase (already exists)
        'Student' => 'student',       // Keep as lowercase (already exists)
        'Teacher' => 'teacher',       // Keep as lowercase (already exists)
        'ProjectManager' => 'project-manager',
        'EventManager' => 'event-manager',
        'Editor' => 'writer',         // Map to existing 'writer' role
        'Employee' => 'employee',     // Keep as lowercase (already exists)
        'Star' => 'star',             // Keep as lowercase (already exists)

        // snake_case to kebab-case conversions
        'mlr_agent' => 'mlr-agent',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔧 Role Name Unification Tool');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Step 1: Show current state
        $this->showCurrentRoles();

        // Step 2: Show planned changes
        $changes = $this->analyzePlannedChanges();

        if (empty($changes['migrations']) && empty($changes['deletions'])) {
            $this->info('✅ All role names are already standardized. Nothing to do.');

            return Command::SUCCESS;
        }

        // Step 3: Confirm changes
        if (! $dryRun && !$force && ! $this->confirm('Do you want to proceed with these changes?')) {
            $this->warn('Operation cancelled.');
            return Command::FAILURE;
        }

        // Step 4: Execute migrations
        if (! $dryRun) {
            $this->executeChanges($changes);
        }

        // Step 5: Summary
        $this->showSummary($changes, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Show current roles in the database
     */
    private function showCurrentRoles(): void
    {
        $this->info('📋 Current roles in database:');

        $roles = Role::withCount('users')->orderBy('name')->get();

        $tableData = $roles->map(function ($role): array {
            $naming = $this->detectNamingConvention($role->name);
            $status = $this->getRoleStatus($role->name);

            return [
                $role->name,
                $role->users_count,
                $naming,
                $status,
            ];
        })->toArray();

        $this->table(
            ['Role Name', 'Users', 'Naming', 'Status'],
            $tableData
        );
        $this->newLine();
    }

    /**
     * Detect the naming convention of a role
     */
    private function detectNamingConvention(string $name): string
    {
        if (preg_match('/^[a-z]+(-[a-z]+)*$/', $name)) {
            return 'kebab-case ✅';
        }
        if (preg_match('/^[a-z]+$/', $name)) {
            return 'lowercase ✅';
        }
        if (preg_match('/^[A-Z][a-zA-Z]*$/', $name)) {
            return 'PascalCase ⚠️';
        }
        if (preg_match('/^[a-z]+(_[a-z]+)+$/', $name)) {
            return 'snake_case ⚠️';
        }

        return 'unknown';
    }

    /**
     * Get the status of a role (to keep, rename, or delete)
     */
    private function getRoleStatus(string $name): string
    {
        if (isset($this->roleMappings[$name])) {
            $target = $this->roleMappings[$name];
            if ($target === $name) {
                return '✅ Keep';
            }

            return "➡️ Migrate to '{$target}'";
        }

        return '✅ Keep';
    }

    /**
     * Analyze what changes need to be made
     *
     * @return array{migrations: array<string, array{from: string, to: string, users: int}>, deletions: array<string, int>}
     */
    private function analyzePlannedChanges(): array
    {
        $migrations = [];
        $deletions = [];

        $this->info('📝 Planned changes:');
        $this->newLine();

        foreach ($this->roleMappings as $oldName => $newName) {
            $oldRole = Role::where('name', $oldName)->withCount('users')->first();

            if (! $oldRole) {
                continue;
            }

            // Check if target role already exists
            $newRole = Role::where('name', $newName)->first();

            if ($oldName === $newName) {
                // Role name is already correct
                continue;
            }

            if ($newRole && $oldRole) {
                // Both roles exist - need to migrate users and delete old one
                $migrations[$oldName] = [
                    'from' => $oldName,
                    'to' => $newName,
                    'users' => $oldRole->users_count,
                ];
                $deletions[$oldName] = $oldRole->users_count;

                $this->line("  • Migrate {$oldRole->users_count} user(s) from '{$oldName}' to '{$newName}'");
                $this->line("  • Delete duplicate role '{$oldName}'");
            } elseif ($oldRole && ! $newRole) {
                // Only old role exists - rename it
                $migrations[$oldName] = [
                    'from' => $oldName,
                    'to' => $newName,
                    'users' => $oldRole->users_count,
                    'rename' => true,
                ];

                $this->line("  • Rename role '{$oldName}' to '{$newName}' ({$oldRole->users_count} user(s))");
            }
        }

        $this->newLine();

        return [
            'migrations' => $migrations,
            'deletions' => $deletions,
        ];
    }

    /**
     * Execute the planned changes
     *
     * @param  array{migrations: array, deletions: array}  $changes
     */
    private function executeChanges(array $changes): void
    {
        $this->info('🚀 Executing changes...');
        $this->newLine();

        DB::beginTransaction();

        try {
            foreach ($changes['migrations'] as $change) {
                $oldRole = Role::where('name', $change['from'])->first();

                if (! $oldRole) {
                    continue;
                }

                if (isset($change['rename']) && $change['rename']) {
                    // Simple rename - just update the role name
                    $oldRole->update(['name' => $change['to']]);
                    $this->line("  ✅ Renamed '{$change['from']}' to '{$change['to']}'");
                } else {
                    // Migration needed - move users to target role then delete old one
                    $targetRole = Role::where('name', $change['to'])->first();

                    if ($targetRole) {
                        // Get all users with the old role
                        $users = $oldRole->users;

                        foreach ($users as $user) {
                            // Remove old role and assign new one if not already assigned
                            $user->removeRole($oldRole);
                            if (! $user->hasRole($targetRole)) {
                                $user->assignRole($targetRole);
                            }
                        }

                        $this->line("  ✅ Migrated {$change['users']} user(s) from '{$change['from']}' to '{$change['to']}'");
                    }
                }
            }

            // Delete duplicate roles
            foreach ($changes['deletions'] as $roleName => $userCount) {
                $role = Role::where('name', $roleName)->first();
                if ($role && $role->users()->count() === 0) {
                    $role->delete();
                    $this->line("  ✅ Deleted duplicate role '{$roleName}'");
                }
            }

            DB::commit();
            $this->newLine();
            $this->info('✅ All changes committed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during migration: '.$e->getMessage());

            throw $e;
        }

        // Clear permission cache after changes
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Show summary of changes
     *
     * @param  array{migrations: array, deletions: array}  $changes
     */
    private function showSummary(array $changes, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📊 Summary:');

        $migratedUsers = array_sum(array_column($changes['migrations'], 'users'));
        $deletedRoles = count($changes['deletions']);
        $renamedRoles = count(array_filter($changes['migrations'], fn (array $c): bool => isset($c['rename']) && $c['rename']));

        $prefix = $dryRun ? 'Would be ' : '';

        $this->table(
            ['Action', 'Count'],
            [
                [$prefix.'Users migrated', $migratedUsers],
                [$prefix.'Roles renamed', $renamedRoles],
                [$prefix.'Duplicate roles deleted', $deletedRoles],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply these changes.');
        }
    }
}
