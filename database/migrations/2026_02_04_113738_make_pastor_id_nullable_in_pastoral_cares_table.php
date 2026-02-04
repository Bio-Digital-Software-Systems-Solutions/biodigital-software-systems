<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * With polymorphic assigned_agent relationship, pastor_id is no longer required.
     * MLR agents and other agent types can be assigned without having a pastor_id.
     */
    public function up(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table) {
            $table->unsignedBigInteger('pastor_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table) {
            $table->unsignedBigInteger('pastor_id')->nullable(false)->change();
        });
    }
};
