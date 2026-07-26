<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $permissionRenames = [
        'view stars' => 'view volunteers',
        'create stars' => 'create volunteers',
        'edit stars' => 'edit volunteers',
        'delete stars' => 'delete volunteers',
        'manage stars' => 'manage volunteers',
    ];

    public function up(): void
    {
        Schema::rename('stars', 'volunteers');

        Schema::table('volunteers', function (Blueprint $table): void {
            $table->renameColumn('star_number', 'volunteer_number');
        });

        if (Schema::hasTable('permissions')) {
            foreach ($this->permissionRenames as $old => $new) {
                DB::table('permissions')->where('name', $old)->update(['name' => $new]);
            }
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('name', 'star')->update(['name' => 'volunteer']);
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', 'App\Models\Star')
                ->update(['subject_type' => 'App\Models\Volunteer']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('volunteers', function (Blueprint $table): void {
            $table->renameColumn('volunteer_number', 'star_number');
        });

        Schema::rename('volunteers', 'stars');

        if (Schema::hasTable('permissions')) {
            foreach ($this->permissionRenames as $old => $new) {
                DB::table('permissions')->where('name', $new)->update(['name' => $old]);
            }
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('name', 'volunteer')->update(['name' => 'star']);
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', 'App\Models\Volunteer')
                ->update(['subject_type' => 'App\Models\Star']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
