<?php

namespace App\Http\Controllers;

use App\Enums\Report\ReportPeriodType;
use App\Enums\Report\ReportStatus;
use App\Enums\Report\ReportType;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\ReportComment;
use App\Models\ReportSection;
use App\Models\ReportTemplate;
use App\Models\ReportTag;
use App\Services\Report\ReportDataAggregatorService;
use App\Services\Report\ReportGeneratorService;
use App\Services\Report\ReportPdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DepartmentReportController extends Controller
{
    public function __construct(
        protected ReportGeneratorService $reportGenerator,
        protected ReportDataAggregatorService $dataAggregator,
        protected ReportPdfExportService $pdfExportService
    ) {}

    /**
     * Display a listing of reports.
     */
    public function index(Request $request)
    {
        $query = DepartmentReport::with(['department', 'author', 'template'])
            ->withCount(['sections', 'comments', 'attachments']);

        if ($request->filled('department_id')) {
            $query->forDepartment($request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->filled('year')) {
            $query->byYear($request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('executive_summary', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'departments' => Department::active()->ordered()->get(['id', 'name', 'uuid']),
            'statuses' => ReportStatus::toSelectOptions(),
            'types' => ReportType::toSelectOptions(),
            'periodTypes' => ReportPeriodType::toSelectOptions(),
            'filters' => $request->only(['department_id', 'status', 'type', 'period_type', 'year', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new report.
     */
    public function create(Request $request)
    {
        $templates = ReportTemplate::active()
            ->when($request->filled('department_id'), fn($q) => $q->where(function ($q2) use ($request): void {
                $q2->where('department_id', $request->department_id)
                    ->orWhereNull('department_id');
            }))
            ->get(['id', 'uuid', 'name', 'type', 'period_type', 'description']);

        return Inertia::render('Reports/Create', [
            'departments' => Department::active()->ordered()->get(['id', 'name', 'uuid']),
            'templates' => $templates,
            'types' => ReportType::toSelectOptions(),
            'periodTypes' => ReportPeriodType::toSelectOptions(),
            'departmentId' => $request->department_id,
        ]);
    }

    /**
     * Store a newly created report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'template_id' => 'nullable|exists:report_templates,id',
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', ReportType::values()),
            'period_type' => 'required|string|in:' . implode(',', ReportPeriodType::values()),
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'executive_summary' => 'nullable|string',
            'sections' => 'nullable|array',
            'approvers' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        if ($validated['template_id']) {
            $template = ReportTemplate::findOrFail($validated['template_id']);
            $report = $this->reportGenerator->createFromTemplate(
                $template,
                \Carbon\Carbon::parse($validated['period_start']),
                \Carbon\Carbon::parse($validated['period_end']),
                Auth::id()
            );

            if (!empty($validated['title'])) {
                $report->update(['title' => $validated['title']]);
            }
        } else {
            $report = $this->reportGenerator->createReport($validated, Auth::id());
        }

        if (!empty($validated['tags'])) {
            $this->reportGenerator->addTags($report, $validated['tags']);
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Rapport créé avec succès.');
    }

    /**
     * Display the specified report.
     */
    public function show(DepartmentReport $report)
    {
        $report->load([
            'department',
            'author',
            'approver',
            'template',
            'sections.comments.user',
            'sections.attachments',
            'approvals.user',
            'comments.user',
            'comments.replies.user',
            'attachments.uploader',
            'versions.creator',
            'tags',
        ]);

        $aggregatedData = null;
        if ($report->status === ReportStatus::DRAFT) {
            $aggregatedData = $this->dataAggregator->aggregateForReport($report);
        }

        return Inertia::render('Reports/Show', [
            'report' => $report,
            'aggregatedData' => $aggregatedData,
            'canEdit' => $report->can_edit && $report->author_id === Auth::id(),
            'canSubmit' => $report->can_submit && $report->author_id === Auth::id(),
            'canApprove' => $report->approvals()
                ->where('user_id', Auth::id())
                ->pending()
                ->exists(),
            'canPublish' => $report->status === ReportStatus::APPROVED && Auth::user()->can('publish reports'),
        ]);
    }

    /**
     * Show the form for editing the specified report.
     */
    public function edit(DepartmentReport $report)
    {
        if (!$report->can_edit) {
            return redirect()->route('reports.show', $report)
                ->with('error', 'Ce rapport ne peut plus être modifié.');
        }

        $report->load(['sections', 'tags', 'approvals.user']);

        return Inertia::render('Reports/Edit', [
            'report' => $report,
            'types' => ReportType::toSelectOptions(),
            'periodTypes' => ReportPeriodType::toSelectOptions(),
            'popularTags' => ReportTag::getPopularTags(20),
        ]);
    }

    /**
     * Update the specified report.
     */
    public function update(Request $request, DepartmentReport $report)
    {
        if (!$report->can_edit) {
            return back()->with('error', 'Ce rapport ne peut plus être modifié.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'executive_summary' => 'nullable|string',
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date|after_or_equal:period_start',
            'tags' => 'nullable|array',
        ]);

        $this->reportGenerator->updateReport($report, $validated);

        if (isset($validated['tags'])) {
            $report->tags()->delete();
            $this->reportGenerator->addTags($report, $validated['tags']);
        }

        return back()->with('success', 'Rapport mis à jour avec succès.');
    }

    /**
     * Update a specific section of the report.
     */
    public function updateSection(Request $request, DepartmentReport $report, ReportSection $section)
    {
        if (!$report->can_edit) {
            return back()->with('error', 'Ce rapport ne peut plus être modifié.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|array',
        ]);

        $this->reportGenerator->updateSection($section, $validated);

        return back()->with('success', 'Section mise à jour avec succès.');
    }

    /**
     * Populate report with aggregated data.
     */
    public function populate(DepartmentReport $report)
    {
        if (!$report->can_edit) {
            return back()->with('error', 'Ce rapport ne peut plus être modifié.');
        }

        $this->reportGenerator->populateWithData($report);

        return back()->with('success', 'Données agrégées avec succès.');
    }

    /**
     * Submit the report for review.
     */
    public function submit(Request $request, DepartmentReport $report)
    {
        if (!$report->can_submit) {
            return back()->with('error', 'Ce rapport ne peut pas être soumis. Veuillez compléter toutes les sections requises.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $this->reportGenerator->submitForReview($report, $validated['notes'] ?? null);

        return back()->with('success', 'Rapport soumis pour révision.');
    }

    /**
     * Approve or reject the report.
     */
    public function approve(Request $request, DepartmentReport $report)
    {
        $validated = $request->validate([
            'approved' => 'required|boolean',
            'comments' => 'nullable|string',
        ]);

        try {
            $this->reportGenerator->processApproval(
                $report,
                Auth::id(),
                $validated['approved'],
                $validated['comments'] ?? null
            );

            $message = $validated['approved']
                ? 'Rapport approuvé avec succès.'
                : 'Révision demandée pour le rapport.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Publish the report.
     */
    public function publish(DepartmentReport $report)
    {
        try {
            $this->reportGenerator->publish($report);
            return back()->with('success', 'Rapport publié avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Archive the report.
     */
    public function archive(DepartmentReport $report)
    {
        try {
            $this->reportGenerator->archive($report);
            return back()->with('success', 'Rapport archivé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate a report.
     */
    public function duplicate(DepartmentReport $report)
    {
        $newReport = $report->duplicate();

        return redirect()->route('reports.edit', $newReport)
            ->with('success', 'Rapport dupliqué avec succès.');
    }

    /**
     * Add a comment to the report.
     */
    public function addComment(Request $request, DepartmentReport $report)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'section_id' => 'nullable|exists:report_sections,id',
            'parent_id' => 'nullable|exists:report_comments,id',
            'type' => 'nullable|string',
        ]);

        ReportComment::create([
            'report_id' => $report->id,
            'section_id' => $validated['section_id'] ?? null,
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'type' => $validated['type'] ?? 'comment',
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Commentaire ajouté.');
    }

    /**
     * Resolve a comment.
     */
    public function resolveComment(ReportComment $comment)
    {
        $comment->resolve(Auth::id());
        return back()->with('success', 'Commentaire résolu.');
    }

    /**
     * Add an attachment to the report.
     */
    public function addAttachment(Request $request, DepartmentReport $report)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'section_id' => 'nullable|exists:report_sections,id',
        ]);

        $this->reportGenerator->addAttachment(
            $report,
            $request->file('file'),
            Auth::id(),
            $request->section_id
        );

        return back()->with('success', 'Fichier ajouté avec succès.');
    }

    /**
     * Remove an attachment from the report.
     */
    public function removeAttachment(DepartmentReport $report, $attachmentId)
    {
        $attachment = $report->attachments()->findOrFail($attachmentId);
        $this->reportGenerator->removeAttachment($attachment);

        return back()->with('success', 'Fichier supprimé.');
    }

    /**
     * Export the report.
     */
    public function export(Request $request, DepartmentReport $report)
    {
        $format = $request->get('format', 'json');

        if ($format === 'pdf') {
            return $this->pdfExportService->download($report);
        }

        $data = $this->reportGenerator->exportToArray($report);
        return response()->json($data);
    }

    /**
     * Generate PDF and store in department documents.
     */
    public function generatePdf(DepartmentReport $report)
    {
        try {
            $document = $this->pdfExportService->generateAndStore($report);

            return back()->with('success', 'Rapport PDF généré et stocké avec succès.')
                ->with('generated_document', [
                    'uuid' => $document->uuid,
                    'file_url' => $document->file_url,
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF (without storing).
     */
    public function downloadPdf(DepartmentReport $report): \Illuminate\Http\Response
    {
        return $this->pdfExportService->download($report);
    }

    /**
     * Stream PDF for inline viewing.
     */
    public function streamPdf(DepartmentReport $report): \Illuminate\Http\Response
    {
        return $this->pdfExportService->stream($report);
    }

    /**
     * Regenerate PDF and update stored document.
     */
    public function regeneratePdf(DepartmentReport $report)
    {
        try {
            $document = $this->pdfExportService->regenerate($report);

            return back()->with('success', 'Rapport PDF régénéré avec succès.')
                ->with('generated_document', [
                    'uuid' => $document->uuid,
                    'file_url' => $document->file_url,
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la régénération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * List all generated reports for a department.
     */
    public function listGeneratedReports(Request $request, Department $department)
    {
        $year = $request->get('year');
        $month = $request->get('month');

        $reports = $this->pdfExportService->listGeneratedReports(
            $department->id,
            $year ? (int) $year : null,
            $month ? (int) $month : null
        );

        return response()->json([
            'reports' => $reports,
            'total' => $reports->count(),
        ]);
    }

    /**
     * Get report preview data (for live preview).
     */
    public function preview(DepartmentReport $report)
    {
        return response()->json([
            'report' => $report->toExportArray(),
            'aggregatedData' => $this->dataAggregator->aggregateForReport($report),
        ]);
    }

    /**
     * Compare two versions of a report.
     */
    public function compareVersions(DepartmentReport $report, $version1, $version2)
    {
        $v1 = $report->versions()->where('version_number', $version1)->firstOrFail();
        $v2 = $report->versions()->where('version_number', $version2)->firstOrFail();

        return response()->json([
            'version1' => $v1,
            'version2' => $v2,
            'changes' => $v2->compareWith($v1),
        ]);
    }

    /**
     * Remove the specified report.
     */
    public function destroy(DepartmentReport $report)
    {
        if (!in_array($report->status, [ReportStatus::DRAFT, ReportStatus::ARCHIVED])) {
            return back()->with('error', 'Seuls les rapports en brouillon ou archivés peuvent être supprimés.');
        }

        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Rapport supprimé avec succès.');
    }
}
