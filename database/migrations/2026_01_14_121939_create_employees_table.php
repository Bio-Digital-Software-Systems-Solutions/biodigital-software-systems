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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            // Personal Information
            $table->string('employee_number')->unique();
            $table->string('position')->nullable();
            $table->string('job_title')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('tax_id')->nullable();

            // Contact
            $table->string('personal_email')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('personal_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Germany');

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Employment
            $table->string('status')->default('active'); // active, inactive, on_leave, terminated
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract, intern, volunteer
            $table->date('hire_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();

            // Compensation
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // bank_transfer, cash, check
            $table->string('bank_name')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('bank_bic')->nullable();

            // Work Schedule
            $table->decimal('weekly_hours', 5, 2)->default(40.00);
            $table->json('working_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->time('default_start_time')->nullable();
            $table->time('default_end_time')->nullable();

            // Leave
            $table->integer('annual_leave_days')->default(30);
            $table->integer('remaining_leave_days')->default(30);
            $table->integer('sick_days_taken')->default(0);

            // Skills & Qualifications
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();

            // Documents
            $table->string('avatar')->nullable();
            $table->string('contract_document')->nullable();
            $table->string('id_document')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('employment_type');
            $table->index('hire_date');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
