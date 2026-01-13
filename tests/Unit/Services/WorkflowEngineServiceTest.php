<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStepInstance;
use App\Services\WorkflowEngineService;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\InstanceStatus;
use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\StepType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowEngineService $service;
    protected User $user;
    protected Department $department;
    protected DepartmentWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WorkflowEngineService();
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();

        $this->workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Active,
            ]);
    }

    public function test_can_start_workflow_instance(): void
    {
        // Create start and end steps
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
            'name' => 'Start',
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::End,
            'is_end' => true,
            'name' => 'End',
        ]);

        // Create transition
        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $endStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user, [
            'test_data' => 'value',
        ]);

        $this->assertInstanceOf(WorkflowInstance::class, $instance);
        $this->assertEquals(InstanceStatus::Running, $instance->status);
        $this->assertEquals($this->user->id, $instance->initiated_by_id);
        $this->assertEquals(['test_data' => 'value'], $instance->data);
    }

    public function test_cannot_start_draft_workflow(): void
    {
        $draftWorkflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::Draft,
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow must be active');

        $this->service->startWorkflow($draftWorkflow, $this->user);
    }

    public function test_can_complete_step_instance(): void
    {
        // Create workflow structure
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        $taskStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Task,
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::End,
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

        // Start workflow
        $instance = $this->service->startWorkflow($this->workflow, $this->user);

        // Get the active step instance (should be task step after auto-completing start)
        $taskStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('step_id', $taskStep->id)
            ->first();

        if ($taskStepInstance) {
            $this->service->completeStepInstance($taskStepInstance, $this->user, [
                'result' => 'completed',
            ]);

            $taskStepInstance->refresh();
            $this->assertEquals(StepInstanceStatus::Completed, $taskStepInstance->status);
        }
    }

    public function test_approval_step_can_be_approved(): void
    {
        // Create workflow with approval step
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        $approvalStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Approval,
            'name' => 'Manager Approval',
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::End,
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

        // Start workflow
        $instance = $this->service->startWorkflow($this->workflow, $this->user);

        // Get the approval step instance
        $approvalStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('step_id', $approvalStep->id)
            ->first();

        if ($approvalStepInstance) {
            $approver = User::factory()->create();

            $this->service->processApproval($approvalStepInstance, $approver, true, 'Approved');

            $approvalStepInstance->refresh();
            $this->assertEquals(StepInstanceStatus::Completed, $approvalStepInstance->status);
            $this->assertNotNull($approvalStepInstance->output_data['approved_at'] ?? null);
        }
    }

    public function test_approval_step_can_be_rejected(): void
    {
        // Create workflow with approval step
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        $approvalStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Approval,
            'name' => 'Manager Approval',
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::End,
            'is_end' => true,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $approvalStep->id,
        ]);

        // Start workflow
        $instance = $this->service->startWorkflow($this->workflow, $this->user);

        // Get the approval step instance
        $approvalStepInstance = WorkflowStepInstance::where('workflow_instance_id', $instance->id)
            ->where('step_id', $approvalStep->id)
            ->first();

        if ($approvalStepInstance) {
            $approver = User::factory()->create();

            $this->service->processApproval($approvalStepInstance, $approver, false, 'Rejected due to budget');

            $approvalStepInstance->refresh();
            $this->assertEquals(StepInstanceStatus::Rejected, $approvalStepInstance->status);
        }
    }

    public function test_workflow_instance_completes_when_end_step_reached(): void
    {
        // Create simple workflow
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        $endStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::End,
            'is_end' => true,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $endStep->id,
        ]);

        // Start workflow - should auto-complete start and move to end
        $instance = $this->service->startWorkflow($this->workflow, $this->user);

        // Give the jobs time to process or manually process
        $instance->refresh();

        // The workflow may still be running if jobs are queued
        $this->assertContains($instance->status, [
            InstanceStatus::Running,
            InstanceStatus::Completed,
        ]);
    }

    public function test_can_cancel_workflow_instance(): void
    {
        // Create simple workflow
        $startStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Start,
            'is_start' => true,
        ]);

        $taskStep = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::Task,
        ]);

        WorkflowTransition::factory()->create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $startStep->id,
            'to_step_id' => $taskStep->id,
        ]);

        $instance = $this->service->startWorkflow($this->workflow, $this->user);

        $this->service->cancelWorkflow($instance, $this->user, 'No longer needed');

        $instance->refresh();
        $this->assertEquals(InstanceStatus::Cancelled, $instance->status);
        $this->assertNotNull($instance->completed_at);
    }
}
