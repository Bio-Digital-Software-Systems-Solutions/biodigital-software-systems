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
        Schema::table('departments', function (Blueprint $table): void {
            $table->foreignId('first_deputy_id')->nullable()->after('head_of_department')->constrained('users')->nullOnDelete();
            $table->foreignId('second_deputy_id')->nullable()->after('first_deputy_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('first_deputy_id');
            $table->dropConstrainedForeignId('second_deputy_id');
        });
    }
};
