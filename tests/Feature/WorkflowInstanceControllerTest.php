<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepInstance;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\WorkflowInstanceStatus;
use App\Enums\Workflow\StepType;
use App\Enums\Workflow\StepInstanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowInstanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Department $department;
    protected DepartmentWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();
        $this->workflow = DepartmentWorkflow::factory()
            ->for($this->department)
            ->create([
                'created_by' => $this->user->id,
                'status' => WorkflowStatus::ACTIVE,
            ]);
    }

    protected function createInstance(array $attributes = []): WorkflowInstance
    {
        return WorkflowInstance::factory()->create(array_merge([
            'workflow_id' => $this->workflow->id,
            'department_id' => $this->department->id,
            'started_by' => $this->user->id,
            'status' => WorkflowInstanceStatus::ACTIVE,
        ], $attributes));
    }

    // ==========================================
    // Index Page Tests
    // ==========================================

    public function test_user_can_view_workflow_instances_index(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createInstance();
        }

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Index')
                ->has('instances.data', 3)
                ->has('filters')
        );
    }

    public function test_index_can_filter_by_status(): void
    {
        // Create instances with different statuses
        $this->createInstance(['status' => WorkflowInstanceStatus::ACTIVE]);
        $this->createInstance(['status' => WorkflowInstanceStatus::ACTIVE]);
        $this->createInstance(['status' => WorkflowInstanceStatus::COMPLETED]);
        $this->createInstance(['status' => WorkflowInstanceStatus::CANCELLED]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Index')
                ->has('instances.data', 2)
        );
    }

    public function test_index_shows_empty_state_when_no_instances(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Index')
                ->has('instances.data', 0)
        );
    }

    public function test_index_includes_workflow_and_department_info(): void
    {
        $this->createInstance();

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Index')
                ->has('instances.data.0.workflow')
                ->has('instances.data.0.department')
                ->has('instances.data.0.starter')
        );
    }

    // ==========================================
    // Show Page Tests
    // ==========================================

    public function test_user_can_view_workflow_instance_show_page(): void
    {
        $instance = $this->createInstance();

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.show', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Show')
                ->has('instance')
                ->has('progress')
        );
    }

    public function test_show_page_includes_step_instances(): void
    {
        $instance = $this->createInstance();

        $step = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::ACTION,
        ]);

        WorkflowStepInstance::factory()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'status' => StepInstanceStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.show', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Show')
                ->has('instance.step_instances', 1)
        );
    }

    public function test_user_can_pause_active_instance(): void
    {
        $instance = $this->createInstance(['status' => WorkflowInstanceStatus::ACTIVE]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-instances.pause', $instance));

        $response->assertRedirect();

        $instance->refresh();
        $this->assertEquals(WorkflowInstanceStatus::PAUSED, $instance->status);
    }

    public function test_user_can_resume_paused_instance(): void
    {
        $instance = $this->createInstance(['status' => WorkflowInstanceStatus::PAUSED]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-instances.resume', $instance));

        $response->assertRedirect();

        $instance->refresh();
        $this->assertEquals(WorkflowInstanceStatus::ACTIVE, $instance->status);
    }

    public function test_user_can_cancel_instance(): void
    {
        $instance = $this->createInstance(['status' => WorkflowInstanceStatus::ACTIVE]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-instances.cancel', $instance), [
                'reason' => 'Test cancellation reason',
            ]);

        $response->assertRedirect();

        $instance->refresh();
        $this->assertEquals(WorkflowInstanceStatus::CANCELLED, $instance->status);
        $this->assertEquals('Test cancellation reason', $instance->cancellation_reason);
    }

    public function test_user_cannot_pause_completed_instance(): void
    {
        $instance = $this->createInstance(['status' => WorkflowInstanceStatus::COMPLETED]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-instances.pause', $instance));

        $instance->refresh();
        // Should still be completed since pause should not work on completed instances
        $this->assertEquals(WorkflowInstanceStatus::COMPLETED, $instance->status);
    }

    public function test_instance_show_displays_current_step(): void
    {
        $instance = $this->createInstance();

        $step = WorkflowStep::factory()->create([
            'workflow_id' => $this->workflow->id,
            'type' => StepType::ACTION,
            'name' => 'Current Active Step',
        ]);

        WorkflowStepInstance::factory()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'status' => StepInstanceStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.show', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Show')
                ->has('currentStep')
        );
    }

    public function test_instance_show_calculates_progress(): void
    {
        // Create a workflow with 4 steps
        $steps = [];
        for ($i = 0; $i < 4; $i++) {
            $steps[] = WorkflowStep::factory()->create([
                'workflow_id' => $this->workflow->id,
            ]);
        }

        $instance = $this->createInstance();

        // Complete 2 of 4 steps (50% progress)
        WorkflowStepInstance::factory()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $steps[0]->id,
            'status' => StepInstanceStatus::COMPLETED,
        ]);

        WorkflowStepInstance::factory()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $steps[1]->id,
            'status' => StepInstanceStatus::COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.show', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/Show')
                ->where('progress', 50)
        );
    }

    // ==========================================
    // ActivityLog Page Tests
    // ==========================================

    public function test_user_can_view_activity_log_page(): void
    {
        $instance = $this->createInstance();

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->has('instance')
                ->has('logs')
        );
    }

    public function test_activity_log_displays_logs(): void
    {
        $instance = $this->createInstance();

        // Create some activity logs
        \App\Models\WorkflowActivityLog::factory()
            ->count(5)
            ->create([
                'workflow_instance_id' => $instance->id,
                'user_id' => $this->user->id,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->has('logs.data', 5)
        );
    }

    public function test_activity_log_shows_empty_state_when_no_logs(): void
    {
        $instance = $this->createInstance();

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->has('logs.data', 0)
        );
    }

    public function test_activity_log_includes_user_info(): void
    {
        $instance = $this->createInstance();

        \App\Models\WorkflowActivityLog::factory()->create([
            'workflow_instance_id' => $instance->id,
            'user_id' => $this->user->id,
            'action' => 'started',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->has('logs.data.0.user')
        );
    }

    public function test_activity_log_is_ordered_by_created_at_descending(): void
    {
        $instance = $this->createInstance();

        // Create logs with specific timestamps
        $oldLog = \App\Models\WorkflowActivityLog::factory()->create([
            'workflow_instance_id' => $instance->id,
            'user_id' => $this->user->id,
            'action' => 'started',
            'created_at' => now()->subHours(2),
        ]);

        $newLog = \App\Models\WorkflowActivityLog::factory()->create([
            'workflow_instance_id' => $instance->id,
            'user_id' => $this->user->id,
            'action' => 'completed',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->where('logs.data.0.action', 'completed')
                ->where('logs.data.1.action', 'started')
        );
    }

    public function test_activity_log_includes_instance_info(): void
    {
        $instance = $this->createInstance();

        $response = $this->actingAs($this->user)
            ->get(route('workflow-instances.activity-log', $instance));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('WorkflowInstances/ActivityLog')
                ->has('instance')
                ->where('instance.uuid', (string) $instance->uuid)
        );
    }
}
