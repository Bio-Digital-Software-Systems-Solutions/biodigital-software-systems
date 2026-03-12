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
        Schema::create('ohada_account_classes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('class_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // bilan, gestion, hors_bilan
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ohada_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('class_id')->constrained('ohada_account_classes')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ohada_accounts')->nullOnDelete();
            $table->integer('level'); // 1=class, 2=group, 3=account, 4=sub-account
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('class_id');
            $table->index('parent_id');
            $table->index('level');
        });

        Schema::create('ohada_financial_statements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('accounting_system_id')->constrained('accounting_systems')->cascadeOnDelete();
            $table->json('structure')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ohada_financial_statements');
        Schema::dropIfExists('ohada_accounts');
        Schema::dropIfExists('ohada_account_classes');
    }
};
