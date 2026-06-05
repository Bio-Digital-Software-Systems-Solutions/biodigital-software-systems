<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mlr_agents') && ! Schema::hasTable('care_service_agents')) {
            Schema::rename('mlr_agents', 'care_service_agents');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('care_service_agents') && ! Schema::hasTable('mlr_agents')) {
            Schema::rename('care_service_agents', 'mlr_agents');
        }
    }
};
