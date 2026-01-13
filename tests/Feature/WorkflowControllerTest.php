<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\StepType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
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

    public function test_user_can_view_workflows_index(): void
    {
        DepartmentWorkflow::factory()
            ->count(3)
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('workflows.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflows/Index')
            ->has('workflows', 3)
        );
    }

    public function test_user_can_create_workflow(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('workflows.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflows/Create')
        );
    }

    public function test_user_can_store_workflow(): void
    {
        $workflowData = [
            'name' => 'Test Workflow',
            'description' => 'A test workflow description',
            'department_id' => $this->department->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.store'), $workflowData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_workflows', [
            'name' => 'Test Workflow',
            'department_id' => $this->department->id,
            'status' => WorkflowStatus::Draft->value,
        ]);
    }

    public function test_user_can_view_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('workflows.show', $workflow));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflows/Show')
            ->has('workflow')
        );
    }

    public function test_user_can_edit_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Draft,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflows.edit', $workflow));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflows/Builder')
            ->has('workflow')
        );
    }

    public function test_user_can_update_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Draft,
            ]);

        $updateData = [
            'name' => 'Updated Workflow Name',
            'description' => 'Updated description',
            'steps' => json_encode([]),
            'transitions' => json_encode([]),
        ];

        $response = $this->actingAs($this->user)
            ->put(route('workflows.update', $workflow), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_workflows', [
            'id' => $workflow->id,
            'name' => 'Updated Workflow Name',
        ]);
    }

    public function test_user_cannot_update_active_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Active,
            ]);

        $updateData = [
            'name' => 'Should Not Update',
            'description' => 'This should fail',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('workflows.update', $workflow), $updateData);

        // Should either redirect with error or return 403
        $this->assertDatabaseMissing('department_workflows', [
            'id' => $workflow->id,
            'name' => 'Should Not Update',
        ]);
    }

    public function test_user_can_activate_draft_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Draft,
            ]);

        // Add required start and end steps
        WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::End,
            'is_end' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('workflows.activate', $workflow));

        $response->assertRedirect();

        $workflow->refresh();
        $this->assertEquals(WorkflowStatus::Active, $workflow->status);
    }

    public function test_user_can_duplicate_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'name' => 'Original Workflow',
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('workflows.duplicate', $workflow));

        $response->assertRedirect();

        $this->assertDatabaseHas('department_workflows', [
            'name' => 'Original Workflow (copie)',
            'status' => WorkflowStatus::Draft->value,
        ]);
    }

    public function test_user_can_delete_draft_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Draft,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflows.destroy', $workflow));

        $response->assertRedirect();

        $this->assertSoftDeleted('department_workflows', [
            'id' => $workflow->id,
        ]);
    }

    public function test_user_cannot_delete_active_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Active,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflows.destroy', $workflow));

        // Active workflows should not be deleted
        $this->assertDatabaseHas('department_workflows', [
            'id' => $workflow->id,
            'deleted_at' => null,
        ]);
    }
}
