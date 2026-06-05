<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            if (Schema::hasColumn('care_services', 'mlr_agent_id') && ! Schema::hasColumn('care_services', 'care_service_agent_id')) {
                $table->renameColumn('mlr_agent_id', 'care_service_agent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            if (Schema::hasColumn('care_services', 'care_service_agent_id') && ! Schema::hasColumn('care_services', 'mlr_agent_id')) {
                $table->renameColumn('care_service_agent_id', 'mlr_agent_id');
            }
        });
    }
};
