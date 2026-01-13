<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_generates_uuid_on_creation(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $document = DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->create();

        $this->assertNotNull($document->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $document->uuid
        );
    }

    /** @test */
    public function it_auto_sets_year_and_month_if_not_provided(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $document = DepartmentDocument::create([
            'department_id' => $department->id,
            'uploaded_by' => $user->id,
            'original_name' => 'test.pdf',
            'file_name' => 'test-uuid.pdf',
            'file_path' => 'department_documents/1/2026/1/test-uuid.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'extension' => 'pdf',
        ]);

        $this->assertEquals(now()->year, $document->year);
        $this->assertEquals(now()->month, $document->month);
    }

    /** @test */
    public function it_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($department)
            ->create();

        $this->assertInstanceOf(Department::class, $document->department);
        $this->assertEquals($department->id, $document->department->id);
    }

    /** @test */
    public function it_belongs_to_uploader(): void
    {
        $user = User::factory()->create();
        $document = DepartmentDocument::factory()
            ->uploadedBy($user)
            ->create();

        $this->assertInstanceOf(User::class, $document->uploader);
        $this->assertEquals($user->id, $document->uploader->id);
    }

    /** @test */
    public function it_generates_correct_file_url(): void
    {
        $document = DepartmentDocument::factory()->create([
            'file_path' => 'department_documents/1/2026/1/test-file.pdf',
        ]);

        $this->assertStringContainsString('storage/department_documents/1/2026/1/test-file.pdf', $document->file_url);
    }

    /** @test */
    public function it_formats_file_size_in_bytes(): void
    {
        $document = DepartmentDocument::factory()->create([
            'file_size' => 512,
        ]);

        $this->assertEquals('512 B', $document->formatted_file_size);
    }

    /** @test */
    public function it_formats_file_size_in_kilobytes(): void
    {
        $document = DepartmentDocument::factory()->create([
            'file_size' => 2048,
        ]);

        $this->assertEquals('2 KB', $document->formatted_file_size);
    }

    /** @test */
    public function it_formats_file_size_in_megabytes(): void
    {
        $document = DepartmentDocument::factory()->create([
            'file_size' => 5242880, // 5MB
        ]);

        $this->assertEquals('5 MB', $document->formatted_file_size);
    }

    /** @test */
    public function it_returns_correct_month_names(): void
    {
        $monthNames = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        foreach ($monthNames as $monthNumber => $expectedName) {
            $document = DepartmentDocument::factory()->create([
                'month' => $monthNumber,
            ]);

            $this->assertEquals($expectedName, $document->month_name);
        }
    }

    /** @test */
    public function it_identifies_pdf_file_type(): void
    {
        $document = DepartmentDocument::factory()->pdf()->create();
        $this->assertEquals('pdf', $document->file_type);
    }

    /** @test */
    public function it_identifies_word_file_type(): void
    {
        $document = DepartmentDocument::factory()->word()->create();
        $this->assertEquals('word', $document->file_type);

        $document2 = DepartmentDocument::factory()->create(['extension' => 'doc']);
        $this->assertEquals('word', $document2->file_type);
    }

    /** @test */
    public function it_identifies_excel_file_type(): void
    {
        $document = DepartmentDocument::factory()->excel()->create();
        $this->assertEquals('excel', $document->file_type);

        $document2 = DepartmentDocument::factory()->create(['extension' => 'xls']);
        $this->assertEquals('excel', $document2->file_type);

        $document3 = DepartmentDocument::factory()->create(['extension' => 'csv']);
        $this->assertEquals('excel', $document3->file_type);
    }

    /** @test */
    public function it_identifies_powerpoint_file_type(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'pptx']);
        $this->assertEquals('powerpoint', $document->file_type);

        $document2 = DepartmentDocument::factory()->create(['extension' => 'ppt']);
        $this->assertEquals('powerpoint', $document2->file_type);
    }

    /** @test */
    public function it_identifies_image_file_type(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('image', $document->file_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_identifies_archive_file_type(): void
    {
        $extensions = ['zip', 'rar', '7z'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('archive', $document->file_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_identifies_text_file_type(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'txt']);
        $this->assertEquals('text', $document->file_type);
    }

    /** @test */
    public function it_returns_other_for_unknown_file_type(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'unknown']);
        $this->assertEquals('other', $document->file_type);
    }

    /** @test */
    public function it_can_scope_by_year(): void
    {
        $department = Department::factory()->create();

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2026, 1)
            ->count(3)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2025, 12)
            ->count(2)
            ->create();

        $documents2026 = DepartmentDocument::byYear(2026)->count();
        $documents2025 = DepartmentDocument::byYear(2025)->count();

        $this->assertEquals(3, $documents2026);
        $this->assertEquals(2, $documents2025);
    }

    /** @test */
    public function it_can_scope_by_month(): void
    {
        $department = Department::factory()->create();

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2026, 1)
            ->count(3)
            ->create();

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2026, 2)
            ->count(2)
            ->create();

        $documentsJan = DepartmentDocument::byMonth(2026, 1)->count();
        $documentsFeb = DepartmentDocument::byMonth(2026, 2)->count();

        $this->assertEquals(3, $documentsJan);
        $this->assertEquals(2, $documentsFeb);
    }

    /** @test */
    public function it_builds_correct_tree_structure(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        // Create documents for 2026 Jan, Feb and 2025 Dec
        DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->forPeriod(2026, 1)
            ->count(2)
            ->create(['category' => 'test-category']);

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->forPeriod(2026, 2)
            ->count(1)
            ->create(['category' => 'test-category']);

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->forPeriod(2025, 12)
            ->count(1)
            ->create(['category' => 'test-category']);

        $tree = DepartmentDocument::getTreeForDepartment($department->id);

        // Should have 2 years
        $this->assertCount(2, $tree);

        // 2026 should be first (desc order)
        $this->assertEquals(2026, $tree[0]['year']);
        $this->assertEquals(3, $tree[0]['document_count']);

        // 2026 should have 2 months
        $this->assertCount(2, $tree[0]['months']);

        // February should be first in months (desc order)
        $this->assertEquals(2, $tree[0]['months'][0]['month']);
        $this->assertEquals('Février', $tree[0]['months'][0]['month_name']);
        $this->assertEquals(1, $tree[0]['months'][0]['document_count']);

        // January should be second
        $this->assertEquals(1, $tree[0]['months'][1]['month']);
        $this->assertEquals('Janvier', $tree[0]['months'][1]['month_name']);
        $this->assertEquals(2, $tree[0]['months'][1]['document_count']);

        // 2025 should be second year
        $this->assertEquals(2025, $tree[1]['year']);
        $this->assertEquals(1, $tree[1]['document_count']);
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $document = DepartmentDocument::factory()->create();
        $documentId = $document->id;

        $document->delete();

        $this->assertSoftDeleted('department_documents', ['id' => $documentId]);

        // Should not be included in regular queries
        $this->assertNull(DepartmentDocument::find($documentId));

        // Should be found when including trashed
        $this->assertNotNull(DepartmentDocument::withTrashed()->find($documentId));
    }

    /** @test */
    public function it_uses_uuid_as_route_key(): void
    {
        $document = DepartmentDocument::factory()->create();

        $this->assertEquals('uuid', $document->getRouteKeyName());
    }

    /** @test */
    public function it_includes_uploader_info_in_tree(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->forPeriod(2026, 1)
            ->create(['category' => 'test-category']);

        $tree = DepartmentDocument::getTreeForDepartment($department->id);

        // Documents are now in categories within months
        $document = $this->findFirstDocumentInTree($tree);

        $this->assertArrayHasKey('uploader', $document);
        $this->assertEquals($user->id, $document['uploader']['id']);
        $this->assertEquals('John Doe', $document['uploader']['name']);
    }

    /** @test */
    public function it_uses_original_name_as_title_fallback_in_tree(): void
    {
        $department = Department::factory()->create();

        // Document without title
        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2026, 1)
            ->create([
                'title' => null,
                'original_name' => 'important-report.pdf',
                'category' => 'test-category',
            ]);

        $tree = DepartmentDocument::getTreeForDepartment($department->id);
        // Documents are now in categories within months
        $document = $this->findFirstDocumentInTree($tree);

        $this->assertEquals('important-report.pdf', $document['title']);
    }

    /** @test */
    public function it_uses_title_when_provided_in_tree(): void
    {
        $department = Department::factory()->create();

        DepartmentDocument::factory()
            ->forDepartment($department)
            ->forPeriod(2026, 1)
            ->create([
                'title' => 'Custom Title',
                'original_name' => 'file.pdf',
                'category' => 'test-category',
            ]);

        $tree = DepartmentDocument::getTreeForDepartment($department->id);
        // Documents are now in categories within months
        $document = $this->findFirstDocumentInTree($tree);

        $this->assertEquals('Custom Title', $document['title']);
    }

    /**
     * Helper method to find the first document in the tree structure.
     */
    private function findFirstDocumentInTree(array $tree): ?array
    {
        foreach ($tree as $year) {
            foreach ($year['months'] as $month) {
                foreach ($month['categories'] as $category) {
                    if (!empty($category['documents'])) {
                        return $category['documents'][0];
                    }
                }
            }
        }
        return null;
    }

    // ==================== VIDEO/AUDIO FILE TYPE TESTS ====================

    /** @test */
    public function it_identifies_video_file_type(): void
    {
        $extensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('video', $document->file_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_identifies_audio_file_type(): void
    {
        $extensions = ['mp3', 'wav', 'ogg', 'm4a'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('audio', $document->file_type, "Failed for extension: $ext");
        }
    }

    // ==================== CAN_PREVIEW AND PREVIEW_TYPE TESTS ====================

    /** @test */
    public function it_returns_can_preview_true_for_previewable_types(): void
    {
        $previewableExtensions = [
            'pdf' => 'pdf',
            'jpg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'mp4' => 'video',
            'webm' => 'video',
            'mp3' => 'audio',
            'wav' => 'audio',
            'txt' => 'text',
        ];

        foreach ($previewableExtensions as $ext => $type) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertTrue($document->can_preview, "Extension $ext should be previewable");
            $this->assertEquals($type, $document->file_type);
        }
    }

    /** @test */
    public function it_returns_can_preview_false_for_non_previewable_types(): void
    {
        $nonPreviewableExtensions = ['docx', 'xlsx', 'pptx', 'zip', 'rar', 'unknown'];

        foreach ($nonPreviewableExtensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertFalse($document->can_preview, "Extension $ext should not be previewable");
        }
    }

    /** @test */
    public function it_returns_correct_preview_type_for_pdf(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'pdf']);
        $this->assertEquals('pdf', $document->preview_type);
    }

    /** @test */
    public function it_returns_correct_preview_type_for_images(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('image', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_correct_preview_type_for_video(): void
    {
        $extensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('video', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_correct_preview_type_for_audio(): void
    {
        $extensions = ['mp3', 'wav', 'ogg', 'm4a'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('audio', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_correct_preview_type_for_text(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'txt']);
        $this->assertEquals('text', $document->preview_type);
    }

    /** @test */
    public function it_returns_office_preview_type_for_word(): void
    {
        $extensions = ['doc', 'docx'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('office', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_office_preview_type_for_excel(): void
    {
        $extensions = ['xls', 'xlsx'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('office', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_office_preview_type_for_powerpoint(): void
    {
        $extensions = ['ppt', 'pptx'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('office', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_none_preview_type_for_archive(): void
    {
        $extensions = ['zip', 'rar', '7z'];

        foreach ($extensions as $ext) {
            $document = DepartmentDocument::factory()->create(['extension' => $ext]);
            $this->assertEquals('none', $document->preview_type, "Failed for extension: $ext");
        }
    }

    /** @test */
    public function it_returns_none_preview_type_for_unknown_extension(): void
    {
        $document = DepartmentDocument::factory()->create(['extension' => 'unknown']);
        $this->assertEquals('none', $document->preview_type);
    }

    /** @test */
    public function it_generates_correct_preview_url(): void
    {
        $department = Department::factory()->create();
        $document = DepartmentDocument::factory()
            ->forDepartment($department)
            ->create();

        $previewUrl = $document->preview_url;

        $this->assertStringContainsString('/api/departments/', $previewUrl);
        $this->assertStringContainsString($department->uuid, $previewUrl);
        $this->assertStringContainsString('/documents/', $previewUrl);
        $this->assertStringContainsString($document->uuid, $previewUrl);
        $this->assertStringContainsString('/preview', $previewUrl);
    }

    /** @test */
    public function it_includes_preview_fields_in_tree(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        // Create a video document
        DepartmentDocument::factory()
            ->forDepartment($department)
            ->uploadedBy($user)
            ->forPeriod(2026, 1)
            ->create([
                'extension' => 'mp4',
                'category' => 'test-category',
            ]);

        $tree = DepartmentDocument::getTreeForDepartment($department->id);
        // Documents are now in categories within months
        $document = $this->findFirstDocumentInTree($tree);

        $this->assertArrayHasKey('can_preview', $document);
        $this->assertArrayHasKey('preview_type', $document);
        $this->assertArrayHasKey('preview_url', $document);

        $this->assertTrue($document['can_preview']);
        $this->assertEquals('video', $document['preview_type']);
        $this->assertStringContainsString('/preview', $document['preview_url']);
    }
}
