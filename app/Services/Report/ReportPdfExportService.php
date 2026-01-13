<?php

namespace App\Services\Report;

use App\Models\DepartmentDocument;
use App\Models\DepartmentReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportPdfExportService
{
    protected const REPORTS_SUBFOLDER = 'rapports';

    public function __construct(
        protected ReportDataAggregatorService $aggregator
    ) {}

    /**
     * Generate PDF and store it in department documents.
     */
    public function generateAndStore(DepartmentReport $report): DepartmentDocument
    {
        $report->load([
            'department',
            'author',
            'approver',
            'sections.comments',
            'tags',
        ]);

        // Get aggregated data for the report
        $aggregatedData = $this->aggregator->aggregateForReport($report);

        // Generate PDF content
        $pdf = $this->generatePdf($report, $aggregatedData);

        // Store in documents folder with proper structure
        $document = $this->storeInDocuments($report, $pdf);

        // Update report metadata with document reference
        $report->update([
            'metadata' => array_merge($report->metadata ?? [], [
                'generated_document_id' => $document->id,
                'generated_document_uuid' => $document->uuid,
                'generated_at' => now()->toIso8601String(),
            ]),
        ]);

        return $document;
    }

    /**
     * Generate PDF from report data.
     */
    public function generatePdf(DepartmentReport $report, array $aggregatedData): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'report' => $report,
            'department' => $report->department,
            'author' => $report->author,
            'sections' => $report->sections,
            'aggregatedData' => $aggregatedData,
            'generatedAt' => now(),
        ];

        return Pdf::loadView('reports.pdf.report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Store PDF in department documents structure.
     * Structure: departments/{dept_id}/{year}/{month}/rapports/{filename}.pdf
     */
    protected function storeInDocuments(DepartmentReport $report, \Barryvdh\DomPDF\PDF $pdf): DepartmentDocument
    {
        $department = $report->department;
        $periodStart = Carbon::parse($report->period_start);

        $year = $periodStart->year;
        $month = $periodStart->month;

        // Generate unique filename
        $sanitizedTitle = Str::slug($report->title);
        $timestamp = now()->format('Ymd_His');
        $filename = "{$sanitizedTitle}_{$timestamp}.pdf";

        // Build path: departments/{dept_id}/{year}/{month}/rapports/
        $basePath = "departments/{$department->id}/{$year}/{$month}/" . self::REPORTS_SUBFOLDER;
        $fullPath = "{$basePath}/{$filename}";

        // Ensure directory exists and store PDF
        Storage::disk('public')->put($fullPath, $pdf->output());

        // Create department document record
        return DepartmentDocument::create([
            'department_id' => $department->id,
            'uploaded_by' => $report->author_id,
            'original_name' => $this->generateOriginalName($report),
            'file_name' => $filename,
            'file_path' => $fullPath,
            'mime_type' => 'application/pdf',
            'file_size' => Storage::disk('public')->size($fullPath),
            'extension' => 'pdf',
            'year' => $year,
            'month' => $month,
            'title' => $report->title,
            'description' => $this->generateDescription($report),
            'category' => self::REPORTS_SUBFOLDER,
        ]);
    }

    /**
     * Generate original filename for the report.
     */
    protected function generateOriginalName(DepartmentReport $report): string
    {
        $periodLabel = $report->period_label;
        return "Rapport - {$report->title} - {$periodLabel}.pdf";
    }

    /**
     * Generate description for the document.
     */
    protected function generateDescription(DepartmentReport $report): string
    {
        return sprintf(
            "Rapport %s généré automatiquement le %s. Période: %s - %s. Auteur: %s",
            $report->type->label(),
            now()->format('d/m/Y à H:i'),
            Carbon::parse($report->period_start)->format('d/m/Y'),
            Carbon::parse($report->period_end)->format('d/m/Y'),
            $report->author?->full_name ?? 'Système'
        );
    }

    /**
     * Download PDF without storing (for preview).
     */
    public function download(DepartmentReport $report): \Illuminate\Http\Response
    {
        $report->load([
            'department',
            'author',
            'approver',
            'sections.comments',
            'tags',
        ]);

        $aggregatedData = $this->aggregator->aggregateForReport($report);
        $pdf = $this->generatePdf($report, $aggregatedData);

        $filename = Str::slug($report->title) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Stream PDF for inline viewing.
     */
    public function stream(DepartmentReport $report): \Illuminate\Http\Response
    {
        $report->load([
            'department',
            'author',
            'approver',
            'sections.comments',
            'tags',
        ]);

        $aggregatedData = $this->aggregator->aggregateForReport($report);
        $pdf = $this->generatePdf($report, $aggregatedData);

        return $pdf->stream($report->title . '.pdf');
    }

    /**
     * Get the reports folder path for a department and period.
     */
    public static function getReportsFolderPath(int $departmentId, int $year, int $month): string
    {
        return "departments/{$departmentId}/{$year}/{$month}/" . self::REPORTS_SUBFOLDER;
    }

    /**
     * List all generated reports for a department.
     */
    public function listGeneratedReports(int $departmentId, ?int $year = null, ?int $month = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = DepartmentDocument::where('department_id', $departmentId)
            ->where('category', self::REPORTS_SUBFOLDER)
            ->with('uploader');

        if ($year) {
            $query->where('year', $year);
        }

        if ($month) {
            $query->where('month', $month);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Delete a generated report document.
     */
    public function deleteGeneratedReport(DepartmentDocument $document): bool
    {
        if ($document->category !== self::REPORTS_SUBFOLDER) {
            throw new \InvalidArgumentException('Ce document n\'est pas un rapport généré.');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Delete record
        return $document->delete();
    }

    /**
     * Regenerate PDF for an existing report.
     */
    public function regenerate(DepartmentReport $report): DepartmentDocument
    {
        // Delete old generated document if exists
        if (isset($report->metadata['generated_document_id'])) {
            $oldDocument = DepartmentDocument::find($report->metadata['generated_document_id']);
            if ($oldDocument) {
                $this->deleteGeneratedReport($oldDocument);
            }
        }

        // Generate new document
        return $this->generateAndStore($report);
    }
}
