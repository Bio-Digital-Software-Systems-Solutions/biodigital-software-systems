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
        Schema::create('stars', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('nominated_by')->nullable()->constrained('users')->nullOnDelete();

            // Star identification
            $table->string('star_number')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Status and type
            $table->string('status')->default('active');
            $table->string('type')->default('volunteer'); // volunteer, leader, mentor, ambassador

            // Recognition details
            $table->string('category')->nullable(); // service, leadership, community, worship, etc.
            $table->integer('points')->default(0);
            $table->integer('level')->default(1);
            $table->date('recognition_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Achievements
            $table->json('achievements')->nullable();
            $table->json('badges')->nullable();
            $table->json('skills')->nullable();
            $table->json('areas_of_service')->nullable();

            // Availability
            $table->json('available_days')->nullable();
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->integer('hours_per_week')->default(0);
            $table->integer('total_hours_served')->default(0);

            // Contact preferences
            $table->boolean('is_contactable')->default(true);
            $table->string('preferred_contact_method')->nullable(); // email, phone, sms
            $table->boolean('receive_notifications')->default(true);

            // Social/Public profile
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_public_profile')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);

            // Testimonial/Quote
            $table->text('testimonial')->nullable();
            $table->string('favorite_verse')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('category');
            $table->index('level');
            $table->index('is_featured');
            $table->index('recognition_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stars');
    }
};
