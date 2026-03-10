<?php

namespace App\Services\Report;

use App\Enums\Report\ReportPeriodType;
use App\Enums\Report\ReportSectionType;
use App\Enums\Report\ReportStatus;
use App\Enums\Report\ReportType;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\ReportApproval;
use App\Models\ReportAttachment;
use App\Models\ReportSection;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportGeneratorService
{
    public function __construct(
        protected ReportDataAggregatorService $aggregator
    ) {}

    /**
     * Create a new report from a template.
     */
    public function createFromTemplate(
        ReportTemplate $template,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $authorId
    ): DepartmentReport {
        return DB::transaction(function () use ($template, $periodStart, $periodEnd, $authorId) {
            $report = DepartmentReport::create([
                'department_id' => $template->department_id,
                'template_id' => $template->id,
                'author_id' => $authorId,
                'title' => $this->generateTitle($template, $periodStart),
                'type' => $template->type,
                'status' => ReportStatus::DRAFT,
                'period_type' => $template->period_type,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'version' => 1,
            ]);

            $this->createSectionsFromConfig($report, $template->getSectionsConfig());
            $this->createApprovalWorkflow($report, $template->getDefaultApprovers());

            return $report;
        });
    }

    /**
     * Create a new report manually.
     */
    public function createReport(array $data, int $authorId): DepartmentReport
    {
        return DB::transaction(function () use ($data, $authorId) {
            $periodType = ReportPeriodType::from($data['period_type']);
            [$periodStart, $periodEnd] = isset($data['period_start'], $data['period_end'])
                ? [Carbon::parse($data['period_start']), Carbon::parse($data['period_end'])]
                : $periodType->getDates(Carbon::now());

            $report = DepartmentReport::create([
                'department_id' => $data['department_id'],
                'template_id' => $data['template_id'] ?? null,
                'author_id' => $authorId,
                'title' => $data['title'],
                'type' => ReportType::from($data['type']),
                'status' => ReportStatus::DRAFT,
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'executive_summary' => $data['executive_summary'] ?? null,
                'version' => 1,
            ]);

            $sections = $data['sections'] ?? ReportType::from($data['type'])->defaultSections();
            $this->createSectionsFromConfig($report, $sections);

            if (!empty($data['approvers'])) {
                $this->createApprovalWorkflow($report, $data['approvers']);
            }

            return $report;
        });
    }

    /**
     * Auto-generate a report for a department.
     */
    public function autoGenerate(
        Department $department,
        ReportType $type,
        ReportPeriodType $periodType,
        ?Carbon $referenceDate = null,
        ?int $authorId = null
    ): DepartmentReport {
        $referenceDate ??= Carbon::now();
        [$periodStart, $periodEnd] = $periodType->getDates($referenceDate);

        $report = $this->createReport([
            'department_id' => $department->id,
            'title' => $this->generateAutoTitle($department, $type, $periodStart),
            'type' => $type->value,
            'period_type' => $periodType->value,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ], $authorId ?? $department->manager_id ?? auth()->id());

        $this->populateWithData($report);

        return $report;
    }

    /**
     * Populate report sections with aggregated data.
     */
    public function populateWithData(DepartmentReport $report): DepartmentReport
    {
        $data = $this->aggregator->aggregateForReport($report);

        foreach ($report->sections as $section) {
            $content = $this->generateSectionContent($section, $data);
            $section->updateContent($content);
        }

        $report->update([
            'executive_summary' => $this->generateExecutiveSummary($report, $data),
        ]);

        return $report->fresh(['sections']);
    }

    /**
     * Update a report.
     */
    public function updateReport(DepartmentReport $report, array $data): DepartmentReport
    {
        if (!$report->can_edit) {
            throw new \Exception('Ce rapport ne peut plus être modifié.');
        }

        $report->update($data);
        return $report->fresh();
    }

    /**
     * Update a report section.
     */
    public function updateSection(ReportSection $section, array $data): ReportSection
    {
        if (!$section->report->can_edit) {
            throw new \Exception('Ce rapport ne peut plus être modifié.');
        }

        $section->update($data);
        return $section->fresh();
    }

    /**
     * Submit a report for review.
     */
    public function submitForReview(DepartmentReport $report, ?string $notes = null): DepartmentReport
    {
        if (!$report->can_submit) {
            throw new \Exception('Ce rapport ne peut pas être soumis. Veuillez compléter toutes les sections requises.');
        }

        $report->createVersion('Soumission pour révision');
        $report->submission_notes = $notes;
        $report->transitionTo(ReportStatus::PENDING_REVIEW);

        return $report->fresh();
    }

    /**
     * Process an approval decision.
     */
    public function processApproval(
        DepartmentReport $report,
        int $userId,
        bool $approved,
        ?string $comments = null
    ): DepartmentReport {
        $approval = $report->approvals()
            ->where('user_id', $userId)
            ->pending()
            ->first();

        if (!$approval) {
            throw new \Exception('Vous n\'avez pas d\'approbation en attente pour ce rapport.');
        }

        if ($approved) {
            $approval->approve($comments);
            $this->checkAllApprovals($report);
        } else {
            $approval->reject($comments);
            $report->rejection_reason = $comments;
            $report->transitionTo(ReportStatus::REVISION_REQUESTED);
        }

        return $report->fresh();
    }

    /**
     * Request revision on a report.
     */
    public function requestRevision(DepartmentReport $report, string $reason, int $requesterId): DepartmentReport
    {
        $report->rejection_reason = $reason;
        $report->approver_id = $requesterId;
        $report->transitionTo(ReportStatus::REVISION_REQUESTED);

        return $report->fresh();
    }

    /**
     * Publish an approved report.
     */
    public function publish(DepartmentReport $report): DepartmentReport
    {
        if ($report->status !== ReportStatus::APPROVED) {
            throw new \Exception('Seuls les rapports approuvés peuvent être publiés.');
        }

        $report->transitionTo(ReportStatus::PUBLISHED);
        return $report->fresh();
    }

    /**
     * Archive a report.
     */
    public function archive(DepartmentReport $report): DepartmentReport
    {
        $report->transitionTo(ReportStatus::ARCHIVED);
        return $report->fresh();
    }

    /**
     * Add an attachment to a report.
     */
    public function addAttachment(
        DepartmentReport $report,
        UploadedFile $file,
        int $uploaderId,
        ?int $sectionId = null
    ): ReportAttachment {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "reports/{$report->id}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file));

        return ReportAttachment::create([
            'report_id' => $report->id,
            'section_id' => $sectionId,
            'uploaded_by' => $uploaderId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);
    }

    /**
     * Remove an attachment.
     */
    public function removeAttachment(ReportAttachment $attachment): void
    {
        $attachment->delete();
    }

    /**
     * Add tags to a report.
     */
    public function addTags(DepartmentReport $report, array $tags): void
    {
        foreach ($tags as $tag) {
            $report->tags()->firstOrCreate(['tag' => $tag]);
        }
    }

    /**
     * Remove a tag from a report.
     */
    public function removeTag(DepartmentReport $report, string $tag): void
    {
        $report->tags()->where('tag', $tag)->delete();
    }

    /**
     * Export report to array for PDF/Excel generation.
     */
    public function exportToArray(DepartmentReport $report): array
    {
        $report->load(['department', 'author', 'approver', 'sections', 'attachments', 'tags']);

        return [
            'report' => $report->toExportArray(),
            'sections' => $report->sections->map(fn($s): array => [
                'type' => $s->type->value,
                'type_label' => $s->type_label,
                'title' => $s->title,
                'content' => $s->content,
                'is_complete' => $s->is_complete,
            ])->toArray(),
            'attachments' => $report->attachments->map(fn($a): array => [
                'filename' => $a->original_filename,
                'url' => $a->url,
                'size' => $a->size_formatted,
            ])->toArray(),
            'tags' => $report->tags->pluck('tag')->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create sections from configuration.
     */
    protected function createSectionsFromConfig(DepartmentReport $report, array $sections): void
    {
        $order = 0;
        foreach ($sections as $sectionConfig) {
            $type = is_string($sectionConfig)
                ? ReportSectionType::from($sectionConfig)
                : ReportSectionType::from($sectionConfig['type'] ?? $sectionConfig);

            ReportSection::create([
                'report_id' => $report->id,
                'type' => $type,
                'title' => is_array($sectionConfig) ? ($sectionConfig['title'] ?? $type->label()) : $type->label(),
                'description' => is_array($sectionConfig) ? ($sectionConfig['description'] ?? null) : null,
                'order' => $order++,
                'is_required' => is_array($sectionConfig) ? ($sectionConfig['is_required'] ?? false) : false,
                'config' => is_array($sectionConfig) ? ($sectionConfig['config'] ?? null) : null,
            ]);
        }
    }

    /**
     * Create approval workflow.
     */
    protected function createApprovalWorkflow(DepartmentReport $report, array $approvers): void
    {
        $step = 1;
        foreach ($approvers as $approver) {
            ReportApproval::create([
                'report_id' => $report->id,
                'user_id' => is_array($approver) ? $approver['user_id'] : $approver,
                'step' => $step++,
                'role' => is_array($approver) ? ($approver['role'] ?? 'approver') : 'approver',
            ]);
        }
    }

    /**
     * Check if all approvals are complete.
     */
    protected function checkAllApprovals(DepartmentReport $report): void
    {
        $pendingCount = $report->approvals()->pending()->count();

        if ($pendingCount === 0) {
            $report->approver_id = $report->approvals()->latest('decided_at')->first()?->user_id;
            $report->transitionTo(ReportStatus::APPROVED);
        } else {
            $report->transitionTo(ReportStatus::UNDER_REVIEW);
        }
    }

    /**
     * Generate a title for a report.
     */
    protected function generateTitle(ReportTemplate $template, Carbon $periodStart): string
    {
        $periodLabel = match ($template->period_type) {
            ReportPeriodType::MONTHLY => $periodStart->translatedFormat('F Y'),
            ReportPeriodType::QUARTERLY => 'T' . ceil($periodStart->month / 3) . ' ' . $periodStart->year,
            ReportPeriodType::ANNUAL => (string) $periodStart->year,
            ReportPeriodType::WEEKLY => 'Semaine ' . $periodStart->weekOfYear . ' ' . $periodStart->year,
            default => $periodStart->format('d/m/Y'),
        };

        return "{$template->name} - {$periodLabel}";
    }

    /**
     * Generate an auto-generated title.
     */
    protected function generateAutoTitle(Department $department, ReportType $type, Carbon $periodStart): string
    {
        return "{$type->label()} - {$department->name} - {$periodStart->translatedFormat('F Y')}";
    }

    /**
     * Generate content for a section based on its type.
     */
    protected function generateSectionContent(ReportSection $section, array $data): array
    {
        return match ($section->type) {
            ReportSectionType::METRICS => $this->generateMetricsContent($data),
            ReportSectionType::CHART => $this->generateChartContent($data),
            ReportSectionType::TABLE => $this->generateTableContent($section, $data),
            ReportSectionType::CHECKLIST => $this->generateChecklistContent($data),
            ReportSectionType::LIST => $this->generateListContent($data),
            ReportSectionType::BUDGET => $this->generateBudgetContent($data),
            ReportSectionType::TIMELINE => $this->generateTimelineContent($data),
            default => [],
        };
    }

    protected function generateMetricsContent(array $data): array
    {
        $summary = $data['summary'] ?? [];
        $trends = $data['trends'] ?? [];

        return [
            'metrics' => [
                [
                    'label' => 'Activités',
                    'value' => $summary['total_activities'] ?? 0,
                    'trend' => $trends['activities'] ?? null,
                ],
                [
                    'label' => 'Heures',
                    'value' => $summary['total_hours'] ?? 0,
                    'unit' => 'h',
                    'trend' => $trends['hours'] ?? null,
                ],
                [
                    'label' => 'Objectifs complétés',
                    'value' => $summary['objectives_completed'] ?? 0,
                    'total' => $summary['objectives_total'] ?? 0,
                    'trend' => $trends['objectives_completed'] ?? null,
                ],
                [
                    'label' => 'Taux de réalisation',
                    'value' => $summary['completion_rate'] ?? 0,
                    'unit' => '%',
                ],
                [
                    'label' => 'Participants',
                    'value' => $summary['unique_participants'] ?? 0,
                ],
                [
                    'label' => 'Projets actifs',
                    'value' => $summary['projects_active'] ?? 0,
                ],
            ],
        ];
    }

    protected function generateChartContent(array $data): array
    {
        $activities = $data['activities'] ?? [];

        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($activities['by_category'] ?? []),
                'datasets' => [
                    [
                        'label' => 'Activités',
                        'data' => array_column($activities['by_category'] ?? [], 'count'),
                    ],
                    [
                        'label' => 'Heures',
                        'data' => array_column($activities['by_category'] ?? [], 'hours'),
                    ],
                ],
            ],
            'timeline' => $activities['timeline'] ?? [],
        ];
    }

    protected function generateTableContent(ReportSection $section, array $data): array
    {
        $objectives = $data['objectives']['list'] ?? [];

        return [
            'headers' => ['Objectif', 'Statut', 'Progrès', 'Échéance', 'Responsable'],
            'rows' => array_map(fn(array $o): array => [
                $o['title'],
                $o['status_label'],
                $o['progress'] . '%',
                $o['target_date'] ?? '-',
                $o['assignee'] ?? '-',
            ], $objectives),
        ];
    }

    protected function generateChecklistContent(array $data): array
    {
        $objectives = $data['objectives']['list'] ?? [];

        return [
            'items' => array_map(fn(array $o): array => [
                'label' => $o['title'],
                'completed' => $o['status'] === 'completed',
                'progress' => $o['progress'],
            ], $objectives),
        ];
    }

    protected function generateListContent(array $data): array
    {
        $activities = $data['activities']['recent'] ?? [];

        return [
            'items' => array_map(fn(array $a): array => [
                'title' => $a['title'],
                'subtitle' => $a['category_label'] . ' - ' . $a['date'],
                'metadata' => $a['duration'] ? $a['duration'] . 'h' : null,
            ], $activities),
        ];
    }

    protected function generateBudgetContent(array $data): array
    {
        return [
            'total' => 0,
            'spent' => 0,
            'remaining' => 0,
            'items' => [],
        ];
    }

    protected function generateTimelineContent(array $data): array
    {
        $activities = $data['activities']['timeline'] ?? [];

        return [
            'events' => array_map(fn(array $a): array => [
                'date' => $a['date'],
                'title' => $a['count'] . ' activités',
                'description' => $a['hours'] . ' heures',
            ], $activities),
        ];
    }

    /**
     * Generate executive summary from data.
     */
    protected function generateExecutiveSummary(DepartmentReport $report, array $data): string
    {
        $summary = $data['summary'] ?? [];
        $trends = $data['trends'] ?? [];

        $activitiesTrend = $trends['activities']['direction'] ?? 'stable';
        $trendText = match ($activitiesTrend) {
            'up' => 'en hausse',
            'down' => 'en baisse',
            default => 'stable',
        };

        $parts = [];
        $parts[] = "Ce rapport couvre la période du {$report->period_start->format('d/m/Y')} au {$report->period_end->format('d/m/Y')}.";

        if (isset($summary['total_activities'])) {
            $parts[] = "{$summary['total_activities']} activités ont été réalisées pour un total de {$summary['total_hours']} heures ({$trendText} par rapport à la période précédente).";
        }

        if (isset($summary['objectives_completed'], $summary['objectives_total']) && $summary['objectives_total'] > 0) {
            $parts[] = "{$summary['objectives_completed']} objectifs sur {$summary['objectives_total']} ont été atteints, soit un taux de réalisation de {$summary['completion_rate']}%.";
        }

        if (isset($summary['unique_participants'])) {
            $parts[] = "{$summary['unique_participants']} membres ont participé aux activités du département.";
        }

        return implode(' ', $parts);
    }
}
