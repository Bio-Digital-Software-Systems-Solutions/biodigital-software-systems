<?php

namespace App\Services\Workflow;

use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\WorkflowInstanceStatus;
use App\Enums\Workflow\WorkflowStatus;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowActivityLog;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepInstance;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function __construct(
        protected StepExecutorService $stepExecutor
    ) {}

    /**
     * Create a new workflow.
     */
    public function createWorkflow(array $data): DepartmentWorkflow
    {
        return DepartmentWorkflow::create($data);
    }

    /**
     * Update a workflow.
     */
    public function updateWorkflow(DepartmentWorkflow $workflow, array $data): DepartmentWorkflow
    {
        $workflow->update($data);
        return $workflow->fresh();
    }

    /**
     * Activate a workflow.
     */
    public function activateWorkflow(DepartmentWorkflow $workflow): DepartmentWorkflow
    {
        if ($workflow->steps()->count() === 0) {
            throw new \Exception('Workflow must have at least one step before activation.');
        }

        if (!$workflow->steps()->where('is_start', true)->exists()) {
            throw new \Exception('Workflow must have a start step.');
        }

        return $workflow->activate();
    }

    /**
     * Start a new workflow instance.
     */
    public function startWorkflow(
        DepartmentWorkflow $workflow,
        int $userId,
        array $inputData = [],
        ?int $parentInstanceId = null,
        ?int $parentStepInstanceId = null
    ): WorkflowInstance {
        if (!$workflow->isActive()) {
            throw new \Exception('Cannot start inactive workflow.');
        }

        return DB::transaction(function () use ($workflow, $userId, $inputData, $parentInstanceId, $parentStepInstanceId) {
            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'department_id' => $workflow->department_id,
                'started_by' => $userId,
                'name' => $workflow->name . ' - ' . now()->format('Y-m-d H:i'),
                'status' => WorkflowInstanceStatus::ACTIVE,
                'input_data' => $inputData,
                'context' => array_merge($workflow->variables ?? [], $inputData),
                'parent_instance_id' => $parentInstanceId,
                'parent_step_instance_id' => $parentStepInstanceId,
                'started_at' => now(),
            ]);

            WorkflowActivityLog::workflowStarted($instance);

            // Execute the start step
            $this->executeNextSteps($instance);

            return $instance->fresh();
        });
    }

    /**
     * Execute the next steps in the workflow.
     */
    public function executeNextSteps(WorkflowInstance $instance): void
    {
        if ($instance->isFinished()) {
            return;
        }

        $workflow = $instance->workflow;

        // Get current active step instances
        $activeSteps = $instance->stepInstances()
            ->whereIn('status', [
                StepInstanceStatus::ACTIVE->value,
                StepInstanceStatus::WAITING->value,
            ])
            ->get();

        // If no active steps, start from the beginning
        if ($activeSteps->isEmpty()) {
            $startStep = $workflow->steps()->where('is_start', true)->first();
            if ($startStep) {
                $this->createAndExecuteStepInstance($instance, $startStep);
            }
            return;
        }

        // Check for completed steps and find next transitions
        foreach ($activeSteps as $stepInstance) {
            if ($stepInstance->isCompleted()) {
                $this->processCompletedStep($instance, $stepInstance);
            }
        }
    }

    /**
     * Process a completed step and move to next steps.
     */
    protected function processCompletedStep(WorkflowInstance $instance, WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;

        // Check if this is an end step
        if ($step->is_end) {
            $this->checkWorkflowCompletion($instance);
            return;
        }

        // Get outgoing transitions
        $transitions = $step->outgoingTransitions()->orderBy('priority')->get();

        // Build context for transition evaluation
        $context = array_merge(
            $instance->context ?? [],
            $stepInstance->output_data ?? [],
            ['approval_result' => $stepInstance->context['approval_result'] ?? null]
        );

        $transitionTaken = false;

        foreach ($transitions as $transition) {
            if ($transition->evaluate($context)) {
                $nextStep = $transition->toStep;
                if ($nextStep) {
                    $this->createAndExecuteStepInstance($instance, $nextStep);
                    $transitionTaken = true;

                    // For exclusive gateways, only take first matching transition
                    if ($step->type->value === 'condition') {
                        break;
                    }
                }
            }
        }

        // If no transition taken and there's a default, use it
        if (!$transitionTaken) {
            $defaultTransition = $transitions->where('is_default', true)->first();
            if ($defaultTransition && $defaultTransition->toStep) {
                $this->createAndExecuteStepInstance($instance, $defaultTransition->toStep);
            }
        }

        $this->checkWorkflowCompletion($instance);
    }

    /**
     * Create and execute a step instance.
     */
    protected function createAndExecuteStepInstance(WorkflowInstance $instance, WorkflowStep $step): WorkflowStepInstance
    {
        // Check if step instance already exists and is not finished
        $existingInstance = $instance->stepInstances()
            ->where('workflow_step_id', $step->id)
            ->whereNotIn('status', [
                StepInstanceStatus::COMPLETED->value,
                StepInstanceStatus::SKIPPED->value,
                StepInstanceStatus::FAILED->value,
                StepInstanceStatus::CANCELLED->value,
            ])
            ->first();

        if ($existingInstance) {
            return $existingInstance;
        }

        $stepInstance = WorkflowStepInstance::create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'status' => StepInstanceStatus::PENDING,
            'input_data' => $instance->context,
            'max_attempts' => $step->retry_count ?? 3,
            'due_at' => $step->timeout_hours ? now()->addHours($step->timeout_hours) : null,
        ]);

        WorkflowActivityLog::stepStarted($stepInstance);

        // Execute the step
        $this->stepExecutor->execute($stepInstance);

        return $stepInstance->fresh();
    }

    /**
     * Check if workflow should be completed.
     */
    protected function checkWorkflowCompletion(WorkflowInstance $instance): void
    {
        $instance->refresh();

        // Check if all active/pending steps are done
        $pendingSteps = $instance->stepInstances()
            ->whereIn('status', [
                StepInstanceStatus::PENDING->value,
                StepInstanceStatus::ACTIVE->value,
                StepInstanceStatus::WAITING->value,
            ])
            ->count();

        if ($pendingSteps === 0) {
            // Check if any end step was reached
            $completedEndSteps = $instance->stepInstances()
                ->where('status', StepInstanceStatus::COMPLETED->value)
                ->whereHas('step', fn($q) => $q->where('is_end', true))
                ->exists();

            if ($completedEndSteps) {
                $this->completeWorkflow($instance);
            }
        }
    }

    /**
     * Complete a workflow instance.
     */
    public function completeWorkflow(WorkflowInstance $instance, array $outputData = []): WorkflowInstance
    {
        $instance->complete($outputData);
        WorkflowActivityLog::workflowCompleted($instance);

        // If this is a sub-workflow, notify parent
        if ($instance->parent_step_instance_id) {
            $parentStepInstance = $instance->parentStepInstance;
            if ($parentStepInstance) {
                $parentStepInstance->complete($outputData);
                $this->executeNextSteps($parentStepInstance->workflowInstance);
            }
        }

        return $instance;
    }

    /**
     * Cancel a workflow instance.
     */
    public function cancelWorkflow(WorkflowInstance $instance, ?string $reason = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $reason) {
            // Cancel all pending/active step instances
            $instance->stepInstances()
                ->whereIn('status', [
                    StepInstanceStatus::PENDING->value,
                    StepInstanceStatus::ACTIVE->value,
                    StepInstanceStatus::WAITING->value,
                ])
                ->update([
                    'status' => StepInstanceStatus::CANCELLED,
                    'completed_at' => now(),
                ]);

            $instance->cancel($reason);

            WorkflowActivityLog::log(
                'cancelled',
                WorkflowInstance::class,
                $instance->id,
                $instance->id,
                null,
                null,
                ['reason' => $reason]
            );

            return $instance;
        });
    }

    /**
     * Pause a workflow instance.
     */
    public function pauseWorkflow(WorkflowInstance $instance): WorkflowInstance
    {
        $instance->pause();

        WorkflowActivityLog::log(
            'paused',
            WorkflowInstance::class,
            $instance->id,
            $instance->id
        );

        return $instance;
    }

    /**
     * Resume a paused workflow instance.
     */
    public function resumeWorkflow(WorkflowInstance $instance): WorkflowInstance
    {
        $instance->resume();

        WorkflowActivityLog::log(
            'resumed',
            WorkflowInstance::class,
            $instance->id,
            $instance->id
        );

        $this->executeNextSteps($instance);

        return $instance;
    }

    /**
     * Get workflow statistics.
     */
    public function getWorkflowStats(DepartmentWorkflow $workflow): array
    {
        $instances = $workflow->instances();

        return [
            'total_instances' => $instances->count(),
            'active_instances' => $instances->clone()->where('status', WorkflowInstanceStatus::ACTIVE)->count(),
            'completed_instances' => $instances->clone()->where('status', WorkflowInstanceStatus::COMPLETED)->count(),
            'failed_instances' => $instances->clone()->where('status', WorkflowInstanceStatus::FAILED)->count(),
            'cancelled_instances' => $instances->clone()->where('status', WorkflowInstanceStatus::CANCELLED)->count(),
            'average_completion_time' => $this->calculateAverageCompletionTime($workflow),
        ];
    }

    /**
     * Calculate average completion time for a workflow.
     */
    protected function calculateAverageCompletionTime(DepartmentWorkflow $workflow): ?float
    {
        $completedInstances = $workflow->instances()
            ->where('status', WorkflowInstanceStatus::COMPLETED)
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->get();

        if ($completedInstances->isEmpty()) {
            return null;
        }

        $totalMinutes = $completedInstances->sum(function ($instance) {
            return $instance->started_at->diffInMinutes($instance->completed_at);
        });

        return round($totalMinutes / $completedInstances->count(), 2);
    }
}
