<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $renames = [
        'view pastoral care' => 'view care service',
        'create pastoral care' => 'create care service',
        'edit pastoral care' => 'edit care service',
        'delete pastoral care' => 'delete care service',
        'manage pastoral care' => 'manage care service',
        'select pastor for pastoral care' => 'select pastor for care service',
        'transfer pastoral care' => 'transfer care service',
        'view all pastoral care' => 'view all care service',
        'view pastoral care client notes' => 'view care service client notes',
        'view pastoral care statistics' => 'view care service statistics',
        'manage pastor availability' => 'manage care service availability',
        'manage pastoral appointments' => 'manage care service appointments',
    ];

    public function up(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('permissions')
                ->where('name', $old)
                ->update(['name' => $new]);
        }

        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('permissions')
                ->where('name', $new)
                ->update(['name' => $old]);
        }

        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
