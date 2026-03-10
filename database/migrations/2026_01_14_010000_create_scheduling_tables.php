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
        // 1. Weekly Schedules - Main schedule container
        if (!Schema::hasTable('weekly_schedules')) {
            Schema::create('weekly_schedules', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->date('week_start');
                $table->date('week_end');
                $table->string('status')->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();

                $table->unique(['department_id', 'week_start']);
            });
        }

        // 2. Department Scheduling Settings
        if (!Schema::hasTable('department_scheduling_settings')) {
            Schema::create('department_scheduling_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('department_id')->unique()->constrained()->cascadeOnDelete();
                $table->integer('default_shift_duration')->default(8);
                $table->integer('min_rest_between_shifts')->default(11);
                $table->integer('max_hours_per_week')->default(40);
                $table->integer('max_hours_per_day')->default(10);
                $table->integer('max_consecutive_days')->default(6);
                $table->decimal('overtime_threshold', 5, 2)->default(40);
                $table->boolean('allow_self_assignment')->default(true);
                $table->boolean('allow_shift_swap')->default(true);
                $table->boolean('require_swap_approval')->default(true);
                $table->integer('advance_schedule_weeks')->default(4);
                $table->boolean('auto_publish_enabled')->default(false);
                $table->time('auto_publish_time')->nullable();
                $table->string('auto_publish_day')->nullable();
                $table->boolean('notifications_enabled')->default(true);
                $table->json('notification_settings')->nullable();
                $table->timestamps();
            });
        }

        // 3. Scheduling Positions
        if (!Schema::hasTable('scheduling_positions')) {
            Schema::create('scheduling_positions', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('color')->nullable();
                $table->decimal('hourly_rate', 10, 2)->nullable();
                $table->json('required_skills')->nullable();
                $table->integer('min_experience_months')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Shifts
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('weekly_schedule_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('position_id')->nullable()->constrained('scheduling_positions')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('break_duration')->default(0);
                $table->string('type')->default('full_day');
                $table->string('status')->default('draft');
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('location')->nullable();
                $table->string('color')->nullable();
                $table->integer('min_employees')->default(1);
                $table->integer('max_employees')->default(1);
                $table->json('required_skills')->nullable();
                $table->decimal('hourly_rate', 10, 2)->nullable();
                $table->boolean('is_overtime')->default(false);
                $table->boolean('requires_approval')->default(false);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('checked_out_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['department_id', 'date']);
                $table->index(['user_id', 'date']);
            });
        }

        // 5. Shift Tasks
        if (!Schema::hasTable('shift_tasks')) {
            Schema::create('shift_tasks', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->integer('sort_order')->default(0);
                $table->integer('estimated_minutes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 6. Employee Availabilities
        if (!Schema::hasTable('employee_availabilities')) {
            Schema::create('employee_availabilities', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status')->default('available');
                $table->string('recurrence_type')->default('weekly');
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'department_id', 'day_of_week']);
            });
        }

        // 7. Employee Absences
        if (!Schema::hasTable('employee_absences')) {
            Schema::create('employee_absences', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_full_day')->default(true);
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('status')->default('pending');
                $table->text('reason')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('documents')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'start_date', 'end_date']);
            });
        }

        // 8. Shift Swap Requests
        if (!Schema::hasTable('shift_swap_requests')) {
            Schema::create('shift_swap_requests', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('requested_shift_id')->constrained('shifts')->cascadeOnDelete();
                $table->foreignId('offered_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('status')->default('pending_colleague');
                $table->text('reason')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 9. Time Entries
        if (!Schema::hasTable('time_entries')) {
            Schema::create('time_entries', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
                $table->date('date');
                $table->time('clock_in');
                $table->time('clock_out')->nullable();
                $table->integer('break_minutes')->default(0);
                $table->decimal('total_hours', 5, 2)->nullable();
                $table->decimal('overtime_hours', 5, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('clock_in_method')->default('manual');
                $table->string('clock_out_method')->nullable();
                $table->json('location_data')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'date']);
            });
        }

        // 10. Scheduling Templates
        if (!Schema::hasTable('scheduling_templates')) {
            Schema::create('scheduling_templates', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('shifts_data');
                $table->boolean('is_default')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 11. Schedule Conflicts
        if (!Schema::hasTable('schedule_conflicts')) {
            Schema::create('schedule_conflicts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('conflict_type');
                $table->string('severity')->default('warning');
                $table->text('description');
                $table->json('related_data')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        // 12. Skills
        if (!Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 13. Employee Skills
        if (!Schema::hasTable('employee_skills')) {
            Schema::create('employee_skills', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
                $table->string('proficiency_level')->default('intermediate');
                $table->date('acquired_date')->nullable();
                $table->date('certified_until')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'skill_id']);
            });
        }

        // 14. Shift Assignments History
        if (!Schema::hasTable('shift_assignment_history')) {
            Schema::create('shift_assignment_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        // 15. Overtime Rules
        if (!Schema::hasTable('overtime_rules')) {
            Schema::create('overtime_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('threshold_hours', 5, 2);
                $table->string('period');
                $table->decimal('multiplier', 3, 2)->default(1.5);
                $table->boolean('requires_approval')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 16. Scheduling Notifications
        if (!Schema::hasTable('scheduling_notifications')) {
            Schema::create('scheduling_notifications', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->string('channel')->default('in_app');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'read_at']);
            });
        }

        // 17. Scheduling Reports
        if (!Schema::hasTable('scheduling_reports')) {
            Schema::create('scheduling_reports', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->date('period_start');
                $table->date('period_end');
                $table->json('data');
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduling_reports');
        Schema::dropIfExists('scheduling_notifications');
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('shift_assignment_history');
        Schema::dropIfExists('employee_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('schedule_conflicts');
        Schema::dropIfExists('scheduling_templates');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('employee_absences');
        Schema::dropIfExists('employee_availabilities');
        Schema::dropIfExists('shift_tasks');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('scheduling_positions');
        Schema::dropIfExists('department_scheduling_settings');
        Schema::dropIfExists('weekly_schedules');
    }
};
