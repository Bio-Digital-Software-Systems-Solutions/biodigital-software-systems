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
        Schema::table('messages', function (Blueprint $table) {
            // Store CC and BCC as JSON arrays of user IDs
            $table->json('cc_recipients')->nullable()->after('receiver_id');
            $table->json('bcc_recipients')->nullable()->after('cc_recipients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['cc_recipients', 'bcc_recipients']);
        });
    }
};
