<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epics', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('business_value')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->date('target_date')->nullable();
            $table->json('labels')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epics');
    }
};
