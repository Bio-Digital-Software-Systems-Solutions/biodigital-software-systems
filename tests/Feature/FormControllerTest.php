<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentFormField;
use App\Enums\Form\FormStatus;
use App\Enums\Form\FieldType;
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

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();
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
            ->has('forms', 3)
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
            'status' => FormStatus::Draft->value,
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
                'status' => FormStatus::Draft,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.edit', $form));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Builder')
            ->has('form')
        );
    }

    public function test_user_can_update_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::Draft,
            ]);

        $updateData = [
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
            'fields' => json_encode([]),
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
                'status' => FormStatus::Draft,
            ]);

        // Add at least one field
        DepartmentFormField::factory()->create([
            'form_id' => $form->id,
            'type' => FieldType::Text,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.publish', $form));

        $response->assertRedirect();

        $form->refresh();
        $this->assertEquals(FormStatus::Published, $form->status);
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
        DepartmentFormField::factory()->count(3)->create([
            'form_id' => $form->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.duplicate', $form));

        $response->assertRedirect();

        $this->assertDatabaseHas('department_forms', [
            'name' => 'Original Form (copie)',
            'status' => FormStatus::Draft->value,
        ]);

        // Check fields were duplicated
        $duplicatedForm = DepartmentForm::where('name', 'Original Form (copie)')->first();
        $this->assertEquals(3, $duplicatedForm->fields()->count());
    }

    public function test_user_can_archive_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::Published,
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('forms.archive', $form));

        $response->assertRedirect();

        $form->refresh();
        $this->assertEquals(FormStatus::Archived, $form->status);
    }

    public function test_user_can_delete_draft_form(): void
    {
        $form = DepartmentForm::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => FormStatus::Draft,
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
                'status' => FormStatus::Published,
            ]);

        DepartmentFormField::factory()->create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Full Name',
            'type' => FieldType::Text,
            'is_required' => true,
        ]);

        DepartmentFormField::factory()->create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email Address',
            'type' => FieldType::Email,
            'is_required' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('forms.show', $form));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forms/Show')
            ->has('form.fields', 2)
        );
    }
}
