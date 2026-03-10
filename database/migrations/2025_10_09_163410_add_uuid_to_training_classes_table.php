<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_classes', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        \App\Models\TrainingClass::whereNull('uuid')->each(function ($class): void {
            $class->uuid = (string) Str::uuid();
            $class->save();
        });

        // Make uuid unique and not nullable
        Schema::table('training_classes', function (Blueprint $table): void {
            $table->uuid('uuid')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_classes', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
