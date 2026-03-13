<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_user', function (Blueprint $table): void {
            if (! Schema::hasColumn('shift_user', 'time_slot')) {
                $table->string('time_slot', 5)->default('00:00')->after('user_id');
            }
        });

        Schema::table('shift_user', function (Blueprint $table): void {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['shift_id', 'user_id']);
            $table->unique(['shift_id', 'user_id', 'time_slot']);
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('shift_user', function (Blueprint $table): void {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['shift_id', 'user_id', 'time_slot']);
            $table->unique(['shift_id', 'user_id']);
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->dropColumn('time_slot');
        });
    }
};
