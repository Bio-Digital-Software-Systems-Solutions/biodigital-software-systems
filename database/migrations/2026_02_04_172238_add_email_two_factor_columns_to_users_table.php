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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_two_factor_code', 8)->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('email_two_factor_expires_at')->nullable()->after('email_two_factor_code');
            $table->boolean('email_two_factor_enabled')->default(false)->after('email_two_factor_expires_at');
            $table->string('preferred_two_factor_method')->nullable()->after('email_two_factor_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_two_factor_code',
                'email_two_factor_expires_at',
                'email_two_factor_enabled',
                'preferred_two_factor_method',
            ]);
        });
    }
};
