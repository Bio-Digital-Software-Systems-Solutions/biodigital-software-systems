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
        Schema::table('messages', function (Blueprint $table): void {
            // Add recipient_type column (user or department)
            $table->string('recipient_type', 50)->default('user')->after('receiver_id');

            // Make receiver_id nullable since department messages won't have individual receiver
            $table->unsignedBigInteger('receiver_id')->nullable()->change();

            // Add department_id column for department-wide messages
            $table->unsignedBigInteger('department_id')->nullable()->after('recipient_type');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['recipient_type', 'department_id']);
            $table->unsignedBigInteger('receiver_id')->nullable(false)->change();
        });
    }
};
