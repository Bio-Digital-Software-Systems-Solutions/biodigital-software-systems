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
        Schema::table('care_services', function (Blueprint $table): void {
            $table->string('client_name')->nullable()->change();
            $table->string('client_email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->string('client_name')->nullable(false)->change();
            $table->string('client_email')->nullable(false)->change();
        });
    }
};
