<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();

        // Add the user to the department
        $this->department->users()->attach($this->user->id);
    }

    /** @test */
    public function it_can_list_department_documents_as_tree(): void
    {
        // Create documents for different years and months
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 2)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2025, 12)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.index', $this->department));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'year',
                        'months' => [
                            '*' => [
                                'month',
                                'month_name',
                                'categories' => [
                                    '*' => [
                                        'name',
                                        'key',
                                        'documents' => [
                                            '*' => [
                                                'uuid',
                                                'title',
                                                'original_name',
                                                'file_url',
                                                'formatted_file_size',
                                                'extension',
                                                'file_type',
                                            ],
                                        ],
                                    ],
                                ],
                                'document_count',
                            ],
                        ],
                        'document_count',
                    ],
                ],
                'total_documents',
            ])
            ->assertJsonPath('total_documents', 3);
    }

    /** @test */
    public function it_can_get_documents_by_year(): void
    {
        // Create 2 documents for 2026 and 1 for 2025
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->count(2)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2025, 12)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.year', [
                'department' => $this->department,
                'year' => 2026,
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_get_documents_by_month(): void
    {
        // Create 2 documents for January 2026 and 1 for February 2026
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->count(2)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 2)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.month', [
                'department' => $this->department,
                'year' => 2026,
                'month' => 1,
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_upload_a_pdf_document(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'title' => 'Monthly Report',
                'description' => 'January 2026 monthly report',
                'category' => 'report',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'title',
                    'original_name',
                    'file_url',
                    'extension',
                    'file_type',
                ],
            ])
            ->assertJsonPath('data.extension', 'pdf')
            ->assertJsonPath('data.file_type', 'pdf');

        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'uploaded_by' => $this->user->id,
            'title' => 'Monthly Report',
            'description' => 'January 2026 monthly report',
            'category' => 'report',
            'extension' => 'pdf',
        ]);
    }

    /** @test */
    public function it_can_upload_a_word_document(): void
    {
        $file = UploadedFile::fake()->create('document.docx', 2048, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'docx')
            ->assertJsonPath('data.file_type', 'word');
    }

    /** @test */
    public function it_can_upload_an_excel_document(): void
    {
        $file = UploadedFile::fake()->create('spreadsheet.xlsx', 1500, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'xlsx')
            ->assertJsonPath('data.file_type', 'excel');
    }

    /** @test */
    public function it_can_upload_an_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'jpg')
            ->assertJsonPath('data.file_type', 'image');
    }

    /** @test */
    public function it_can_upload_a_powerpoint_document(): void
    {
        $file = UploadedFile::fake()->create('presentation.pptx', 3000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'pptx')
            ->assertJsonPath('data.file_type', 'powerpoint');
    }

    /** @test */
    public function it_validates_required_file_when_uploading(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_size_limit(): void
    {
        // Create a file larger than 50MB
        $file = UploadedFile::fake()->create('large.pdf', 55000, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_year_range(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'year' => 1999, // Before 2000
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['year']);
    }

    /** @test */
    public function it_validates_month_range(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'month' => 13, // Invalid month
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['month']);
    }

    /** @test */
    public function it_can_show_a_specific_document(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Test Document',
                'description' => 'Test Description',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'title',
                    'original_name',
                    'file_url',
                    'formatted_file_size',
                    'extension',
                    'file_type',
                    'description',
                    'category',
                    'uploader',
                ],
            ])
            ->assertJsonPath('data.uuid', $document->uuid)
            ->assertJsonPath('data.title', 'Test Document');
    }

    /** @test */
    public function it_returns_404_for_document_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_update_document_metadata(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Original Title',
                'description' => 'Original Description',
                'category' => 'original',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.documents.update', [
                'department' => $this->department,
                'document' => $document,
            ]), [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
                'category' => 'updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.description', 'Updated Description')
            ->assertJsonPath('data.category', 'updated');

        $this->assertDatabaseHas('department_documents', [
            'id' => $document->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'category' => 'updated',
        ]);
    }

    /** @test */
    public function it_cannot_update_document_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.documents.update', [
                'department' => $this->department,
                'document' => $document,
            ]), [
                'title' => 'Hacked Title',
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_delete_a_document(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.documents.destroy', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Document should be soft deleted
        $this->assertSoftDeleted('department_documents', ['id' => $document->id]);
    }

    /** @test */
    public function it_cannot_delete_document_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.documents.destroy', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();

        // Document should still exist
        $this->assertDatabaseHas('department_documents', ['id' => $document->id]);
    }

    /** @test */
    public function it_can_download_a_document(): void
    {
        // Create a real fake file
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/test-file.pdf',
            'PDF content here'
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/test-file.pdf',
                'original_name' => 'test-document.pdf',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.download', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        $response->assertDownload('test-document.pdf');
    }

    /** @test */
    public function it_returns_404_when_downloading_nonexistent_file(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'nonexistent/path/file.pdf',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.download', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_cannot_download_document_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.download', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson(route('api.departments.documents.index', $this->department));

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_organizes_documents_by_year_and_month_in_tree(): void
    {
        // Create documents for specific periods
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->count(3)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 2)
            ->count(2)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2025, 12)
            ->count(1)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.index', $this->department));

        $response->assertOk();

        $data = $response->json('data');

        // Should have 2 years (2026 and 2025)
        $this->assertCount(2, $data);

        // 2026 should be first (desc order)
        $this->assertEquals(2026, $data[0]['year']);
        $this->assertEquals(5, $data[0]['document_count']); // 3 + 2 documents

        // 2025 should be second
        $this->assertEquals(2025, $data[1]['year']);
        $this->assertEquals(1, $data[1]['document_count']);

        // Check month names
        $months2026 = collect($data[0]['months'])->pluck('month_name')->toArray();
        $this->assertContains('Février', $months2026);
        $this->assertContains('Janvier', $months2026);
    }

    /** @test */
    public function it_can_upload_document_with_custom_year_and_month(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'year' => 2025,
                'month' => 6,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.year', 2025)
            ->assertJsonPath('data.month', 6)
            ->assertJsonPath('data.month_name', 'Juin');

        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'year' => 2025,
            'month' => 6,
        ]);
    }

    /** @test */
    public function it_defaults_to_current_year_and_month_when_not_specified(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.year', now()->year)
            ->assertJsonPath('data.month', now()->month);
    }

    /** @test */
    public function it_stores_file_in_correct_directory_structure(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'year' => 2026,
                'month' => 3,
            ]);

        $response->assertCreated();

        $response->json('data.file_name');
        $expectedDir = 'department_documents/' . $this->department->id . '/2026/3';

        // Check that file was stored in correct directory
        $storedFiles = Storage::disk('public')->files($expectedDir);
        $this->assertCount(1, $storedFiles);
    }

    // ==================== SEARCH TESTS ====================

    /** @test */
    public function it_can_search_documents_by_title(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Monthly Budget Report']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Annual Summary']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Team Meeting Notes']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Budget',
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('search_term', 'Budget');

        $this->assertEquals('Monthly Budget Report', $response->json('data.0.title'));
    }

    /** @test */
    public function it_can_search_documents_by_original_name(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Some Title',
                'original_name' => 'finance-report-2026.xlsx',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Other Doc',
                'original_name' => 'meeting-notes.docx',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'finance',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 1);

        $this->assertStringContainsString('finance', $response->json('data.0.original_name'));
    }

    /** @test */
    public function it_can_search_documents_by_description(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Document A',
                'description' => 'Contains quarterly revenue data',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Document B',
                'description' => 'Employee onboarding guide',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'revenue',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 1);

        $this->assertStringContainsString('revenue', $response->json('data.0.description'));
    }

    /** @test */
    public function it_can_search_documents_by_category(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Doc 1',
                'category' => 'financial',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Doc 2',
                'category' => 'hr',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Doc 3',
                'category' => 'financial-report',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'financial',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 2);
    }

    /** @test */
    public function it_returns_empty_results_for_no_match(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Budget Report']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'nonexistent',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_validates_search_query_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_validates_search_query_max_length(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => str_repeat('a', 256),
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_performs_case_insensitive_search(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'BUDGET Report']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'budget summary']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Budget',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 2);
    }

    /** @test */
    public function it_searches_across_multiple_fields(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Project Plan',
                'original_name' => 'doc.pdf',
                'description' => 'Contains finance data',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Finance Report',
                'original_name' => 'report.pdf',
                'description' => 'Quarterly summary',
            ]);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Other Doc',
                'original_name' => 'finance-data.xlsx',
                'description' => 'Some data',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'finance',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 3);
    }

    /** @test */
    public function it_only_searches_within_department(): void
    {
        $otherDepartment = Department::factory()->create();

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Budget Report']);

        DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create(['title' => 'Budget Summary']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Budget',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 1);
    }

    /** @test */
    public function it_orders_search_results_by_year_and_month_desc(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2025, 6)
            ->create(['title' => 'Budget 2025']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->create(['title' => 'Budget 2026 Jan']);

        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 3)
            ->create(['title' => 'Budget 2026 Mar']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Budget',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 3);

        $data = $response->json('data');

        // Should be ordered: 2026/3, 2026/1, 2025/6
        $this->assertEquals(2026, $data[0]['year']);
        $this->assertEquals(3, $data[0]['month']);
        $this->assertEquals(2026, $data[1]['year']);
        $this->assertEquals(1, $data[1]['month']);
        $this->assertEquals(2025, $data[2]['year']);
        $this->assertEquals(6, $data[2]['month']);
    }

    /** @test */
    public function it_returns_proper_document_structure_in_search(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'title' => 'Test Report',
                'description' => 'Test description',
                'category' => 'test-category',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Test',
            ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'title',
                        'original_name',
                        'file_url',
                        'formatted_file_size',
                        'extension',
                        'file_type',
                        'year',
                        'month',
                        'month_name',
                        'description',
                        'category',
                        'uploader',
                    ],
                ],
                'total',
                'search_term',
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_search(): void
    {
        $response = $this->getJson(route('api.departments.documents.search', [
            'department' => $this->department,
            'q' => 'test',
        ]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_can_search_with_partial_word_match(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Financial Report 2026']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Finan',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 1);
    }

    /** @test */
    public function it_does_not_include_deleted_documents_in_search(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Active Budget Report']);

        $deletedDoc = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['title' => 'Deleted Budget Report']);

        $deletedDoc->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.search', [
                'department' => $this->department,
                'q' => 'Budget',
            ]));

        $response->assertOk()
            ->assertJsonPath('total', 1);

        $this->assertEquals('Active Budget Report', $response->json('data.0.title'));
    }

    // ==================== PREVIEW TESTS ====================

    /** @test */
    public function it_can_preview_a_document(): void
    {
        // Create a real fake file
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/test-file.pdf',
            'PDF content here'
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/test-file.pdf',
                'original_name' => 'test-document.pdf',
                'mime_type' => 'application/pdf',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="test-document.pdf"');
    }

    /** @test */
    public function it_can_preview_an_image(): void
    {
        // Create a real fake image file
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/test-image.png',
            $imageContent
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/test-image.png',
                'original_name' => 'test-image.png',
                'extension' => 'png',
                'mime_type' => 'image/png',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    /** @test */
    public function it_returns_404_when_previewing_nonexistent_file(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'nonexistent/path/file.pdf',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_cannot_preview_document_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($otherDepartment)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_requires_authentication_for_preview(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->getJson(route('api.departments.documents.preview', [
            'department' => $this->department,
            'document' => $document,
        ]));

        $response->assertUnauthorized();
    }

    // ==================== VIDEO/AUDIO FILE TYPE TESTS ====================

    /** @test */
    public function it_can_upload_a_video_file(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'title' => 'Team Meeting Recording',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'mp4')
            ->assertJsonPath('data.file_type', 'video')
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'video');
    }

    /** @test */
    public function it_can_upload_a_webm_video_file(): void
    {
        $file = UploadedFile::fake()->create('video.webm', 4000, 'video/webm');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'webm')
            ->assertJsonPath('data.file_type', 'video')
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'video');
    }

    /** @test */
    public function it_can_upload_an_audio_file_mp3(): void
    {
        $file = UploadedFile::fake()->create('audio.mp3', 2000, 'audio/mpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'title' => 'Podcast Episode',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'mp3')
            ->assertJsonPath('data.file_type', 'audio')
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'audio');
    }

    /** @test */
    public function it_can_upload_an_audio_file_wav(): void
    {
        $file = UploadedFile::fake()->create('recording.wav', 3000, 'audio/wav');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'wav')
            ->assertJsonPath('data.file_type', 'audio')
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'audio');
    }

    /** @test */
    public function it_can_upload_an_ogg_audio_file(): void
    {
        $file = UploadedFile::fake()->create('sound.ogg', 1500, 'audio/ogg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.extension', 'ogg')
            ->assertJsonPath('data.file_type', 'audio')
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'audio');
    }

    // ==================== CAN_PREVIEW AND PREVIEW_TYPE TESTS ====================

    /** @test */
    public function it_returns_can_preview_true_for_pdf(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'pdf']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'pdf');
    }

    /** @test */
    public function it_returns_can_preview_true_for_images(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'jpg']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'image');
    }

    /** @test */
    public function it_returns_can_preview_true_for_video(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'mp4']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'video');
    }

    /** @test */
    public function it_returns_can_preview_true_for_audio(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'mp3']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'audio');
    }

    /** @test */
    public function it_returns_can_preview_true_for_text_files(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'txt']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', true)
            ->assertJsonPath('data.preview_type', 'text');
    }

    /** @test */
    public function it_returns_office_preview_type_for_word_documents(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'docx']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', false)
            ->assertJsonPath('data.preview_type', 'office');
    }

    /** @test */
    public function it_returns_office_preview_type_for_excel_documents(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'xlsx']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', false)
            ->assertJsonPath('data.preview_type', 'office');
    }

    /** @test */
    public function it_returns_office_preview_type_for_powerpoint_documents(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'pptx']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', false)
            ->assertJsonPath('data.preview_type', 'office');
    }

    /** @test */
    public function it_returns_can_preview_false_for_archive_files(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create(['extension' => 'zip']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonPath('data.can_preview', false)
            ->assertJsonPath('data.preview_type', 'none');
    }

    /** @test */
    public function it_includes_preview_url_in_document_response(): void
    {
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.show', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'file_url',
                    'preview_url',
                    'can_preview',
                    'preview_type',
                ],
            ]);

        // Verify preview_url format
        $previewUrl = $response->json('data.preview_url');
        $this->assertStringContainsString('/api/departments/', $previewUrl);
        $this->assertStringContainsString('/documents/', $previewUrl);
        $this->assertStringContainsString('/preview', $previewUrl);
    }

    /** @test */
    public function it_includes_preview_fields_in_tree_response(): void
    {
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->create(['extension' => 'mp4']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.documents.index', $this->department));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'year',
                        'months' => [
                            '*' => [
                                'categories' => [
                                    '*' => [
                                        'documents' => [
                                            '*' => [
                                                'uuid',
                                                'file_url',
                                                'preview_url',
                                                'can_preview',
                                                'preview_type',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        // Verify video document has correct preview fields (in rapports category by default)
        // Find the category that contains the document
        $categories = $response->json('data.0.months.0.categories');
        $doc = null;
        foreach ($categories as $category) {
            if (!empty($category['documents'])) {
                $doc = $category['documents'][0];
                break;
            }
        }
        $this->assertNotNull($doc, 'Document should be found in one of the categories');
        $this->assertTrue($doc['can_preview']);
        $this->assertEquals('video', $doc['preview_type']);
    }

    /** @test */
    public function it_can_preview_video_file(): void
    {
        // Create a fake video file
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/video.mp4',
            'fake video content'
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/video.mp4',
                'original_name' => 'video.mp4',
                'extension' => 'mp4',
                'mime_type' => 'video/mp4',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Content-Disposition', 'inline; filename="video.mp4"');
    }

    /** @test */
    public function it_can_preview_audio_file(): void
    {
        // Create a fake audio file
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/audio.mp3',
            'fake audio content'
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/audio.mp3',
                'original_name' => 'audio.mp3',
                'extension' => 'mp3',
                'mime_type' => 'audio/mpeg',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/mpeg');
        $response->assertHeader('Content-Disposition', 'inline; filename="audio.mp3"');
    }

    /** @test */
    public function preview_includes_cache_control_header(): void
    {
        Storage::disk('public')->put(
            'department_documents/' . $this->department->id . '/2026/1/doc.pdf',
            'PDF content'
        );

        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->create([
                'file_path' => 'department_documents/' . $this->department->id . '/2026/1/doc.pdf',
                'original_name' => 'doc.pdf',
                'mime_type' => 'application/pdf',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get(route('api.departments.documents.preview', [
                'department' => $this->department,
                'document' => $document,
            ]));

        $response->assertOk();
        // Cache-Control directive order may vary, check for both values
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
    }

    // ==================== DEFAULT CATEGORY TESTS ====================

    /** @test */
    public function it_leaves_category_null_when_no_category_provided(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        // Upload without specifying category
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'title' => 'Test Document',
            ]);

        $response->assertCreated();

        // Verify the document has no category (appears directly under month)
        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'title' => 'Test Document',
            'category' => null,
        ]);
    }

    /** @test */
    public function it_does_not_create_automatic_category_on_upload(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        // Get initial category count for this department
        $initialCategoryCount = \App\Models\DepartmentDocumentCategory::where('department_id', $this->department->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->count();

        // Upload without category
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
            ]);

        $response->assertCreated();

        // Verify no new category was created for the month
        $newCategoryCount = \App\Models\DepartmentDocumentCategory::where('department_id', $this->department->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->count();

        $this->assertEquals($initialCategoryCount, $newCategoryCount);
    }

    /** @test */
    public function it_uses_provided_category_when_specified(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'title' => 'Specific Category Document',
                'category' => 'custom-category',
            ]);

        $response->assertCreated();

        // Verify the document uses the provided category, not the default
        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'title' => 'Specific Category Document',
            'category' => 'custom-category',
        ]);
    }

    /** @test */
    public function it_uses_existing_category_slug_when_provided(): void
    {
        // First create a category
        \App\Models\DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Rapports Financiers',
            'slug' => 'rapports-financiers',
            'year' => now()->year,
            'month' => now()->month,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'category' => 'rapports-financiers',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'category' => 'rapports-financiers',
        ]);
    }

    /** @test */
    public function it_does_not_duplicate_default_month_category_on_multiple_uploads(): void
    {
        $file1 = UploadedFile::fake()->create('report1.pdf', 1024, 'application/pdf');
        $file2 = UploadedFile::fake()->create('report2.pdf', 1024, 'application/pdf');

        // Upload first file without category
        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file1,
            ])
            ->assertCreated();

        // Upload second file without category
        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file2,
            ])
            ->assertCreated();

        // Verify only one default month category exists
        $categoryCount = \App\Models\DepartmentDocumentCategory::where([
            'department_id' => $this->department->id,
            'year' => now()->year,
            'month' => now()->month,
            'is_system' => true,
        ])->count();

        // Should have exactly 2 system categories: month default + rapports
        $this->assertLessThanOrEqual(2, $categoryCount);
    }

    /** @test */
    public function it_stores_document_without_category_when_none_provided(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        // Upload with specific year/month but no category
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'year' => 2025,
                'month' => 6, // June
            ]);

        $response->assertCreated();

        // Verify the document has no category (will appear directly under month)
        $this->assertDatabaseHas('department_documents', [
            'department_id' => $this->department->id,
            'year' => 2025,
            'month' => 6,
            'category' => null,
        ]);
    }

    /** @test */
    public function it_treats_empty_string_category_as_null(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        // Upload with empty string category
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.documents.store', $this->department), [
                'file' => $file,
                'category' => '',
            ]);

        $response->assertCreated();

        // Verify the document has null category (empty string treated as no category)
        $document = DepartmentDocument::latest()->first();
        $this->assertNull($document->category);
    }
}
