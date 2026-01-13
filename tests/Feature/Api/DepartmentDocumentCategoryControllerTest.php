<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentDocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentDocumentCategoryControllerTest extends TestCase
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
    public function it_can_list_categories_for_a_month(): void
    {
        // Create system category for rapports
        DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Rapports',
            'slug' => 'rapports',
            'year' => 2026,
            'month' => 1,
            'is_system' => true,
            'sort_order' => 0,
        ]);

        // Create default month category (Janvier)
        DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Janvier',
            'slug' => 'janvier',
            'year' => 2026,
            'month' => 1,
            'is_system' => true,
            'sort_order' => -1,
        ]);

        // Create custom category
        DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
                'year' => 2026,
                'month' => 1,
            ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'slug',
                        'key',
                        'year',
                        'month',
                        'month_name',
                        'is_system',
                        'sort_order',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Janvier') // First is default month (sort_order -1)
            ->assertJsonPath('data.0.is_system', true)
            ->assertJsonPath('data.1.name', 'Rapports') // Second is rapports (sort_order 0)
            ->assertJsonPath('data.2.name', 'Factures'); // Third is custom (sort_order 1)
    }

    /** @test */
    public function it_creates_default_categories_when_listing_if_not_exists(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
                'year' => 2026,
                'month' => 3,
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // Default month (Mars) + Rapports

        // Verify both default categories were created
        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'year' => 2026,
            'month' => 3,
            'slug' => 'rapports',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'year' => 2026,
            'month' => 3,
            'slug' => 'mars',
            'is_system' => true,
        ]);
    }

    /** @test */
    public function it_can_create_a_custom_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), [
                'name' => 'Procès-verbaux',
                'year' => 2026,
                'month' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'name',
                    'slug',
                    'key',
                    'year',
                    'month',
                    'is_system',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Procès-verbaux')
            ->assertJsonPath('data.slug', 'proces-verbaux')
            ->assertJsonPath('data.is_system', false);

        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'name' => 'Procès-verbaux',
            'slug' => 'proces-verbaux',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_prevents_creating_duplicate_category(): void
    {
        // Create existing category
        DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), [
                'name' => 'Factures',
                'year' => 2026,
                'month' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Un sous-dossier avec ce nom existe déjà pour ce mois.');
    }

    /** @test */
    public function it_can_rename_a_custom_category(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.document-categories.update', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]), [
                'name' => 'Factures Fournisseurs',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Factures Fournisseurs')
            ->assertJsonPath('data.slug', 'factures-fournisseurs');

        $this->assertDatabaseHas('department_document_categories', [
            'id' => $category->id,
            'name' => 'Factures Fournisseurs',
            'slug' => 'factures-fournisseurs',
        ]);
    }

    /** @test */
    public function it_updates_documents_category_when_renaming(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        // Create a document in this category
        $document = DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->create(['category' => 'factures']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.document-categories.update', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]), [
                'name' => 'Factures Client',
            ]);

        $response->assertOk();

        // Check the document's category was updated
        $this->assertDatabaseHas('department_documents', [
            'id' => $document->id,
            'category' => 'factures-client',
        ]);
    }

    /** @test */
    public function it_prevents_renaming_system_category(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Rapports',
            'slug' => 'rapports',
            'year' => 2026,
            'month' => 1,
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.document-categories.update', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]), [
                'name' => 'Mes Rapports',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Les dossiers système ne peuvent pas être modifiés.');
    }

    /** @test */
    public function it_prevents_renaming_to_existing_category_name(): void
    {
        // Create two categories
        $category1 = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Contrats',
            'slug' => 'contrats',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.document-categories.update', [
                'department' => $this->department->uuid,
                'category' => $category1->uuid,
            ]), [
                'name' => 'Contrats',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Un sous-dossier avec ce nom existe déjà pour ce mois.');
    }

    /** @test */
    public function it_can_delete_empty_custom_category(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.document-categories.destroy', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sous-dossier supprimé avec succès.');

        $this->assertSoftDeleted('department_document_categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_system_category(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Rapports',
            'slug' => 'rapports',
            'year' => 2026,
            'month' => 1,
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.document-categories.destroy', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Les dossiers système ne peuvent pas être supprimés.');

        $this->assertDatabaseHas('department_document_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_category_with_documents(): void
    {
        $category = DepartmentDocumentCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        // Create a document in this category
        DepartmentDocument::factory()
            ->forDepartment($this->department)
            ->uploadedBy($this->user)
            ->forPeriod(2026, 1)
            ->create(['category' => 'factures']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.document-categories.destroy', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        // Check category still exists
        $this->assertDatabaseHas('department_document_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_prevents_accessing_category_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $category = DepartmentDocumentCategory::create([
            'department_id' => $otherDepartment->id,
            'name' => 'Factures',
            'slug' => 'factures',
            'year' => 2026,
            'month' => 1,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        // Laravel's implicit route binding scopes the category to the department,
        // so it will return 404 (ModelNotFoundException) when trying to access
        // a category that belongs to another department
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.document-categories.update', [
                'department' => $this->department->uuid,
                'category' => $category->uuid,
            ]), [
                'name' => 'New Name',
            ]);

        // Laravel returns 404 because the scoped binding fails
        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_fields_for_create(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'year', 'month']);
    }

    /** @test */
    public function it_validates_year_and_month_ranges(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), [
                'name' => 'Test',
                'year' => 1999,
                'month' => 13,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year', 'month']);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson(route('api.departments.document-categories.index', [
            'department' => $this->department->uuid,
            'year' => 2026,
            'month' => 1,
        ]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_increments_sort_order_for_new_categories(): void
    {
        // Create first category
        $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), [
                'name' => 'Factures',
                'year' => 2026,
                'month' => 1,
            ]);

        // Create second category
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.document-categories.store', [
                'department' => $this->department->uuid,
            ]), [
                'name' => 'Contrats',
                'year' => 2026,
                'month' => 1,
            ]);

        $response->assertCreated();

        // Verify sort_order
        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'slug' => 'factures',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'slug' => 'contrats',
            'sort_order' => 2,
        ]);
    }

    // ==================== DEFAULT MONTH CATEGORY TESTS ====================

    /** @test */
    public function it_uses_current_month_when_no_parameters_provided(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        $response->assertOk()
            ->assertJsonPath('year', now()->year)
            ->assertJsonPath('month', now()->month);
    }

    /** @test */
    public function it_creates_default_month_category_when_listing_without_params(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        $response->assertOk();

        // Should have at least the default month category and rapports
        $categories = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($categories));

        // Verify default month category was created
        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'year' => now()->year,
            'month' => now()->month,
            'is_system' => true,
        ]);
    }

    /** @test */
    public function it_returns_default_month_category_first_in_list(): void
    {
        // Ensure default categories exist
        DepartmentDocumentCategory::ensureDefaultMonthCategory(
            $this->department->id,
            now()->year,
            now()->month
        );

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        $response->assertOk();

        $categories = $response->json('data');

        // The first category should have sort_order -1 (default month)
        $this->assertEquals(-1, $categories[0]['sort_order']);
    }

    /** @test */
    public function it_creates_default_category_for_specific_month(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
                'year' => 2025,
                'month' => 6, // June
            ]));

        $response->assertOk();

        // Check year and month (may be returned as strings or integers)
        $this->assertEquals(2025, $response->json('year'));
        $this->assertEquals(6, $response->json('month'));

        // Verify June category was created
        $this->assertDatabaseHas('department_document_categories', [
            'department_id' => $this->department->id,
            'year' => 2025,
            'month' => 6,
            'slug' => 'juin',
            'is_system' => true,
        ]);
    }

    /** @test */
    public function it_does_not_duplicate_default_month_category(): void
    {
        // Call twice to ensure no duplicates
        $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        // Count categories for current month
        $categoryCount = DepartmentDocumentCategory::where([
            'department_id' => $this->department->id,
            'year' => now()->year,
            'month' => now()->month,
        ])->count();

        // Should only have 2 system categories (month default + rapports)
        $this->assertEquals(2, $categoryCount);
    }

    /** @test */
    public function default_month_category_name_matches_month(): void
    {
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $expectedName = $monthNames[now()->month];

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.document-categories.index', [
                'department' => $this->department->uuid,
            ]));

        $response->assertOk();

        $categories = collect($response->json('data'));
        $defaultCategory = $categories->firstWhere('sort_order', -1);

        $this->assertNotNull($defaultCategory);
        $this->assertEquals($expectedName, $defaultCategory['name']);
    }
}
