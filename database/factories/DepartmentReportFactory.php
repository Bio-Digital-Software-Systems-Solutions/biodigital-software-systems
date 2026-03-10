<?php

namespace Database\Factories;

use App\Enums\Report\ReportPeriodType;
use App\Enums\Report\ReportStatus;
use App\Enums\Report\ReportType;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\ReportTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentReport>
 */
class DepartmentReportFactory extends Factory
{
    protected $model = DepartmentReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodType = fake()->randomElement(ReportPeriodType::cases());
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        return [
            'department_id' => Department::factory(),
            'template_id' => null,
            'author_id' => User::factory(),
            'approver_id' => null,
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(ReportType::cases()),
            'status' => ReportStatus::DRAFT,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'executive_summary' => fake()->optional()->paragraphs(2, true),
            'submission_notes' => null,
            'approval_notes' => null,
            'rejection_reason' => null,
            'submitted_at' => null,
            'approved_at' => null,
            'published_at' => null,
            'version' => 1,
            'metadata' => [],
        ];
    }

    /**
     * Set report for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes): array => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Set report author.
     */
    public function byAuthor(User $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_id' => $author->id,
        ]);
    }

    /**
     * Set report as monthly.
     */
    public function monthly(?Carbon $date = null): static
    {
        $date ??= Carbon::now();

        return $this->state(fn (array $attributes): array => [
            'period_type' => ReportPeriodType::MONTHLY,
            'period_start' => $date->copy()->startOfMonth(),
            'period_end' => $date->copy()->endOfMonth(),
        ]);
    }

    /**
     * Set report as quarterly.
     */
    public function quarterly(?Carbon $date = null): static
    {
        $date ??= Carbon::now();

        return $this->state(fn (array $attributes): array => [
            'period_type' => ReportPeriodType::QUARTERLY,
            'period_start' => $date->copy()->startOfQuarter(),
            'period_end' => $date->copy()->endOfQuarter(),
        ]);
    }

    /**
     * Set report as annual.
     */
    public function annual(?int $year = null): static
    {
        $year ??= Carbon::now()->year;

        return $this->state(fn (array $attributes): array => [
            'period_type' => ReportPeriodType::ANNUAL,
            'period_start' => Carbon::create($year)->startOfYear(),
            'period_end' => Carbon::create($year)->endOfYear(),
        ]);
    }

    /**
     * Set report status as draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::DRAFT,
        ]);
    }

    /**
     * Set report status as pending review.
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::PENDING_REVIEW,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Set report status as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::APPROVED,
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'approver_id' => User::factory(),
        ]);
    }

    /**
     * Set report status as published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::PUBLISHED,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'published_at' => now(),
            'approver_id' => User::factory(),
        ]);
    }

    /**
     * Set report type.
     */
    public function ofType(ReportType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    /**
     * Set custom period.
     */
    public function forPeriod(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_type' => ReportPeriodType::CUSTOM,
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }

    /**
     * Set report with template.
     */
    public function withTemplate(ReportTemplate $template): static
    {
        return $this->state(fn (array $attributes): array => [
            'template_id' => $template->id,
        ]);
    }

    /**
     * Set report with executive summary.
     */
    public function withSummary(string $summary): static
    {
        return $this->state(fn (array $attributes): array => [
            'executive_summary' => $summary,
        ]);
    }
}
