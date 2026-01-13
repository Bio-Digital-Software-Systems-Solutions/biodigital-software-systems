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
            ->has('workflows.data', 3)
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
            'trigger_type' => 'manual',
            'scope' => 'department',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.store'), $workflowData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_workflows', [
            'name' => 'Test Workflow',
            'department_id' => $this->department->id,
            'status' => WorkflowStatus::DRAFT->value,
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
                'status' => WorkflowStatus::DRAFT,
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
                'status' => WorkflowStatus::DRAFT,
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
                'status' => WorkflowStatus::ACTIVE,
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
                'status' => WorkflowStatus::DRAFT,
            ]);

        // Add required start and end steps
        WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
            'is_end' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('workflows.activate', $workflow));

        $response->assertRedirect();

        $workflow->refresh();
        $this->assertEquals(WorkflowStatus::ACTIVE, $workflow->status);
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
            'name' => 'Original Workflow (Copy)',
            'status' => WorkflowStatus::DRAFT->value,
        ]);
    }

    public function test_user_can_delete_draft_workflow(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
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
                'status' => WorkflowStatus::ACTIVE,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflows.destroy', $workflow));

        // Active workflows should not be deleted
        $this->assertDatabaseHas('department_workflows', [
            'id' => $workflow->id,
            'deleted_at' => null,
        ]);
    }

    public function test_workflow_can_have_steps_with_transitions(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
            'is_start' => true,
            'position_x' => 0,
            'position_y' => 100,
        ]);

        $taskStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::ACTION,
            'position_x' => 200,
            'position_y' => 100,
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
            'is_end' => true,
            'position_x' => 400,
            'position_y' => 100,
        ]);

        // Create transitions between steps
        $transition1 = WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $taskStep->id,
        ]);

        $transition2 = WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $taskStep->id,
            'to_step_id' => $endStep->id,
        ]);

        // Verify the workflow has the correct structure
        $this->assertEquals(3, $workflow->steps()->count());
        $this->assertEquals(2, $workflow->transitions()->count());

        // Verify transitions connect the correct steps
        $this->assertEquals($startStep->id, $transition1->from_step_id);
        $this->assertEquals($taskStep->id, $transition1->to_step_id);
        $this->assertEquals($taskStep->id, $transition2->from_step_id);
        $this->assertEquals($endStep->id, $transition2->to_step_id);
    }

    public function test_transitions_are_removed_when_step_is_deleted(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $step1 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
        ]);

        $step2 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::ACTION,
        ]);

        $step3 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $step1->id,
            'to_step_id' => $step2->id,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $step2->id,
            'to_step_id' => $step3->id,
        ]);

        $this->assertEquals(2, $workflow->transitions()->count());

        // Delete the middle step
        $step2->delete();

        // Both transitions should be removed via cascade delete since the step was deleted
        // (foreign key constraint with onDelete('cascade'))
        $remainingTransitions = WorkflowTransition::where('workflow_id', $workflow->id)->count();

        $this->assertEquals(0, $remainingTransitions);
    }

    public function test_workflow_steps_have_unique_uuids(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $step1 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
        ]);

        $step2 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::ACTION,
        ]);

        $step3 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
        ]);

        // Each step should have a unique UUID
        $this->assertNotEmpty($step1->uuid);
        $this->assertNotEmpty($step2->uuid);
        $this->assertNotEmpty($step3->uuid);

        $this->assertNotEquals($step1->uuid, $step2->uuid);
        $this->assertNotEquals($step2->uuid, $step3->uuid);
        $this->assertNotEquals($step1->uuid, $step3->uuid);
    }

    public function test_workflow_transitions_have_unique_uuids(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $step1 = WorkflowStep::factory()->create(['workflow_id' => $workflow->id]);
        $step2 = WorkflowStep::factory()->create(['workflow_id' => $workflow->id]);
        $step3 = WorkflowStep::factory()->create(['workflow_id' => $workflow->id]);

        $transition1 = WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $step1->id,
            'to_step_id' => $step2->id,
        ]);

        $transition2 = WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $step2->id,
            'to_step_id' => $step3->id,
        ]);

        // Each transition should have a unique UUID
        $this->assertNotEmpty($transition1->uuid);
        $this->assertNotEmpty($transition2->uuid);
        $this->assertNotEquals($transition1->uuid, $transition2->uuid);
    }

    public function test_can_update_workflow_metadata(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        $updateData = [
            'name' => 'Workflow Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('workflows.update', $workflow), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_workflows', [
            'id' => $workflow->id,
            'name' => 'Workflow Updated Name',
        ]);
    }

    public function test_can_save_workflow_steps_and_transitions(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        // Create steps data with UUIDs (simulating frontend behavior)
        $stepsData = [
            [
                'uuid' => 'frontend-step-1',
                'name' => 'Start',
                'type' => 'start',
                'order' => 0,
                'position_x' => 0,
                'position_y' => 100,
                'is_start' => true,
                'is_end' => false,
            ],
            [
                'uuid' => 'frontend-step-2',
                'name' => 'Action',
                'type' => 'action',
                'order' => 1,
                'position_x' => 200,
                'position_y' => 100,
                'is_start' => false,
                'is_end' => false,
            ],
            [
                'uuid' => 'frontend-step-3',
                'name' => 'End',
                'type' => 'end',
                'order' => 2,
                'position_x' => 400,
                'position_y' => 100,
                'is_start' => false,
                'is_end' => true,
            ],
        ];

        // Create transitions data with UUID references (simulating frontend behavior)
        $transitionsData = [
            [
                'uuid' => 'frontend-trans-1',
                'from_step_uuid' => 'frontend-step-1',
                'to_step_uuid' => 'frontend-step-2',
                'condition_type' => 'always',
            ],
            [
                'uuid' => 'frontend-trans-2',
                'from_step_uuid' => 'frontend-step-2',
                'to_step_uuid' => 'frontend-step-3',
                'condition_type' => 'always',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.save-steps', $workflow), [
                'steps' => $stepsData,
                'transitions' => $transitionsData,
            ]);

        $response->assertRedirect();

        // Verify steps were created
        $this->assertEquals(3, $workflow->steps()->count());
        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $workflow->id,
            'uuid' => 'frontend-step-1',
            'type' => 'start',
            'is_start' => true,
        ]);
        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $workflow->id,
            'uuid' => 'frontend-step-2',
            'type' => 'action',
        ]);
        $this->assertDatabaseHas('workflow_steps', [
            'workflow_id' => $workflow->id,
            'uuid' => 'frontend-step-3',
            'type' => 'end',
            'is_end' => true,
        ]);

        // Verify transitions were created
        $this->assertEquals(2, $workflow->transitions()->count());

        // Get the created steps to verify transitions
        $startStep = $workflow->steps()->where('uuid', 'frontend-step-1')->first();
        $actionStep = $workflow->steps()->where('uuid', 'frontend-step-2')->first();
        $endStep = $workflow->steps()->where('uuid', 'frontend-step-3')->first();

        $this->assertDatabaseHas('workflow_transitions', [
            'workflow_id' => $workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $actionStep->id,
        ]);
        $this->assertDatabaseHas('workflow_transitions', [
            'workflow_id' => $workflow->id,
            'from_step_id' => $actionStep->id,
            'to_step_id' => $endStep->id,
        ]);
    }

    public function test_save_steps_replaces_existing_steps_and_transitions(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        // Create initial steps
        $oldStep1 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
        ]);
        $oldStep2 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
        ]);
        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $oldStep1->id,
            'to_step_id' => $oldStep2->id,
        ]);

        $this->assertEquals(2, $workflow->steps()->count());
        $this->assertEquals(1, $workflow->transitions()->count());

        // Now save new steps (should replace old ones)
        $newStepsData = [
            [
                'uuid' => 'new-step-1',
                'name' => 'New Start',
                'type' => 'start',
                'order' => 0,
                'position_x' => 0,
                'position_y' => 0,
                'is_start' => true,
                'is_end' => false,
            ],
            [
                'uuid' => 'new-step-2',
                'name' => 'New Action',
                'type' => 'action',
                'order' => 1,
                'position_x' => 100,
                'position_y' => 0,
                'is_start' => false,
                'is_end' => false,
            ],
            [
                'uuid' => 'new-step-3',
                'name' => 'New Approval',
                'type' => 'approval',
                'order' => 2,
                'position_x' => 200,
                'position_y' => 0,
                'is_start' => false,
                'is_end' => false,
            ],
            [
                'uuid' => 'new-step-4',
                'name' => 'New End',
                'type' => 'end',
                'order' => 3,
                'position_x' => 300,
                'position_y' => 0,
                'is_start' => false,
                'is_end' => true,
            ],
        ];

        $newTransitionsData = [
            [
                'uuid' => 'new-trans-1',
                'from_step_uuid' => 'new-step-1',
                'to_step_uuid' => 'new-step-2',
                'condition_type' => 'always',
            ],
            [
                'uuid' => 'new-trans-2',
                'from_step_uuid' => 'new-step-2',
                'to_step_uuid' => 'new-step-3',
                'condition_type' => 'always',
            ],
            [
                'uuid' => 'new-trans-3',
                'from_step_uuid' => 'new-step-3',
                'to_step_uuid' => 'new-step-4',
                'condition_type' => 'always',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.save-steps', $workflow), [
                'steps' => $newStepsData,
                'transitions' => $newTransitionsData,
            ]);

        $response->assertRedirect();

        // Old steps and transitions should be deleted, new ones created
        $this->assertEquals(4, $workflow->steps()->count());
        $this->assertEquals(3, $workflow->transitions()->count());

        // Old steps should not exist anymore
        $this->assertDatabaseMissing('workflow_steps', [
            'id' => $oldStep1->id,
            'workflow_id' => $workflow->id,
        ]);
        $this->assertDatabaseMissing('workflow_steps', [
            'id' => $oldStep2->id,
            'workflow_id' => $workflow->id,
        ]);
    }

    public function test_save_steps_validates_step_types(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        // Try to save with invalid step type
        $invalidStepsData = [
            [
                'uuid' => 'invalid-step',
                'name' => 'Invalid',
                'type' => 'invalid_type_that_does_not_exist',
                'order' => 0,
                'position_x' => 0,
                'position_y' => 0,
                'is_start' => false,
                'is_end' => false,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.save-steps', $workflow), [
                'steps' => $invalidStepsData,
                'transitions' => [],
            ]);

        // Should fail validation
        $response->assertStatus(302); // redirect with errors
        $this->assertEquals(0, $workflow->steps()->count());
    }

    public function test_save_steps_handles_all_valid_step_types(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        // Test all valid step types
        $validTypes = ['start', 'end', 'approval', 'condition', 'action', 'wait', 'notification', 'form', 'subprocess', 'parallel_split', 'parallel_join'];

        $stepsData = [];
        foreach ($validTypes as $index => $type) {
            $stepsData[] = [
                'uuid' => "step-{$type}",
                'name' => ucfirst($type),
                'type' => $type,
                'order' => $index,
                'position_x' => $index * 100,
                'position_y' => 0,
                'is_start' => $type === 'start',
                'is_end' => $type === 'end',
            ];
        }

        $response = $this->actingAs($this->user)
            ->post(route('workflows.save-steps', $workflow), [
                'steps' => $stepsData,
                'transitions' => [],
            ]);

        $response->assertRedirect();
        $this->assertEquals(count($validTypes), $workflow->steps()->count());

        // Verify each type was saved correctly
        foreach ($validTypes as $type) {
            $this->assertDatabaseHas('workflow_steps', [
                'workflow_id' => $workflow->id,
                'uuid' => "step-{$type}",
                'type' => $type,
            ]);
        }
    }

    public function test_save_steps_preserves_step_config(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        $stepsData = [
            [
                'uuid' => 'approval-step',
                'name' => 'Manager Approval',
                'type' => 'approval',
                'order' => 0,
                'position_x' => 0,
                'position_y' => 0,
                'is_start' => false,
                'is_end' => false,
                'config' => [
                    'approval_type' => 'all',
                    'min_approvals' => 2,
                    'timeout_hours' => 24,
                ],
                'approval_type' => 'all',
                'timeout_hours' => 24,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('workflows.save-steps', $workflow), [
                'steps' => $stepsData,
                'transitions' => [],
            ]);

        $response->assertRedirect();

        $step = $workflow->steps()->where('uuid', 'approval-step')->first();
        $this->assertNotNull($step);
        $this->assertEquals('all', $step->approval_type->value);
        $this->assertEquals(24, $step->timeout_hours);
    }

    public function test_branching_workflow_with_condition_step(): void
    {
        $workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create(['created_by' => $this->user->id]);

        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $conditionStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::CONDITION,
            'name' => 'Check Amount',
        ]);

        $approvalStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::APPROVAL,
            'name' => 'Manager Approval',
        ]);

        $endStep1 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
            'is_end' => true,
            'name' => 'Approved End',
        ]);

        $endStep2 = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'type' => StepType::END,
            'is_end' => true,
            'name' => 'Auto Approved End',
        ]);

        // Start -> Condition
        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $conditionStep->id,
        ]);

        // Condition -> Approval (high amount)
        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $conditionStep->id,
            'to_step_id' => $approvalStep->id,
            'name' => 'High Amount',
        ]);

        // Condition -> End2 (low amount - auto approved)
        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $conditionStep->id,
            'to_step_id' => $endStep2->id,
            'name' => 'Low Amount',
        ]);

        // Approval -> End1
        WorkflowTransition::factory()->create([
            'workflow_id' => $workflow->id,
            'from_step_id' => $approvalStep->id,
            'to_step_id' => $endStep1->id,
        ]);

        // Verify structure
        $this->assertEquals(5, $workflow->steps()->count());
        $this->assertEquals(4, $workflow->transitions()->count());

        // Condition step should have 2 outgoing transitions
        $conditionOutgoing = WorkflowTransition::where('workflow_id', $workflow->id)
            ->where('from_step_id', $conditionStep->id)
            ->count();
        $this->assertEquals(2, $conditionOutgoing);
    }
}
