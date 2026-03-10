<?php

namespace Tests\Feature;

use App\Enums\Form\FormFieldType;
use App\Enums\Form\FormStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view forms',
            'create forms',
            'edit forms',
            'delete forms',
            'manage forms',
        ]);
    }

    public function test_user_can_view_forms_index(): void
    {
        DepartmentForm::factory()
            ->count(3)
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Index')
            ->has('forms.data', 3)
        );
    }

    public function test_user_can_create_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('forms.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Create')
        );
    }

    public function test_user_can_store_form(): void
    {
        $formData = [
            'name' => 'Test Form',
            'description' => 'A test form description',
            'department_id' => $this->department->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('forms.store'), $formData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_forms', [
            'name' => 'Test Form',
            'department_id' => $this->department->id,
            'status' => FormStatus::DRAFT->value,
        ]);
    }

    public function test_user_can_view_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.show', $form));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Show')
            ->has('form')
        );
    }

    public function test_user_can_edit_draft_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.edit', $form));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Builder')
            ->has('form')
            ->has('fields')
        );
    }

    public function test_user_can_update_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $updateData = [
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('forms.update', $form), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_forms', [
            'id' => $form->id,
            'name' => 'Updated Form Name',
        ]);
    }

    public function test_user_can_publish_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        // Add at least one field
        FormField::factory()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TEXT,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.publish', $form));

        $response->assertRedirect();

        $form->refresh();
        $this->assertEquals(FormStatus::PUBLISHED, $form->status);
        $this->assertNotNull($form->published_at);
    }

    public function test_user_can_duplicate_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'name' => 'Original Form',
            ]);

        // Add fields to duplicate
        FormField::factory()->count(3)->create([
            'form_id' => $form->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.duplicate', $form));

        $response->assertRedirect();

        $this->assertDatabaseHas('department_forms', [
            'name' => 'Original Form (Copy)',
            'status' => FormStatus::DRAFT->value,
        ]);

        // Check fields were duplicated
        $duplicatedForm = DepartmentForm::where('name', 'Original Form (Copy)')->first();
        $this->assertEquals(3, $duplicatedForm->fields()->count());
    }

    public function test_user_can_archive_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::PUBLISHED,
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.archive', $form));

        $response->assertRedirect();

        $form->refresh();
        $this->assertEquals(FormStatus::ARCHIVED, $form->status);
    }

    public function test_user_can_delete_draft_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('forms.destroy', $form));

        $response->assertRedirect();

        $this->assertSoftDeleted('department_forms', [
            'id' => $form->id,
        ]);
    }

    public function test_form_with_fields_renders_correctly(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::PUBLISHED,
            ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Full Name',
            'type' => FormFieldType::TEXT,
            'is_required' => true,
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email Address',
            'type' => FormFieldType::EMAIL,
            'is_required' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.show', $form));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Show')
            ->has('form')
        );

        // Verify the form and fields are loaded properly
        $form->refresh();
        $this->assertEquals(2, $form->fields()->count());
    }

    // ==========================================
    // FIELD PERSISTENCE TESTS
    // ==========================================

    public function test_user_can_save_form_fields(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            [
                'name' => 'field_1',
                'label' => 'Field 1',
                'type' => 'text',
                'is_required' => true,
            ],
            [
                'name' => 'field_2',
                'label' => 'Field 2',
                'type' => 'email',
                'is_required' => false,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(2, $form->fields()->count());
        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'name' => 'field_1',
            'label' => 'Field 1',
            'type' => 'text',
        ]);

        // Verify is_required separately since boolean handling varies by DB
        $field1 = $form->fields()->where('name', 'field_1')->first();
        $this->assertTrue($field1->is_required);
    }

    public function test_save_fields_replaces_existing_fields(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        // Create initial fields
        FormField::factory()->count(3)->create([
            'form_id' => $form->id,
        ]);

        $this->assertEquals(3, $form->fields()->count());

        // Save new fields (should replace existing)
        $newFields = [
            [
                'name' => 'new_field',
                'label' => 'New Field',
                'type' => 'text',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $newFields]);

        $response->assertStatus(200);

        // Should have only 1 field now
        $this->assertEquals(1, $form->fields()->count());
        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'name' => 'new_field',
        ]);
    }

    public function test_save_fields_with_nested_children(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            [
                'name' => 'section_1',
                'label' => 'Section 1',
                'type' => 'section',
                'children' => [
                    [
                        'name' => 'nested_field_1',
                        'label' => 'Nested Field 1',
                        'type' => 'text',
                    ],
                    [
                        'name' => 'nested_field_2',
                        'label' => 'Nested Field 2',
                        'type' => 'number',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);

        // Should have 3 fields total (1 parent + 2 children)
        $this->assertEquals(3, $form->fields()->count());

        // Check parent field exists
        $section = FormField::where('form_id', $form->id)->where('name', 'section_1')->first();
        $this->assertNotNull($section);
        $this->assertNull($section->parent_field_id);

        // Check nested fields exist with correct parent
        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'name' => 'nested_field_1',
            'parent_field_id' => $section->id,
        ]);
    }

    public function test_save_fields_validates_required_attributes(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        // Missing required fields
        $fields = [
            [
                'name' => 'field_1',
                // Missing 'label' and 'type'
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fields.0.label', 'fields.0.type']);
    }

    public function test_save_empty_fields_array_clears_all_fields(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        // Create initial fields
        FormField::factory()->count(3)->create([
            'form_id' => $form->id,
        ]);

        $this->assertEquals(3, $form->fields()->count());

        // Save empty fields array
        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => []]);

        $response->assertStatus(200);

        // All fields should be deleted
        $this->assertEquals(0, $form->fields()->count());
    }

    public function test_save_fields_preserves_field_order(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            ['name' => 'field_c', 'label' => 'Field C', 'type' => 'text'],
            ['name' => 'field_a', 'label' => 'Field A', 'type' => 'text'],
            ['name' => 'field_b', 'label' => 'Field B', 'type' => 'text'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);

        // Check order is preserved
        $savedFields = $form->fields()->orderBy('order')->get();
        $this->assertEquals('field_c', $savedFields[0]->name);
        $this->assertEquals('field_a', $savedFields[1]->name);
        $this->assertEquals('field_b', $savedFields[2]->name);
        $this->assertEquals(0, $savedFields[0]->order);
        $this->assertEquals(1, $savedFields[1]->order);
        $this->assertEquals(2, $savedFields[2]->order);
    }

    public function test_save_fields_with_all_attributes(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            [
                'name' => 'full_field',
                'label' => 'Full Field',
                'type' => 'select',
                'description' => 'A full field description',
                'placeholder' => 'Select an option...',
                'help_text' => 'This is help text',
                'default_value' => 'option_1',
                'options' => [
                    ['label' => 'Option 1', 'value' => 'option_1'],
                    ['label' => 'Option 2', 'value' => 'option_2'],
                ],
                'validation' => ['min' => 1, 'max' => 100],
                'is_required' => true,
                'is_readonly' => false,
                'is_hidden' => false,
                'column_span' => 6,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);

        $savedField = $form->fields()->first();
        $this->assertEquals('full_field', $savedField->name);
        $this->assertEquals('Full Field', $savedField->label);
        $this->assertEquals('select', $savedField->type->value);
        $this->assertEquals('A full field description', $savedField->description);
        $this->assertEquals('Select an option...', $savedField->placeholder);
        $this->assertEquals('This is help text', $savedField->help_text);
        $this->assertEquals('option_1', $savedField->default_value);
        $this->assertCount(2, $savedField->options);
        $this->assertEquals(true, $savedField->is_required);
        $this->assertEquals(6, $savedField->column_span);
    }

    public function test_save_fields_ignores_unknown_attributes(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            [
                'name' => 'field_1',
                'label' => 'Field 1',
                'type' => 'text',
                'unknown_attribute' => 'should be ignored',
                'another_unknown' => 123,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);

        // Field should be saved without error
        $savedField = $form->fields()->first();
        $this->assertEquals('field_1', $savedField->name);
    }

    public function test_cannot_publish_form_without_fields(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        // No fields added

        $response = $this->actingAs($this->user)
            ->post(route('forms.publish', $form));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $form->refresh();
        $this->assertEquals(FormStatus::DRAFT, $form->status);
    }

    public function test_deeply_nested_fields_are_saved_correctly(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::DRAFT,
            ]);

        $fields = [
            [
                'name' => 'level_1',
                'label' => 'Level 1',
                'type' => 'section',
                'children' => [
                    [
                        'name' => 'level_2',
                        'label' => 'Level 2',
                        'type' => 'group',
                        'children' => [
                            [
                                'name' => 'level_3',
                                'label' => 'Level 3',
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forms.save-fields', $form), ['fields' => $fields]);

        $response->assertStatus(200);

        // Should have 3 fields total
        $this->assertEquals(3, $form->fields()->count());

        // Check hierarchy
        $level1 = $form->fields()->whereNull('parent_field_id')->first();
        $level2 = $form->fields()->where('parent_field_id', $level1->id)->first();
        $level3 = $form->fields()->where('parent_field_id', $level2->id)->first();

        $this->assertEquals('level_1', $level1->name);
        $this->assertEquals('level_2', $level2->name);
        $this->assertEquals('level_3', $level3->name);
    }

    public function test_unauthenticated_user_cannot_save_fields(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $response = $this->postJson(route('forms.save-fields', $form), [
            'fields' => [
                ['name' => 'field_1', 'label' => 'Field 1', 'type' => 'text'],
            ],
        ]);

        $response->assertStatus(401);
    }
}
