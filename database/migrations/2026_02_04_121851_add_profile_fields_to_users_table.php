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
        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable()->after('avatar');
            $table->string('position')->nullable()->after('bio');
            $table->string('address')->nullable()->after('position');
            $table->json('languages')->nullable()->after('address');
            $table->json('hobbies')->nullable()->after('languages');
            $table->boolean('is_calendar_public')->default(false)->after('hobbies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'bio',
                'position',
                'address',
                'languages',
                'hobbies',
                'is_calendar_public',
            ]);
        });
    }
};
