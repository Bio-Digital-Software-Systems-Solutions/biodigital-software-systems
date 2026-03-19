<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_sops', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('extension');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('routine_sops', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['status', 'validated_by', 'validated_at']);
        });
    }
};
