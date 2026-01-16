<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('department_forms', function (Blueprint $table) {
            $table->foreignId('parent_form_id')
                ->nullable()
                ->after('is_template')
                ->constrained('department_forms')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_forms', function (Blueprint $table) {
            $table->dropForeign(['parent_form_id']);
            $table->dropColumn('parent_form_id');
        });
    }
};
