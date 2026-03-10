<?php

namespace Tests\Unit\Services;

use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\StepType;
use App\Enums\Workflow\WorkflowInstanceStatus;
use App\Enums\Workflow\WorkflowStatus;
use App\Models\Department;
use App\Models\DepartmentWorkflow;
use App\Models\StepApproval;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepInstance;
use App\Models\WorkflowTransition;
use App\Services\Workflow\StepExecutorService;
use App\Services\Workflow\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowService $service;

    protected StepExecutorService $stepExecutor;

    protected User $user;

    protected Department $department;

    protected DepartmentWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WorkflowService::class);
        $this->stepExecutor = app(StepExecutorService::class);
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();

        $this->workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::ACTIVE,
            ]);
    }

    public function test_can_start_workflow_instance(): void
    {
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
            'name' => 'Start',
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::END,
            'is_end' => true,
            'name' => 'End',
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $endStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id, [
            'test_data' => 'value',
        ]);

        $this->assertInstanceOf(WorkflowInstance::class, $instance);
        $this->assertEquals($this->user->id, $instance->started_by);
        $this->assertEquals(['test_data' => 'value'], $instance->input_data);
    }

    public function test_cannot_start_draft_workflow(): void
    {
        $draftWorkflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::DRAFT,
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot start inactive workflow.');

        $this->service->startWorkflow($draftWorkflow, $this->user->id);
    }

    public function test_can_complete_step_instance(): void
    {
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $taskStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::ACTION,
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::END,
            'is_end' => true,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $taskStep->id,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $taskStep->id,
            'to_step_id' => $endStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id);

        $taskStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('workflow_step_id', $taskStep->id)
            ->first();

        $this->assertNotNull($taskStepInstance);
        $this->assertEquals(StepInstanceStatus::ACTIVE, $taskStepInstance->status);

        $taskStepInstance->complete(['result' => 'completed'], $this->user->id);

        $taskStepInstance->refresh();
        $this->assertEquals(StepInstanceStatus::COMPLETED, $taskStepInstance->status);
    }

    public function test_approval_step_can_be_approved(): void
    {
        $approver = User::factory()->create();

        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $approvalStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::APPROVAL,
            'name' => 'Manager Approval',
            'approvers' => [['type' => 'user', 'id' => $approver->id]],
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::END,
            'is_end' => true,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $approvalStep->id,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $approvalStep->id,
            'to_step_id' => $endStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id);

        $approvalStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('workflow_step_id', $approvalStep->id)
            ->first();

        $this->assertNotNull($approvalStepInstance);
        $this->assertEquals(StepInstanceStatus::ACTIVE, $approvalStepInstance->status);

        $approval = StepApproval::where('step_instance_id', $approvalStepInstance->id)
            ->where('approver_id', $approver->id)
            ->first();

        $this->assertNotNull($approval);

        $this->stepExecutor->processApproval($approval, 'approved', 'Looks good');

        $approvalStepInstance->refresh();
        $this->assertEquals(StepInstanceStatus::COMPLETED, $approvalStepInstance->status);
        $this->assertEquals('approved', $approvalStepInstance->output_data['approval_result'] ?? null);
    }

    public function test_approval_step_can_be_rejected(): void
    {
        $approver = User::factory()->create();

        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $approvalStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::APPROVAL,
            'name' => 'Manager Approval',
            'approvers' => [['type' => 'user', 'id' => $approver->id]],
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $approvalStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id);

        $approvalStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('workflow_step_id', $approvalStep->id)
            ->first();

        $this->assertNotNull($approvalStepInstance);

        $approval = StepApproval::where('step_instance_id', $approvalStepInstance->id)
            ->where('approver_id', $approver->id)
            ->first();

        $this->assertNotNull($approval);

        $this->stepExecutor->processApproval($approval, 'rejected', 'Rejected due to budget');

        $approvalStepInstance->refresh();
        $this->assertEquals(StepInstanceStatus::COMPLETED, $approvalStepInstance->status);
        $this->assertEquals('rejected', $approvalStepInstance->output_data['approval_result'] ?? null);
    }

    public function test_workflow_instance_completes_when_end_step_reached(): void
    {
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::END,
            'is_end' => true,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $endStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id);

        $instance->refresh();
        $this->assertEquals(WorkflowInstanceStatus::COMPLETED, $instance->status);
        $this->assertNotNull($instance->completed_at);
    }

    public function test_can_cancel_workflow_instance(): void
    {
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::START,
            'is_start' => true,
        ]);

        $taskStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::ACTION,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $taskStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user->id);

        $this->service->cancelWorkflow($instance, 'No longer needed');

        $instance->refresh();
        $this->assertEquals(WorkflowInstanceStatus::CANCELLED, $instance->status);
        $this->assertNotNull($instance->cancelled_at);
    }
}
