<?php

namespace App\Jobs;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowTransition;
use App\Models\WorkflowStep;
use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\WorkflowInstanceStatus;
use App\Enums\Workflow\StepType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteWorkflowTransition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public WorkflowStepInstance $completedStepInstance
    ) {}

    public function handle(): void
    {
        $stepInstance = $this->completedStepInstance->fresh(['step', 'workflowInstance.workflow']);

        if (!$stepInstance) {
            Log::warning('Step instance not found for transition', ['id' => $this->completedStepInstance->id]);
            return;
        }

        $workflowInstance = $stepInstance->workflowInstance;

        // Check if workflow is still running
        if ($workflowInstance->status !== WorkflowInstanceStatus::RUNNING) {
            Log::info('Workflow not running, skipping transition', [
                'workflow_instance_id' => $workflowInstance->id,
            ]);
            return;
        }

        $step = $stepInstance->step;

        // Check if this is an end step
        if ($step->is_end) {
            $this->checkWorkflowCompletion($workflowInstance);
            return;
        }

        // Get outgoing transitions
        $transitions = WorkflowTransition::where('from_step_id', $step->id)
            ->orderBy('priority')
            ->get();

        if ($transitions->isEmpty()) {
            Log::warning('No outgoing transitions found', [
                'step_id' => $step->id,
                'step_name' => $step->name,
            ]);
            return;
        }

        // Evaluate transitions and find next steps
        $nextSteps = [];
        $workflowData = $workflowInstance->data ?? [];

        foreach ($transitions as $transition) {
            if ($transition->evaluate($workflowData)) {
                $nextSteps[] = $transition->toStep;

                // If not parallel and not condition, take first matching transition
                if ($step->type !== StepType::PARALLEL && $step->type !== StepType::CONDITION) {
                    break;
                }
            }
        }

        if ($nextSteps === []) {
            // Use default transition if no condition matched
            $defaultTransition = $transitions->firstWhere('is_default', true);
            if ($defaultTransition) {
                $nextSteps[] = $defaultTransition->toStep;
            } else {
                Log::warning('No matching transition found', [
                    'step_id' => $step->id,
                    'workflow_data' => $workflowData,
                ]);
                return;
            }
        }

        // Create step instances for next steps
        foreach ($nextSteps as $nextStep) {
            $this->createAndDispatchStep($workflowInstance, $nextStep);
        }

        Log::info('Transitions executed', [
            'from_step_id' => $step->id,
            'next_steps_count' => count($nextSteps),
        ]);
    }

    private function createAndDispatchStep($workflowInstance, WorkflowStep $step): void
    {
        // Check if step instance already exists
        $existingInstance = WorkflowStepInstance::where('workflow_instance_id', $workflowInstance->id)
            ->where('step_id', $step->id)
            ->first();

        if ($existingInstance) {
            Log::info('Step instance already exists', [
                'step_id' => $step->id,
                'existing_status' => $existingInstance->status->value,
            ]);

            // For parallel joins, check if all incoming branches are complete
            if ($step->type === StepType::PARALLEL && $existingInstance->status === StepInstanceStatus::WAITING) {
                $this->checkParallelJoin($existingInstance);
            }
            return;
        }

        // Create new step instance
        $stepInstance = WorkflowStepInstance::create([
            'workflow_instance_id' => $workflowInstance->id,
            'step_id' => $step->id,
            'status' => StepInstanceStatus::PENDING,
            'assigned_to_id' => $this->determineAssignee($step, $workflowInstance),
        ]);

        // Dispatch job to process the step
        ProcessWorkflowStep::dispatch($stepInstance);

        Log::info('Step instance created and dispatched', [
            'step_instance_id' => $stepInstance->id,
            'step_type' => $step->type->value,
        ]);
    }

    private function determineAssignee(WorkflowStep $step, $workflowInstance): ?int
    {
        $config = $step->config ?? [];
        $assignmentType = $config['assignment_type'] ?? 'user';

        switch ($assignmentType) {
            case 'initiator':
                return $workflowInstance->initiated_by_id;

            case 'previous':
                $previousInstance = WorkflowStepInstance::where('workflow_instance_id', $workflowInstance->id)
                    ->where('status', StepInstanceStatus::COMPLETED)
                    ->latest('completed_at')
                    ->first();
                return $previousInstance?->assigned_to_id;

            case 'user':
                return $config['assigned_user_id'] ?? null;

            case 'role':
                // Get first user with the specified role
                if (isset($config['assigned_role'])) {
                    $user = \App\Models\User::role($config['assigned_role'])->first();
                    return $user?->id;
                }
                return null;

            case 'department':
                // Get department manager
                if (isset($config['assigned_department_id'])) {
                    $department = \App\Models\Department::find($config['assigned_department_id']);
                    return $department?->manager_id;
                }
                return null;

            default:
                return null;
        }
    }

    private function checkParallelJoin(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $workflowInstance = $stepInstance->workflowInstance;

        // Get all incoming transitions
        $incomingTransitions = WorkflowTransition::where('to_step_id', $step->id)->get();

        // Check if all source steps are completed
        $allComplete = true;
        foreach ($incomingTransitions as $transition) {
            $sourceInstance = WorkflowStepInstance::where('workflow_instance_id', $workflowInstance->id)
                ->where('step_id', $transition->from_step_id)
                ->first();

            if (!$sourceInstance || $sourceInstance->status !== StepInstanceStatus::COMPLETED) {
                $allComplete = false;
                break;
            }
        }

        if ($allComplete) {
            // All branches complete, proceed with the parallel join step
            $stepInstance->update(['status' => StepInstanceStatus::PENDING]);
            ProcessWorkflowStep::dispatch($stepInstance);
        }
    }

    private function checkWorkflowCompletion($workflowInstance): void
    {
        // Get all end steps
        $endSteps = $workflowInstance->workflow->steps()
            ->where('is_end', true)
            ->pluck('id');

        // Check if all end steps have been reached
        $completedEndSteps = WorkflowStepInstance::where('workflow_instance_id', $workflowInstance->id)
            ->whereIn('step_id', $endSteps)
            ->where('status', StepInstanceStatus::COMPLETED)
            ->count();

        // If at least one end step is complete, mark workflow as complete
        if ($completedEndSteps > 0) {
            $workflowInstance->update([
                'status' => WorkflowInstanceStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            Log::info('Workflow completed', [
                'workflow_instance_id' => $workflowInstance->id,
            ]);
        }
    }
}
