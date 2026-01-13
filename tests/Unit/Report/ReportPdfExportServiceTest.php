<?php

namespace Tests\Unit\Report;

use App\Enums\Report\ReportPeriodType;
use App\Enums\Report\ReportStatus;
use App\Enums\Report\ReportType;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentReport;
use App\Models\User;
use App\Services\Report\ReportDataAggregatorService;
use App\Services\Report\ReportPdfExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportPdfExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportPdfExportService $service;
    protected ReportDataAggregatorService $aggregator;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->department = Department::factory()->active()->create();
        $this->aggregator = new ReportDataAggregatorService();
        $this->service = new ReportPdfExportService($this->aggregator);
    }

    public function test_generates_correct_reports_folder_path(): void
    {
        $path = ReportPdfExportService::getReportsFolderPath(1, 2024, 3);

        $this->assertEquals('departments/1/2024/3/rapports', $path);
    }

    public function test_generates_correct_reports_folder_path_for_different_departments(): void
    {
        $path1 = ReportPdfExportService::getReportsFolderPath(5, 2023, 12);
        $path2 = ReportPdfExportService::getReportsFolderPath(10, 2024, 1);

        $this->assertEquals('departments/5/2023/12/rapports', $path1);
        $this->assertEquals('departments/10/2024/1/rapports', $path2);
    }

    public function test_generate_and_store_creates_pdf_document(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 6, 15))
            ->create([
                'title' => 'Rapport Test Juin 2024',
            ]);

        $document = $this->service->generateAndStore($report);

        $this->assertInstanceOf(DepartmentDocument::class, $document);
        $this->assertEquals($this->department->id, $document->department_id);
        $this->assertEquals('rapports', $document->category);
        $this->assertEquals('application/pdf', $document->mime_type);
        $this->assertEquals('pdf', $document->extension);
        $this->assertEquals(2024, $document->year);
        $this->assertEquals(6, $document->month);
    }

    public function test_generated_pdf_stored_in_correct_path(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 3, 15))
            ->create([
                'title' => 'Rapport Mars 2024',
            ]);

        $document = $this->service->generateAndStore($report);

        $expectedBasePath = "departments/{$this->department->id}/2024/3/rapports";
        $this->assertStringStartsWith($expectedBasePath, $document->file_path);
        $this->assertStringEndsWith('.pdf', $document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_report_metadata_updated_after_pdf_generation(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create();

        $document = $this->service->generateAndStore($report);

        $report->refresh();
        $this->assertArrayHasKey('generated_document_id', $report->metadata);
        $this->assertArrayHasKey('generated_document_uuid', $report->metadata);
        $this->assertArrayHasKey('generated_at', $report->metadata);
        $this->assertEquals($document->id, $report->metadata['generated_document_id']);
        $this->assertEquals($document->uuid, $report->metadata['generated_document_uuid']);
    }

    public function test_generated_document_has_correct_title_and_description(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 1, 15))
            ->ofType(ReportType::MONTHLY_ACTIVITY)
            ->create([
                'title' => 'Mon Rapport Test',
            ]);

        $document = $this->service->generateAndStore($report);

        $this->assertEquals('Mon Rapport Test', $document->title);
        $this->assertStringContainsString('Rapport', $document->description);
        $this->assertStringContainsString($this->user->full_name, $document->description);
    }

    public function test_list_generated_reports_returns_only_report_documents(): void
    {
        // Create some regular documents
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->count(3)
            ->create(['category' => 'meeting']);

        // Create report documents
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->count(2)
            ->create(['category' => 'rapports']);

        $reports = $this->service->listGeneratedReports($this->department->id);

        $this->assertCount(2, $reports);
        $reports->each(fn ($doc) => $this->assertEquals('rapports', $doc->category));
    }

    public function test_list_generated_reports_can_filter_by_year(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->forPeriod(2024, 1)
            ->create(['category' => 'rapports']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->forPeriod(2023, 6)
            ->create(['category' => 'rapports']);

        $reports2024 = $this->service->listGeneratedReports($this->department->id, 2024);
        $reports2023 = $this->service->listGeneratedReports($this->department->id, 2023);

        $this->assertCount(1, $reports2024);
        $this->assertCount(1, $reports2023);
        $this->assertEquals(2024, $reports2024->first()->year);
        $this->assertEquals(2023, $reports2023->first()->year);
    }

    public function test_list_generated_reports_can_filter_by_year_and_month(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->forPeriod(2024, 3)
            ->create(['category' => 'rapports']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->forPeriod(2024, 6)
            ->create(['category' => 'rapports']);

        $marchReports = $this->service->listGeneratedReports($this->department->id, 2024, 3);
        $juneReports = $this->service->listGeneratedReports($this->department->id, 2024, 6);

        $this->assertCount(1, $marchReports);
        $this->assertCount(1, $juneReports);
        $this->assertEquals(3, $marchReports->first()->month);
        $this->assertEquals(6, $juneReports->first()->month);
    }

    public function test_delete_generated_report_removes_file_and_record(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create();

        $document = $this->service->generateAndStore($report);
        $filePath = $document->file_path;
        $documentId = $document->id;

        Storage::disk('public')->assertExists($filePath);

        $result = $this->service->deleteGeneratedReport($document);

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($filePath);
        $this->assertNull(DepartmentDocument::find($documentId));
    }

    public function test_delete_generated_report_throws_exception_for_non_report_documents(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->create(['category' => 'meeting']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("n'est pas un rapport généré");

        $this->service->deleteGeneratedReport($document);
    }

    public function test_regenerate_replaces_old_document(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create();

        // Generate first PDF
        $firstDocument = $this->service->generateAndStore($report);
        $firstDocumentId = $firstDocument->id;
        $firstFilePath = $firstDocument->file_path;

        // Verify first document exists
        Storage::disk('public')->assertExists($firstFilePath);
        $this->assertNotNull(DepartmentDocument::find($firstDocumentId));

        // Refresh report to get updated metadata
        $report->refresh();

        // Verify metadata contains document reference
        $this->assertEquals($firstDocumentId, $report->metadata['generated_document_id']);

        // Regenerate PDF
        $secondDocument = $this->service->regenerate($report);

        // Old document should be soft deleted (uses SoftDeletes trait)
        $this->assertNull(DepartmentDocument::find($firstDocumentId));
        $this->assertNotNull(DepartmentDocument::withTrashed()->find($firstDocumentId));

        // New document should exist and be different from old
        $this->assertInstanceOf(DepartmentDocument::class, $secondDocument);
        $this->assertNotEquals($firstDocumentId, $secondDocument->id);
        Storage::disk('public')->assertExists($secondDocument->file_path);

        // Report metadata should be updated with new document reference
        $report->refresh();
        $this->assertEquals($secondDocument->id, $report->metadata['generated_document_id']);
    }

    public function test_generate_pdf_returns_pdf_instance(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create();

        $report->load(['department', 'author', 'approver', 'sections.comments', 'tags']);
        $aggregatedData = $this->aggregator->aggregateForReport($report);

        $pdf = $this->service->generatePdf($report, $aggregatedData);

        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_download_returns_response(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create(['title' => 'Test Download']);

        $response = $this->service->download($report);

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_stream_returns_response(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly()
            ->create(['title' => 'Test Stream']);

        $response = $this->service->stream($report);

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_generated_filename_is_sanitized(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 1, 15))
            ->create([
                'title' => 'Rapport: Test & Validation (Q1/2024)',
            ]);

        $document = $this->service->generateAndStore($report);

        // Filename should be sanitized (slug format)
        $this->assertStringContainsString('rapport-test-validation', $document->file_name);
        $this->assertStringNotContainsString(':', $document->file_name);
        $this->assertStringNotContainsString('&', $document->file_name);
        $this->assertStringNotContainsString('(', $document->file_name);
    }

    public function test_multiple_reports_for_same_period_have_unique_filenames(): void
    {
        $report1 = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 1, 15))
            ->create(['title' => 'Rapport A']);

        $report2 = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly(Carbon::create(2024, 1, 15))
            ->create(['title' => 'Rapport B']);

        $doc1 = $this->service->generateAndStore($report1);

        // Small delay to ensure different timestamp
        sleep(1);

        $doc2 = $this->service->generateAndStore($report2);

        $this->assertNotEquals($doc1->file_name, $doc2->file_name);
        $this->assertNotEquals($doc1->file_path, $doc2->file_path);
    }

    public function test_quarterly_report_uses_correct_period_dates(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->quarterly(Carbon::create(2024, 4, 15)) // Q2 2024
            ->create();

        $document = $this->service->generateAndStore($report);

        // Q2 starts in April
        $this->assertEquals(2024, $document->year);
        $this->assertEquals(4, $document->month);
    }

    public function test_annual_report_uses_correct_period_dates(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->annual(2023)
            ->create();

        $document = $this->service->generateAndStore($report);

        $this->assertEquals(2023, $document->year);
        $this->assertEquals(1, $document->month); // Year starts in January
    }
}
