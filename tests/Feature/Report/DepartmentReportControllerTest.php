<?php

namespace Tests\Feature\Report;

use App\Enums\Report\ReportStatus;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentReport;
use App\Models\User;
use App\Services\Report\ReportPdfExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create permissions
        $this->createReportPermissions();

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('member');

        $this->department = Department::factory()->active()->create();
    }

    protected function createReportPermissions(): void
    {
        $permissions = [
            'view reports',
            'create reports',
            'edit reports',
            'delete reports',
            'approve reports',
            'publish reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->givePermissionTo(['view reports']);
    }

    // ========================================
    // SHOW TESTS
    // ========================================

    public function test_show_displays_report_details(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('reports.show', $report));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Reports/Show')
                ->has('report')
                ->has('canEdit')
                ->has('canSubmit')
                ->has('canApprove')
                ->has('canPublish')
        );
    }

    // ========================================
    // PDF GENERATION TESTS (using Service directly)
    // These tests verify core PDF functionality
    // ========================================

    public function test_generate_pdf_creates_document_in_department_documents(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly(Carbon::create(2024, 3, 15))
            ->create();

        $pdfService = app(ReportPdfExportService::class);
        $document = $pdfService->generateAndStore($report);

        $this->assertInstanceOf(DepartmentDocument::class, $document);
        $this->assertEquals($this->department->id, $document->department_id);
        $this->assertEquals('rapports', $document->category);
        $this->assertEquals(2024, $document->year);
        $this->assertEquals(3, $document->month);

        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_generate_pdf_stores_file_in_correct_folder(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly(Carbon::create(2024, 6, 15))
            ->create();

        $pdfService = app(ReportPdfExportService::class);
        $document = $pdfService->generateAndStore($report);

        $expectedPathPrefix = "departments/{$this->department->id}/2024/6/rapports";
        $this->assertStringStartsWith($expectedPathPrefix, $document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_download_pdf_returns_pdf_file(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly()
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('reports.download-pdf', $report));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_stream_pdf_returns_pdf_inline(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly()
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('reports.stream-pdf', $report));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_regenerate_pdf_replaces_existing_document(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly(Carbon::create(2024, 1, 15))
            ->create();

        $pdfService = app(ReportPdfExportService::class);

        // Generate first PDF
        $firstDocument = $pdfService->generateAndStore($report);
        $firstDocumentId = $firstDocument->id;
        $firstFilePath = $firstDocument->file_path;

        Storage::disk('public')->assertExists($firstFilePath);
        $this->assertNotNull(DepartmentDocument::find($firstDocumentId));

        $report->refresh();

        // Regenerate PDF
        $secondDocument = $pdfService->regenerate($report);

        // Old document should be soft deleted
        $this->assertNull(DepartmentDocument::find($firstDocumentId));
        $this->assertNotNull(DepartmentDocument::withTrashed()->find($firstDocumentId));

        // New document should exist
        $this->assertInstanceOf(DepartmentDocument::class, $secondDocument);
        $this->assertNotEquals($firstDocumentId, $secondDocument->id);
        Storage::disk('public')->assertExists($secondDocument->file_path);

        // Report metadata should be updated
        $report->refresh();
        $this->assertEquals($secondDocument->id, $report->metadata['generated_document_id']);
    }

    public function test_list_generated_reports_returns_json(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->forPeriod(2024, 3)
            ->count(3)
            ->create(['category' => 'rapports']);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.generated-reports', [
                'department' => $this->department,
                'year' => 2024,
                'month' => 3,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 3,
        ]);
        $response->assertJsonCount(3, 'reports');
    }

    // ========================================
    // BUSINESS LOGIC TESTS
    // ========================================

    public function test_draft_report_can_be_edited(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->draft()
            ->create();

        $this->assertTrue($report->can_edit);
    }

    public function test_published_report_cannot_be_edited(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->published()
            ->create();

        $this->assertFalse($report->can_edit);
    }

    public function test_report_progress_calculation(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->create();

        // Without sections, progress should be 0
        $this->assertEquals(0, $report->progress);
    }

    public function test_report_period_label_for_monthly(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->monthly(Carbon::create(2024, 3, 15))
            ->create();

        $this->assertNotEmpty($report->period_label);
    }

    public function test_report_period_label_for_quarterly(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->quarterly(Carbon::create(2024, 4, 15))
            ->create();

        $this->assertStringContainsString('T2', $report->period_label);
    }

    public function test_report_period_label_for_annual(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->admin)
            ->annual(2024)
            ->create();

        $this->assertEquals('2024', $report->period_label);
    }
}
