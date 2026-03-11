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
        Schema::create('pcg_account_classes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('class_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pcg_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('class_id')->constrained('pcg_account_classes')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pcg_accounts')->nullOnDelete();
            $table->integer('level');
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('class_id');
            $table->index('parent_id');
        });

        Schema::create('ifrs_account_classes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('class_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ifrs_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('class_id')->constrained('ifrs_account_classes')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ifrs_accounts')->nullOnDelete();
            $table->integer('level');
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('class_id');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ifrs_accounts');
        Schema::dropIfExists('ifrs_account_classes');
        Schema::dropIfExists('pcg_accounts');
        Schema::dropIfExists('pcg_account_classes');
    }
};
