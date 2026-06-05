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
            // Dual confirmation timestamps
            $table->timestamp('client_confirmed_at')->nullable()->after('confirmation_sent_at');
            $table->timestamp('pastor_confirmed_at')->nullable()->after('client_confirmed_at');

            // Client confirmation token for secure email link
            $table->string('client_confirmation_token')->nullable()->after('pastor_confirmed_at');

            // Pastor confirmation token for secure email link
            $table->string('pastor_confirmation_token')->nullable()->after('client_confirmation_token');

            // Index for token lookup
            $table->index('client_confirmation_token');
            $table->index('pastor_confirmation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->dropIndex(['client_confirmation_token']);
            $table->dropIndex(['pastor_confirmation_token']);
            $table->dropColumn([
                'client_confirmed_at',
                'pastor_confirmed_at',
                'client_confirmation_token',
                'pastor_confirmation_token',
            ]);
        });
    }
};
