<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Templates de rapports
        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50)->default('custom');
            $table->string('period_type', 20)->default('monthly');
            $table->json('sections_config')->nullable();
            $table->json('default_approvers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate')->default(false);
            $table->unsignedTinyInteger('auto_generate_day')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active']);
            $table->index('type');
        });

        // 2. Rapports départementaux
        Schema::create('department_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type', 50)->default('monthly_activity');
            $table->string('status', 30)->default('draft');
            $table->string('period_type', 20)->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->text('executive_summary')->nullable();
            $table->text('submission_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->index('author_id');
            $table->index('type');
        });

        // 3. Sections de rapport
        Schema::create('report_sections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('content')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'order']);
            $table->index('type');
        });

        // 4. Activités départementales
        Schema::create('department_activities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->json('participants')->nullable();
            $table->text('outcomes')->nullable();
            $table->json('metrics')->nullable();
            $table->foreignId('related_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index('category');
        });

        // 5. Objectifs départementaux
        Schema::create('department_objectives', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('department_objectives')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('not_started');
            $table->string('priority', 20)->default('medium');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->json('key_results')->nullable();
            $table->json('success_criteria')->nullable();
            $table->json('blockers')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->index('assigned_to');
            $table->index('priority');
        });

        // 6. KPIs départementaux
        Schema::create('department_kpis', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 20);
            $table->decimal('target_value', 15, 4);
            $table->decimal('warning_threshold', 15, 4)->nullable();
            $table->decimal('critical_threshold', 15, 4)->nullable();
            $table->string('trend_direction', 30)->default('higher_is_better');
            $table->string('calculation_method')->nullable();
            $table->string('data_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active']);
            $table->index('display_order');
        });

        // 7. Valeurs des KPIs
        Schema::create('department_kpi_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kpi_id')->constrained('department_kpis')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('department_reports')->nullOnDelete();
            $table->decimal('value', 15, 4);
            $table->date('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['kpi_id', 'recorded_at']);
            $table->index('report_id');
        });

        // 8. Approbations de rapport
        Schema::create('report_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('role', 50);
            $table->string('status', 20)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'step']);
            $table->index(['user_id', 'status']);
        });

        // 9. Commentaires de rapport
        Schema::create('report_comments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('report_sections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('report_comments')->cascadeOnDelete();
            $table->string('type', 20)->default('comment');
            $table->text('content');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_id', 'section_id']);
            $table->index('parent_id');
            $table->index('type');
        });

        // 10. Versions de rapport
        Schema::create('report_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['report_id', 'version_number']);
            $table->index('created_by');
        });

        // 11. Pièces jointes
        Schema::create('report_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('report_sections')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->string('path');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_id', 'section_id']);
            $table->index('uploaded_by');
        });

        // 12. Rappels
        Schema::create('report_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->string('type', 20);
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'scheduled_at']);
            $table->index(['recipient_id', 'sent_at']);
            $table->index('type');
        });

        // 13. Tags de rapport
        Schema::create('report_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('department_reports')->cascadeOnDelete();
            $table->string('tag', 50);
            $table->timestamps();

            $table->unique(['report_id', 'tag']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_tags');
        Schema::dropIfExists('report_reminders');
        Schema::dropIfExists('report_attachments');
        Schema::dropIfExists('report_versions');
        Schema::dropIfExists('report_comments');
        Schema::dropIfExists('report_approvals');
        Schema::dropIfExists('department_kpi_values');
        Schema::dropIfExists('department_kpis');
        Schema::dropIfExists('department_objectives');
        Schema::dropIfExists('department_activities');
        Schema::dropIfExists('report_sections');
        Schema::dropIfExists('department_reports');
        Schema::dropIfExists('report_templates');
    }
};
