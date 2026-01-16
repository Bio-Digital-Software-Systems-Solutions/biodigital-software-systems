<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_form_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('department_form_submissions', 'workflow_instance_id')) {
                $table->foreignId('workflow_instance_id')->nullable()->after('user_id')->constrained('workflow_instances')->onDelete('set null');
            }
            if (!Schema::hasColumn('department_form_submissions', 'step_instance_id')) {
                $table->foreignId('step_instance_id')->nullable()->after('workflow_instance_id')->constrained('workflow_step_instances')->onDelete('set null');
            }
            if (!Schema::hasColumn('department_form_submissions', 'current_step')) {
                $table->integer('current_step')->default(0)->after('data');
            }
            if (!Schema::hasColumn('department_form_submissions', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('department_form_submissions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('department_form_submissions', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('department_form_submissions', function (Blueprint $table) {
            $table->dropForeign(['workflow_instance_id']);
            $table->dropForeign(['step_instance_id']);
            $table->dropColumn([
                'workflow_instance_id',
                'step_instance_id',
                'current_step',
                'ip_address',
                'user_agent',
                'submitted_at',
            ]);
        });
    }
};
